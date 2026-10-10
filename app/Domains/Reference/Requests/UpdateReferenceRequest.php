<?php

namespace App\Domains\Reference\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Domains\Reference\Services\ReferenceManager;

class UpdateReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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

    public function rules(): array
    {
        $reference = $this->route('reference');

        return [

           'type' => [
                'sometimes',
                'string',
                'exists:lookup_types,code',
            ],

            'code' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('lookups')
                ->where(fn($q) =>

                    $q->where(
                        'lookup_type_id',
                        $this->lookup_type_id
                    )

                )
            ],

            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'description' => [
                'sometimes',
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
                'sometimes',
                'boolean',
            ],

        ];
    }
}