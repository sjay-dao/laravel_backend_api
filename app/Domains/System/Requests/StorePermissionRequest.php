<?php

namespace App\Domains\System\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module' => ['required', 'string', 'max:100'],
            'resource' => ['required', 'string', 'max:100'],
            'action' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:150', 'unique:permissions,code'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}