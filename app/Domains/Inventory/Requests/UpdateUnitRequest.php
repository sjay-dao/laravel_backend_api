<?php

namespace App\Domains\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unit = $this->route('unit');

        return [

            'code' => 'required|string|max:20|unique:units,code,' . $unit->id,

            'name' => 'required|string|max:100',

            'symbol' => 'required|string|max:20',

            'measurement_type' => [
                'required',
                'in:count,weight,volume,length,area,time'
            ],

            'is_base' => 'boolean',

        ];
    }
}