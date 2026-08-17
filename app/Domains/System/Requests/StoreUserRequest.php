<?php

namespace App\Domains\System\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('system.users.create') ?? false;
    }
    
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'confirmed',
                'min:8',
            ],

            'role_ids' => [
                'nullable',
                'array',
            ],

            'role_ids.*' => [
                'integer',
                'exists:roles,id',
            ],
        ];
    }
}