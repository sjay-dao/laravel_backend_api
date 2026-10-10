<?php

namespace App\Domains\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Domains\Employee\Enums\PayBasis;

class UpdateSalaryContractRequest extends FormRequest
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

            'pay_basis' => [
                'required',
                Rule::in(PayBasis::values()),
            ],

            'salary_rate' => [
                'required',
                'numeric',
                'min:0',
            ],

            'effective_from' => [
                'required',
                'date',
            ],

            'effective_to' => [
                'nullable',
                'date',
                'after_or_equal:effective_from',
            ],

            'is_active' => [
                'boolean',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],
        ];
    }
}