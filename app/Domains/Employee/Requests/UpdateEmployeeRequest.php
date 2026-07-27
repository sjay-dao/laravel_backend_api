<?php

namespace App\Domains\Employee\Requests;

use App\Domains\Employee\Models\Employee;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends StoreEmployeeRequest
{
    public function authorize(): bool { return $this->user()?->can('update', $this->route('employee')) ?? false; }
    public function rules(): array
    {
        $rules = parent::rules();
        $employee = $this->route('employee');
        $rules['employee_no'] = ['required', 'string', 'max:50', Rule::unique('employees', 'employee_no')->ignore($employee)];
        $rules['email'] = ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee)];
        return $rules;
    }
}
