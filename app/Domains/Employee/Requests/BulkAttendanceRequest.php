<?php

namespace App\Domains\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkAttendanceRequest extends FormRequest
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
                'exists:employees,id',
            ],

            'dates' => [
                'required',
                'array',
                'min:1',
            ],

            'dates.*' => [
                'required',
                'date',
            ],

            'status' => [
                'required',
                Rule::in([
                    'PRESENT',
                    'ABSENT',
                    'LEAVE',
                    'HOLIDAY',
                    'REST_DAY',
                    'HALF_DAY',
                ]),
            ],
        ];
    }
}