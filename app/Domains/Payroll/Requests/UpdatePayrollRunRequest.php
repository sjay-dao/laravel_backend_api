<?php

namespace App\Domains\Payroll\Requests;

use Illuminate\Validation\Rule;

class UpdatePayrollRunRequest extends StorePayrollRunRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $payrollRun = $this->route('payrollRun');

        $rules['payroll_no'] = [
            'required',
            'string',
            'max:50',
            Rule::unique('payroll_runs', 'payroll_no')
                ->ignore($payrollRun),
        ];

        return $rules;
    }
}