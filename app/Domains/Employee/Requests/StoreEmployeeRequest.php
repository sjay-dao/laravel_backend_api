<?php

namespace App\Domains\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('create', \App\Domains\Employee\Models\Employee::class) ?? false; }
    protected function prepareForValidation(): void
    {
        $this->merge(array_filter(['tin' => data_get($this->input('government_ids'), 'tin'), 'sss_number' => data_get($this->input('government_ids'), 'sss_number'), 'philhealth_number' => data_get($this->input('government_ids'), 'philhealth_number'), 'pagibig_number' => data_get($this->input('government_ids'), 'pagibig_number'), 'bank_name' => data_get($this->input('bank_information'), 'bank_name'), 'bank_account_name' => data_get($this->input('bank_information'), 'account_name'), 'bank_account_number' => data_get($this->input('bank_information'), 'account_number')], fn ($value) => $value !== null));
    }
    public function rules(): array
    {
        return ['employee_no' => ['required', 'string', 'max:50', 'unique:employees,employee_no'], 'first_name' => ['required', 'string', 'max:255'], 'middle_name' => ['nullable', 'string', 'max:255'], 'last_name' => ['required', 'string', 'max:255'], 'suffix' => ['nullable', 'string', 'max:30'], 'birth_date' => ['nullable', 'date', 'before:today'], 'gender' => ['nullable', Rule::in(['male', 'female', 'non_binary', 'prefer_not_to_say'])], 'email' => ['nullable', 'email', 'max:255', 'unique:employees,email'], 'mobile_number' => ['nullable', 'string', 'max:50'], 'phone_number' => ['nullable', 'string', 'max:50'], 'address' => ['nullable', 'string'], 'branch_id' => ['nullable', 'exists:branches,id'], 'department_id' => ['nullable', 'exists:employee_departments,id'], 'position_id' => ['nullable', 'exists:employee_positions,id'], 
        'employment_status' => [
            'required',
            'exists:lookups,id',
        ],
        'hire_date' => ['nullable', 'date'], 'regularization_date' => ['nullable', 'date', 'after_or_equal:hire_date'], 'separation_date' => ['nullable', 'date', 'after_or_equal:hire_date'], 'tin' => ['nullable', 'string', 'max:50'], 'sss_number' => ['nullable', 'string', 'max:50'], 'philhealth_number' => ['nullable', 'string', 'max:50'], 'pagibig_number' => ['nullable', 'string', 'max:50'], 'bank_name' => ['nullable', 'string', 'max:255'], 'bank_account_name' => ['nullable', 'string', 'max:255'], 'bank_account_number' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string'], 'emergency_contacts' => ['sometimes', 'array', 'max:5'], 'emergency_contacts.*.name' => ['required_with:emergency_contacts', 'string', 'max:255'], 'emergency_contacts.*.relationship' => ['nullable', 'string', 'max:100'], 'emergency_contacts.*.mobile_number' => ['required_with:emergency_contacts', 'string', 'max:50'], 'emergency_contacts.*.phone_number' => ['nullable', 'string', 'max:50'], 'emergency_contacts.*.address' => ['nullable', 'string'], 'emergency_contacts.*.is_primary' => ['boolean']];
    }
}
