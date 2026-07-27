<?php

namespace App\Domains\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalarySetupRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('update', $this->route('employee')) ?? false; }
    public function rules(): array { return ['basic_rate' => ['required', 'numeric', 'gt:0'], 'rate_type' => ['required', Rule::in(['monthly', 'daily', 'hourly'])], 'pay_frequency' => ['required', Rule::in(['weekly', 'biweekly', 'semi_monthly', 'monthly'])], 'currency' => ['required', 'string', 'size:3'], 'allowance_amount' => ['nullable', 'numeric', 'min:0'], 'effective_from' => ['required', 'date'], 'effective_to' => ['nullable', 'date', 'after:effective_from'], 'remarks' => ['nullable', 'string']]; }
}
