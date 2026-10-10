<?php

namespace App\Domains\System\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncPermissionRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_ids' => ['required', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ];
    }
}