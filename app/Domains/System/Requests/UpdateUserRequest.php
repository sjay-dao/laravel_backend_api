<?php

namespace App\Domains\System\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('system.users.update') ?? false;
    }
    
    public function rules(): array
    {
        return [

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                Rule::unique('users')
                    ->ignore($this->route('user')),
            ],

            'password' => [
                'nullable',
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