<?php

namespace App\Domains\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeScheduleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'schedule_id' => [
                'required',
                'exists:schedules,id'
            ],

            'effective_from' => [
                'required',
                'date'
            ],

            'effective_to' => [
                'nullable',
                'date'
            ],

            'remarks' => [
                'nullable',
                'string'
            ]

        ];
    }
}