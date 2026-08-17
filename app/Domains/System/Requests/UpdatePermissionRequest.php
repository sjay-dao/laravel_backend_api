<?php

namespace App\Domains\System\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $permission = $this->route('permission');

        return [
            'module' => ['required', 'string', 'max:100'],
            'resource' => ['required', 'string', 'max:100'],
            'action' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:150',
                Rule::unique('permissions', 'code')->ignore($permission),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}