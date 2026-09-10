<?php

namespace App\Domains\Evidence\Services;

use App\Domains\Evidence\Models\EvidenceRecord;
use App\Domains\Evidence\Models\MarketObservation;
use App\Domains\Evidence\Models\SimulatedPurchaseEvent;
use App\Domains\Evidence\Models\SupplierProductObservation;
use App\Domains\Evidence\Models\Survey;
use App\Domains\Evidence\Models\SurveyQuestion;
use App\Domains\Evidence\Models\SurveyQuestionOption;
use App\Domains\Evidence\Models\SurveyRespondent;
use App\Domains\Evidence\Models\SurveyResponse;
use App\Domains\Evidence\Models\SurveyScenario;
use App\Domains\Inventory\Models\InventoryObjectUnit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EvidenceService
{
    public const EPISTEMIC_STATUSES = [
        'observed',
        'reported',
        'accounted',
        'simulated',
        'theoretical',
        'assumed',
        'derived',
        'estimated',
        'unknown',
    ];

    /**
     * Observations are append-only. A newer report creates another evidence
     * record and never changes an earlier supplier observation.
     */
    public function recordSupplierObservation(
        array $data,
        ?int $userId
    ): SupplierProductObservation {
        return DB::transaction(function () use ($data, $userId) {
            $uom = $this->findInventoryObjectUnit(
                (int) $data['inventory_object_unit_id']
            );

            $evidence = EvidenceRecord::create([
                'inventory_object_id' => $uom->inventory_object_id,
                'source_type' => 'supplier',
                'epistemic_status' => $data['epistemic_status'] ?? 'reported',
                'collection_method' => $data['collection_method'],
                'source_reference' => $data['source_reference'] ?? null,
                'source_entity_type' => 'supplier',
                'source_entity_id' => $data['supplier_id'],
                'observed_at' => $data['observed_at'],
                'recorded_at' => now(),
                'location_name' => $data['supplier_location'] ?? null,
                'raw_payload' => $data['raw_payload'] ?? null,
                'context_payload' => $data['context_payload'] ?? null,
                'notes' => $data['notes'] ?? null,
                'collected_by' => $userId,
            ]);

            return SupplierProductObservation::create([
                'evidence_record_id' => $evidence->id,
                'supplier_id' => $data['supplier_id'],
                'inventory_object_unit_id' => $uom->id,
                'purchase_price' => $data['purchase_price'],
                'currency_code' => strtoupper($data['currency_code'] ?? 'PHP'),
                'minimum_order_quantity' => $data['minimum_order_quantity'] ?? null,
                'available_quantity' => $data['available_quantity'] ?? null,
                'product_specification' => $data['product_specification'] ?? null,
                'freight_amount' => $data['freight_amount'] ?? null,
                'freight_terms' => $data['freight_terms'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'lead_time_days' => $data['lead_time_days'] ?? null,
                'discount_amount' => $data['discount_amount'] ?? null,
                'discount_description' => $data['discount_description'] ?? null,
                'supplier_location' => $data['supplier_location'] ?? null,
            ]);
        });
    }

    /**
     * Market observations are likewise append-only historical evidence.
     */
    public function recordMarketObservation(
        array $data,
        ?int $userId
    ): MarketObservation {
        return DB::transaction(function () use ($data, $userId) {
            $uom = $this->findInventoryObjectUnit(
                (int) $data['inventory_object_unit_id']
            );

            $evidence = EvidenceRecord::create([
                'inventory_object_id' => $uom->inventory_object_id,
                'source_type' => 'market',
                'epistemic_status' => $data['epistemic_status'] ?? 'observed',
                'collection_method' => $data['collection_method'],
                'source_reference' => $data['source_reference'] ?? null,
                'source_entity_type' => 'market_store',
                'observed_at' => $data['observed_at'],
                'recorded_at' => now(),
                'location_name' => $data['location_name'] ?? null,
                'raw_payload' => $data['raw_payload'] ?? null,
                'context_payload' => $data['context_payload'] ?? null,
                'notes' => $data['notes'] ?? null,
                'collected_by' => $userId,
            ]);

            return MarketObservation::create([
                'evidence_record_id' => $evidence->id,
                'inventory_object_unit_id' => $uom->id,
                'store_name' => $data['store_name'],
                'channel' => $data['channel'],
                'selling_price' => $data['selling_price'],
                'currency_code' => strtoupper($data['currency_code'] ?? 'PHP'),
                'package_size' => $data['package_size'] ?? null,
                'availability_status' => $data['availability_status'] ?? null,
                'promotion' => $data['promotion'] ?? null,
            ]);
        });
    }

    /**
     * Public responses have no collector user. Internal assisted collection may
     * supply one. Original answers are saved before a separate simulated event
     * is derived, and this method never creates a sale or stock movement.
     */
    public function submitResponse(array $data, ?int $userId = null): SurveyResponse
    {
        return DB::transaction(function () use ($data, $userId) {
            $survey = Survey::query()
                ->with('questions.options')
                ->findOrFail($data['survey_id']);

            if ($survey->status !== 'published') {
                throw ValidationException::withMessages([
                    'survey_id' => 'Only published surveys can receive responses.',
                ]);
            }

            $this->ensureRequiredQuestionsAreAnswered($survey, $data['answers']);

            $respondent = $this->resolveRespondent($data);
            $response = SurveyResponse::create([
                'survey_id' => $survey->id,
                'survey_respondent_id' => $respondent->id,
                'status' => 'submitted',
                'started_at' => $data['started_at'] ?? now(),
                'submitted_at' => now(),
                'raw_payload' => $data['raw_payload'] ?? null,
                'collected_by' => $userId,
            ]);

            foreach ($data['answers'] as $answer) {
                $question = $survey->questions->first(
                    fn (SurveyQuestion $question) => (int) $question->id
                        === (int) $answer['survey_question_id']
                );

                if (! $question) {
                    throw ValidationException::withMessages([
                        'answers' => 'An answer question does not belong to this survey.',
                    ]);
                }

                $option = $this->answerOptionForQuestion($question, $answer);
                $rawValue = array_key_exists('raw_value', $answer)
                    ? $answer['raw_value']
                    : ($option ? [
                        'option_code' => $option->option_code,
                        'label' => $option->label,
                        'value' => $option->raw_value,
                    ] : null);

                $response->answers()->create([
                    'survey_question_id' => $question->id,
                    'survey_question_option_id' => $option?->id,
                    'raw_value' => $rawValue,
                    'raw_text' => $answer['raw_text'] ?? null,
                    'answered_at' => now(),
                ]);
            }

            if (! empty($data['simulated_purchase'])) {
                $this->createSimulatedEvent(
                    $response,
                    $respondent,
                    $data['simulated_purchase'],
                    $userId
                );
            }

            return $response->fresh([
                'respondent',
                'answers.question',
                'answers.option',
                'simulatedPurchaseEvents.evidence',
                'simulatedPurchaseEvents.inventoryObjectUnit.unit',
                'simulatedPurchaseEvents.alternativeInventoryObject',
            ]);
        });
    }

    public function supplierObservations(
        ?int $inventoryObjectId,
        ?int $supplierId,
        int $perPage = 15
    ): LengthAwarePaginator {
        return SupplierProductObservation::query()
            ->with([
                'evidence.collector',
                'supplier',
                'inventoryObjectUnit.inventoryObject',
                'inventoryObjectUnit.unit',
            ])
            ->when($inventoryObjectId, function ($query) use ($inventoryObjectId) {
                $query->whereHas('evidence', function ($evidence) use ($inventoryObjectId) {
                    $evidence->where('inventory_object_id', $inventoryObjectId);
                });
            })
            ->when($supplierId, fn ($query) => $query->where('supplier_id', $supplierId))
            ->latest('id')
            ->paginate($perPage);
    }

    public function marketObservations(
        ?int $inventoryObjectId,
        ?string $channel,
        int $perPage = 15
    ): LengthAwarePaginator {
        return MarketObservation::query()
            ->with([
                'evidence.collector',
                'inventoryObjectUnit.inventoryObject',
                'inventoryObjectUnit.unit',
            ])
            ->when($inventoryObjectId, function ($query) use ($inventoryObjectId) {
                $query->whereHas('evidence', function ($evidence) use ($inventoryObjectId) {
                    $evidence->where('inventory_object_id', $inventoryObjectId);
                });
            })
            ->when($channel, fn ($query) => $query->where('channel', $channel))
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Product evidence reads only the evidence graph and the separate simulated
     * event table; it never touches sales, orders, revenue, or stock movements.
     *
     * @return Collection<int, EvidenceRecord>
     */
    public function forInventoryObject(int $inventoryObjectId): Collection
    {
        return EvidenceRecord::query()
            ->where('inventory_object_id', $inventoryObjectId)
            ->with([
                'collector',
                'supplierObservation.supplier',
                'supplierObservation.inventoryObjectUnit.inventoryObject',
                'supplierObservation.inventoryObjectUnit.unit',
                'marketObservation.inventoryObjectUnit.inventoryObject',
                'marketObservation.inventoryObjectUnit.unit',
                'simulatedPurchaseEvent.response.respondent',
                'simulatedPurchaseEvent.response.answers.question',
                'simulatedPurchaseEvent.response.answers.option',
                'simulatedPurchaseEvent.inventoryObjectUnit.inventoryObject',
                'simulatedPurchaseEvent.inventoryObjectUnit.unit',
                'simulatedPurchaseEvent.alternativeInventoryObject',
            ])
            ->latest('observed_at')
            ->latest('id')
            ->get();
    }

    private function findInventoryObjectUnit(int $inventoryObjectUnitId): InventoryObjectUnit
    {
        return InventoryObjectUnit::query()->findOrFail($inventoryObjectUnitId);
    }

    private function resolveRespondent(array $data): SurveyRespondent
    {
        $sessionReference = $data['session_reference'] ?? null;
        $respondent = $sessionReference
            ? SurveyRespondent::query()
                ->where('session_reference', $sessionReference)
                ->first()
            : null;

        if (! $respondent && ! empty($data['respondent_code'])) {
            $respondent = SurveyRespondent::query()
                ->where('respondent_code', $data['respondent_code'])
                ->first();
        }

        if (! $respondent) {
            $respondent = SurveyRespondent::create([
                'respondent_code' => $data['respondent_code']
                    ?? 'RESP-'.strtoupper((string) Str::ulid()),
                'session_reference' => $sessionReference,
                'profile_payload' => $data['profile_payload'] ?? null,
            ]);
        }

        return $respondent;
    }

    private function ensureRequiredQuestionsAreAnswered(
        Survey $survey,
        array $answers
    ): void {
        $answeredQuestionIds = collect($answers)
            ->pluck('survey_question_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($answeredQuestionIds->count() !== count($answers)) {
            throw ValidationException::withMessages([
                'answers' => 'A survey question may be answered only once per response.',
            ]);
        }

        $missingQuestionIds = $survey->questions
            ->where('is_required', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->diff($answeredQuestionIds)
            ->values();

        if ($missingQuestionIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'answers' => 'Every required survey question must be answered.',
            ]);
        }
    }

    private function answerOptionForQuestion(
        SurveyQuestion $question,
        array $answer
    ): ?SurveyQuestionOption {
        if (empty($answer['survey_question_option_id'])) {
            return null;
        }

        $option = $question->options->first(
            fn (SurveyQuestionOption $option) => (int) $option->id
                === (int) $answer['survey_question_option_id']
        );

        if (! $option) {
            throw ValidationException::withMessages([
                'answers' => 'An answer option does not belong to its survey question.',
            ]);
        }

        return $option;
    }

    private function createSimulatedEvent(
        SurveyResponse $response,
        SurveyRespondent $respondent,
        array $data,
        ?int $userId
    ): SimulatedPurchaseEvent {
        $scenario = isset($data['survey_scenario_id'])
            ? SurveyScenario::query()->findOrFail($data['survey_scenario_id'])
            : null;

        $inventoryObjectUnitId = $scenario?->inventory_object_unit_id
            ?? ($data['inventory_object_unit_id'] ?? null);

        if (! $inventoryObjectUnitId) {
            throw ValidationException::withMessages([
                'simulated_purchase.inventory_object_unit_id' => 'A product unit is required for a simulated purchase.',
            ]);
        }

        $uom = $this->findInventoryObjectUnit((int) $inventoryObjectUnitId);

        if ($scenario) {
            $matchesScenario = (int) $scenario->survey_id === (int) $response->survey_id
                && $scenario->is_active
                && (! isset($data['inventory_object_unit_id'])
                    || (int) $data['inventory_object_unit_id'] === (int) $scenario->inventory_object_unit_id)
                && (int) $scenario->inventory_object_unit_id === (int) $uom->id
                && (int) $scenario->inventory_object_id === (int) $uom->inventory_object_id;

            if (! $matchesScenario) {
                throw ValidationException::withMessages([
                    'simulated_purchase.survey_scenario_id' => 'The scenario must belong to the response survey and selected product unit.',
                ]);
            }
        }

        $proposedPrice = $scenario?->proposed_price
            ?? ($data['proposed_price'] ?? null);

        if ($proposedPrice === null) {
            throw ValidationException::withMessages([
                'simulated_purchase.proposed_price' => 'A proposed price is required when no scenario is supplied.',
            ]);
        }

        if ($scenario && isset($data['proposed_price'])
            && (float) $data['proposed_price'] !== (float) $scenario->proposed_price) {
            throw ValidationException::withMessages([
                'simulated_purchase.proposed_price' => 'Price must match the published scenario.',
            ]);
        }

        $wouldPurchase = (bool) $data['would_purchase'];
        $quantity = $wouldPurchase ? ($data['quantity'] ?? null) : null;
        $conversionFactor = $uom->conversion_factor;
        if ($conversionFactor !== null && (! is_numeric($conversionFactor) || (float) $conversionFactor <= 0)) {
            throw ValidationException::withMessages([
                'simulated_purchase.inventory_object_unit_id' => 'The product unit must have a positive explicit conversion factor, or an unknown (null) factor.',
            ]);
        }
        $uom->loadMissing('unit', 'inventoryObject.baseUnit');
        $currency = $scenario?->currency_code ?? ($data['currency_code'] ?? null);
        if ($scenario && isset($data['currency_code'])
            && strtoupper($data['currency_code']) !== strtoupper($scenario->currency_code)) {
            throw ValidationException::withMessages([
                'simulated_purchase.currency_code' => 'Currency must match the published scenario.',
            ]);
        }
        $rawPurchasePayload = $data['raw_payload'] ?? [
            'would_purchase' => $data['would_purchase'],
            'quantity' => $data['quantity'] ?? null,
            'frequency_raw' => $data['frequency_raw'] ?? null,
            'alternative_inventory_object_id' => $data['alternative_inventory_object_id'] ?? null,
            'decision_reason' => $data['decision_reason'] ?? null,
        ];

        $evidence = EvidenceRecord::create([
            'evidence_scenario_id' => $scenario?->evidence_scenario_id,
            'inventory_object_id' => $uom->inventory_object_id,
            'source_type' => 'consumer_survey',
            'epistemic_status' => 'simulated',
            'collection_method' => 'survey',
            'source_reference' => 'survey_response:'.$response->id,
            'source_entity_type' => 'survey_response',
            'source_entity_id' => $response->id,
            'observed_at' => now(),
            'recorded_at' => now(),
            'raw_payload' => $rawPurchasePayload,
            'context_payload' => $data['context_payload'] ?? null,
            'notes' => $data['decision_reason'] ?? null,
            'collected_by' => $userId,
        ]);

        return SimulatedPurchaseEvent::create([
            'evidence_record_id' => $evidence->id,
            'survey_id' => $response->survey_id,
            'survey_respondent_id' => $respondent->id,
            'survey_response_id' => $response->id,
            'survey_scenario_id' => $scenario?->id,
            'inventory_object_unit_id' => $uom->id,
            'source_type' => 'consumer_survey',
            'epistemic_status' => 'simulated',
            'would_purchase' => $wouldPurchase,
            'proposed_price' => $proposedPrice,
            'currency_code' => $currency === null ? null : strtoupper($currency),
            'unit_snapshot' => [
                'unit_id' => $uom->unit_id,
                'unit_code' => $uom->unit?->code,
                'unit_name' => $uom->unit?->name,
                'base_unit_id' => $uom->inventoryObject->unit_id,
                'base_unit_code' => $uom->inventoryObject->baseUnit?->code,
                'conversion_source' => 'inventory_object_units:'.$uom->id,
                'captured_at' => now()->toIso8601String(),
            ],
            'simulated_quantity' => $quantity,
            'base_quantity' => $quantity === null || $conversionFactor === null
                ? null
                : round((float) $quantity * (float) $conversionFactor, 6),
            'conversion_factor' => $quantity === null ? null : $conversionFactor,
            'frequency_raw' => $data['frequency_raw'] ?? null,
            'estimated_frequency_per_month' => $data['estimated_frequency_per_month'] ?? null,
            'alternative_inventory_object_id' => $data['alternative_inventory_object_id'] ?? null,
            'decision_reason' => $data['decision_reason'] ?? null,
            'derived_payload' => $data['derived_payload'] ?? null,
            'simulated_at' => now(),
        ]);
    }
}
