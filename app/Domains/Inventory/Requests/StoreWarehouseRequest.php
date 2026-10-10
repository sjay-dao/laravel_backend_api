<?php

namespace App\Domains\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
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
                Rule::unique('warehouses', 'code'),
            ],
            
            'branch_id' => [
                'required',
                'int',
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'address' => [
                'nullable',
                'string',
            ],

             'description' => [
                'nullable',
                'string',
            ],

            'active' => [
                'boolean'
            ],


        ];
    }

}