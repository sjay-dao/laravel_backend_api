<?php

namespace App\Domains\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Header
            |--------------------------------------------------------------------------
            */

            'movement_type_id' => [
                'required',
                'exists:inventory_movement_types,id',
            ],

            'movement_date' => [
                'required',
                'date',
            ],

            'branch_id' => [
                'nullable',
                'exists:branches,id',
            ],

            'warehouse_id' => [
                'nullable',
                'exists:warehouses,id',
            ],

            'reference_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'reference_id' => [
                'nullable',
                'integer',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.inventory_object_id' => [
                'required',
                'exists:inventory_objects,id',
            ],

            'items.*.unit_id' => [
                'required',
                'exists:units,id',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'items.*.remarks' => [
                'nullable',
                'string',
            ],

        ];
    }
}