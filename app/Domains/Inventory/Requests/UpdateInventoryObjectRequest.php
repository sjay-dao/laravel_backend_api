<?php

namespace App\Domains\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryObjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'nullable',
                Rule::unique('inventory_objects', 'code')
                    ->ignore($this->route('inventory_object')),
            ],
            'brand' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'variant' => [
                'sometimes',
                'nullable',
                'string',
                'max:150',
            ],
            'packaging_description' => [
                'sometimes',
                'nullable',
                'string',
                'max:150',
            ],
            'specification' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ];
    }
}










