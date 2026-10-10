<?php

namespace App\Domains\Evidence\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSurveyResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $survey = $this->route('survey');

        if ($survey) {
            $this->merge([
                'survey_id' => is_object($survey) ? $survey->id : $survey,
            ]);
        }
    }

    public function rules(): array
    {
        $isPublic = $this->is('api/evidence/public/*');

        return [
            'survey_id' => ['required', 'integer', 'exists:surveys,id'],
            'respondent_code' => ['nullable', 'string', 'max:50'],
            'session_reference' => ['nullable', 'string', 'max:100'],
            'profile_payload' => ['nullable', 'array'],
            'started_at' => ['nullable', 'date'],
            'raw_payload' => ['nullable', 'array'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.survey_question_id' => ['required', 'integer', 'exists:survey_questions,id'],
            'answers.*.survey_question_option_id' => ['nullable', 'integer', 'exists:survey_question_options,id'],
            'answers.*.raw_value' => ['nullable'],
            'answers.*.raw_text' => ['nullable', 'string'],
            'simulated_purchase' => ['nullable', 'array'],
            'simulated_purchase.survey_scenario_id' => [$isPublic ? 'required_with:simulated_purchase' : 'nullable', 'integer', 'exists:survey_scenarios,id'],
            'simulated_purchase.currency_code' => ['nullable', 'string', 'regex:/^[A-Za-z]{3}$/'],
            'simulated_purchase.inventory_object_unit_id' => ['nullable', 'integer', 'exists:inventory_object_units,id'],
            'simulated_purchase.would_purchase' => ['required_with:simulated_purchase', 'boolean'],
            'simulated_purchase.proposed_price' => ['nullable', 'numeric', 'min:0'],
            'simulated_purchase.quantity' => ['nullable', 'numeric', 'min:0'],
            'simulated_purchase.frequency_raw' => ['nullable', 'string', 'max:100'],
            'simulated_purchase.estimated_frequency_per_month' => ['nullable', 'numeric', 'min:0'],
            'simulated_purchase.alternative_inventory_object_id' => ['nullable', 'integer', 'exists:inventory_objects,id'],
            'simulated_purchase.decision_reason' => ['nullable', 'string'],
            'simulated_purchase.raw_payload' => ['nullable', 'array'],
            'simulated_purchase.context_payload' => ['nullable', 'array'],
            'simulated_purchase.derived_payload' => ['nullable', 'array'],
        ];
    }
}
