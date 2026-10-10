<?php

namespace App\Domains\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryObjectUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_object_id' => [
                'required',
                'exists:inventory_objects,id',
            ],

            'unit_id' => [
                'required',
                'exists:units,id',
            ],

            'conversion_factor' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ];
    }
}