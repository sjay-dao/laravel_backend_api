<?php

namespace App\Domains\Payroll\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayrollPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'year' => ['required', 'integer'],
            'month' => ['required', 'integer', 'between:1,12'],
        ];
    }
}