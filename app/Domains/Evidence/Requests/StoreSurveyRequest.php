<?php

namespace App\Domains\Evidence\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('evidence.surveys.create') ?? false;
    }

    public function rules(): array
    {
        return $this->structureRules(true);
    }

    protected function structureRules(bool $isCreate): array
    {
        $required = $isCreate ? 'required' : 'sometimes';

        return [
            'code' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:50', 'unique:surveys,code'],
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'version' => ['nullable', 'integer', 'min:1'],
            'questions' => [$required, 'array', 'min:1'],
            'questions.*.question_key' => ['required', 'string', 'max:100'],
            'questions.*.prompt' => ['required', 'string'],
            'questions.*.question_type' => [
                'required',
                'string',
                Rule::in(['text', 'textarea', 'number', 'boolean', 'single_choice', 'multiple_choice']),
            ],
            'questions.*.is_required' => ['nullable', 'boolean'],
            'questions.*.display_order' => ['nullable', 'integer', 'min:1'],
            'questions.*.configuration' => ['nullable', 'array'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*.option_code' => ['required', 'string', 'max:100'],
            'questions.*.options.*.label' => ['required', 'string', 'max:255'],
            'questions.*.options.*.raw_value' => ['nullable', 'string', 'max:255'],
            'questions.*.options.*.display_order' => ['nullable', 'integer', 'min:1'],
            'scenarios' => [$required, 'array', 'min:1'],
            'scenarios.*.scenario_code' => ['required', 'string', 'max:50'],
            'scenarios.*.inventory_object_id' => ['required', 'integer', 'exists:inventory_objects,id'],
            'scenarios.*.inventory_object_unit_id' => ['required', 'integer', 'exists:inventory_object_units,id'],
            'scenarios.*.proposed_price' => ['required', 'numeric', 'min:0'],
            'scenarios.*.currency_code' => ['nullable', 'string', 'size:3'],
            'scenarios.*.description' => ['nullable', 'string'],
            'scenarios.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}
