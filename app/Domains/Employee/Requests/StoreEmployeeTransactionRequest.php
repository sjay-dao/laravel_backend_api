<?php

namespace App\Domains\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeTransactionRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('manageTransactions', \App\Domains\Employee\Models\Employee::class) ?? false; }
    public function rules(): array { return ['employee_id' => ['required', 'exists:employees,id'], 'type' => ['required', Rule::in(['salary', 'advance', 'bonus', 'deduction', 'reimbursement', 'adjustment'])], 'transaction_date' => ['required', 'date'], 'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'], 'currency' => ['required', 'string', 'size:3'], 'reference_type' => ['nullable', 'string', 'max:100'], 'reference_no' => ['nullable', 'string', 'max:100'], 'remarks' => ['nullable', 'string']]; }
}
