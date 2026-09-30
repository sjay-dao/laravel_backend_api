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
            'retail_price_cents' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'wholesale_price_tiers' => ['sometimes', 'array'],
            'wholesale_price_tiers.*.min_quantity' => ['required', 'numeric', 'gt:0'],
            'wholesale_price_tiers.*.max_quantity' => ['nullable', 'numeric', 'gt:0'],
            'wholesale_price_tiers.*.unit_price_cents' => ['required', 'integer', 'min:0'],
        ];
    }
}
