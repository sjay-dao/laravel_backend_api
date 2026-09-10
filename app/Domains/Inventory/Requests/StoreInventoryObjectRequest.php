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

        ];
    }
}
