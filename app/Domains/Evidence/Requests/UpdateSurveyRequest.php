<?php

namespace App\Domains\Evidence\Requests;

use Illuminate\Validation\Rule;

class UpdateSurveyRequest extends StoreSurveyRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('evidence.surveys.update') ?? false;
    }

    public function rules(): array
    {
        $rules = $this->structureRules(false);
        $survey = $this->route('survey');

        $rules['code'] = [
            'sometimes',
            'string',
            'max:50',
            Rule::unique('surveys', 'code')->ignore($survey),
        ];

        return $rules;
    }
}
