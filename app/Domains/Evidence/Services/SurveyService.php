<?php

namespace App\Domains\Evidence\Services;

use App\Domains\Evidence\Models\Survey;
use App\Domains\Inventory\Models\InventoryObjectUnit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SurveyService
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Survey::query()
            ->withCount(['questions', 'responses', 'scenarios'])
            ->latest('id')
            ->paginate($perPage);
    }

    public function create(array $data, int $userId): Survey
    {
        return DB::transaction(function () use ($data, $userId) {
            $survey = Survey::create([
                ...Arr::only($data, ['code', 'name', 'description', 'version']),
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            $this->replaceStructure($survey, $data);

            return $this->loadDetail($survey);
        });
    }

    public function update(Survey $survey, array $data): Survey
    {
        if ($survey->status !== 'draft') {
            throw ValidationException::withMessages([
                'survey' => 'Only draft surveys can be changed. Create a new version for a published survey.',
            ]);
        }

        return DB::transaction(function () use ($survey, $data) {
            $survey->fill(Arr::only($data, ['code', 'name', 'description', 'version']));
            $survey->save();

            if (array_key_exists('questions', $data) || array_key_exists('scenarios', $data)) {
                $this->replaceStructure($survey, $data);
            }

            return $this->loadDetail($survey);
        });
    }

    public function publish(Survey $survey, int $userId): Survey
    {
        if ($survey->status === 'published') {
            return $this->loadDetail($survey);
        }

        if ($survey->status !== 'draft') {
            throw ValidationException::withMessages([
                'survey' => 'Only draft surveys can be published.',
            ]);
        }

        if (! $survey->questions()->exists()) {
            throw ValidationException::withMessages([
                'questions' => 'A survey needs at least one question before publication.',
            ]);
        }

        if (! $survey->scenarios()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'scenarios' => 'A purchase scenario is required before publication.',
            ]);
        }

        $survey->forceFill([
            'status' => 'published',
            'published_by' => $userId,
            'published_at' => now(),
        ])->save();

        return $this->loadDetail($survey);
    }

    public function detail(Survey $survey): Survey
    {
        return $this->loadDetail($survey);
    }

    public function responsePaginate(Survey $survey, int $perPage = 15): LengthAwarePaginator
    {
        return $survey->responses()
            ->with([
                'respondent',
                'answers.question',
                'answers.option',
                'simulatedPurchaseEvents.evidence',
                'simulatedPurchaseEvents.inventoryObjectUnit.unit',
            ])
            ->latest('submitted_at')
            ->paginate($perPage);
    }

    private function replaceStructure(Survey $survey, array $data): void
    {
        if (array_key_exists('questions', $data)) {
            $survey->questions()->delete();

            foreach ($data['questions'] as $index => $questionData) {
                $question = $survey->questions()->create([
                    ...Arr::only($questionData, [
                        'question_key',
                        'prompt',
                        'question_type',
                        'is_required',
                        'configuration',
                    ]),
                    'display_order' => $questionData['display_order'] ?? $index + 1,
                ]);

                foreach ($questionData['options'] ?? [] as $optionIndex => $optionData) {
                    $question->options()->create([
                        ...Arr::only($optionData, ['option_code', 'label', 'raw_value']),
                        'display_order' => $optionData['display_order'] ?? $optionIndex + 1,
                    ]);
                }
            }
        }

        if (array_key_exists('scenarios', $data)) {
            $survey->scenarios()->delete();

            foreach ($data['scenarios'] as $scenarioData) {
                $uom = InventoryObjectUnit::query()->findOrFail(
                    $scenarioData['inventory_object_unit_id']
                );

                if ((int) $uom->inventory_object_id !== (int) $scenarioData['inventory_object_id']) {
                    throw ValidationException::withMessages([
                        'scenarios' => 'Each scenario product unit must belong to its selected product.',
                    ]);
                }

                $experiment = app(EvidenceScenarioService::class)->create([
                    'code' => 'survey-'.$survey->id.'-'.(string) Str::ulid(),
                    'name' => $scenarioData['scenario_code'],
                    'description' => $scenarioData['description'] ?? null,
                    'context_payload' => ['survey_id' => $survey->id],
                ], $survey->created_by);

                $survey->scenarios()->create([
                    'evidence_scenario_id' => $experiment->id,
                    ...Arr::only($scenarioData, [
                        'scenario_code',
                        'inventory_object_id',
                        'inventory_object_unit_id',
                        'proposed_price',
                        'currency_code',
                        'description',
                        'is_active',
                    ]),
                    'currency_code' => strtoupper($scenarioData['currency_code'] ?? 'PHP'),
                    'is_active' => $scenarioData['is_active'] ?? true,
                ]);
            }
        }
    }

    private function loadDetail(Survey $survey): Survey
    {
        return $survey->fresh([
            'questions.options',
            'scenarios.inventoryObject.baseUnit',
            'scenarios.inventoryObjectUnit.unit',
        ]);
    }
}
