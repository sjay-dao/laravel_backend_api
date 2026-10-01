<?php

namespace App\Domains\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryObjectRequest extends FormRequest
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
                'string',
                'max:50',
                'unique:inventory_objects,code',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'brand' => [
                'nullable',
                'string',
                'max:100',
            ],

            'variant' => [
                'nullable',
                'string',
                'max:150',
            ],

            'packaging_description' => [
                'nullable',
                'string',
                'max:150',
            ],

            'specification' => [
                'nullable',
                'string',
            ],

            'inventory_category_id' => [
                'nullable',
                'exists:inventory_categories,id',
            ],

            'unit_id' => [
                'required',
                'exists:units,id',
            ],

            'track_inventory' => [
                'boolean',
            ],

            'is_sellable' => [
                'boolean',
            ],

            'is_active' => [
                'boolean',
            ],
            'retail_price_cents' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price_tiers' => ['nullable', 'array'],
            'wholesale_price_tiers.*.min_quantity' => ['required', 'numeric', 'gt:0'],
            'wholesale_price_tiers.*.max_quantity' => ['nullable', 'numeric', 'gt:0'],
            'wholesale_price_tiers.*.unit_price_cents' => ['required', 'integer', 'min:0'],

        ];
    }
}
