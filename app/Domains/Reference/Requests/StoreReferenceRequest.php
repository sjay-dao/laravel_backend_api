<?php

namespace App\Domains\Reference\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Domains\Reference\Services\ReferenceManager;

class StoreReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [

           'type' => [
                'required',
                'string',
                'exists:lookup_types,code',
            ],

            'code' => [
                'required',
                'string',
                'max:100',
                'unique:lookups,code,NULL,id,lookup_type_id,' . $this->lookup_type_id,
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'value' => [
                'nullable',
            ],

            'color' => [
                'nullable',
                'string',
                'max:50',
            ],

            'icon' => [
                'nullable',
                'string',
                'max:100',
            ],

            'metadata' => [
                'nullable',
                'array',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'boolean',
            ],

        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim($this->code))
            ]);
        }

        if ($this->filled('type')) {

            $manager = app(
                ReferenceManager::class
            );

            $type = $manager
                ->lookupType(
                    strtoupper($this->type)
                );

            if ($type->exists()) {

                $this->merge([
                    'lookup_type_id' => $type->id(),
                    'type' => strtoupper($this->type)
                ]);

            }

        }
    }
}