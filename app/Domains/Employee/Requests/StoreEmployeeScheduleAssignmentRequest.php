<?php

namespace App\Domains\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeScheduleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'employee_id' => [
                'required',
                'exists:employees,id'
            ],

            'schedule_id' => [
                'required',
                'exists:schedules,id'
            ],

            'effective_from' => [
                'required',
                'date'
            ],

            'remarks' => [
                'nullable',
                'string'
            ]

        ];
    }
}