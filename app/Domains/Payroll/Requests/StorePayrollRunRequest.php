<?php

namespace App\Domains\Payroll\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
        // Later:
        // return $this->user()?->can('create', PayrollRun::class) ?? false;
    }

    public function rules(): array
    {
        return [

            'payroll_no' => [
                'required',
                'string',
                'max:50',
                'unique:payroll_runs,payroll_no',
            ],

            'period_from' => [
                'required',
                'date',
            ],

            'period_to' => [
                'required',
                'date',
                'after_or_equal:period_from',
            ],

            'pay_date' => [
                'required',
                'date',
                'after_or_equal:period_to',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

        ];
    }
}