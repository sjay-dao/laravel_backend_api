<?php

namespace App\Domains\Reference\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('system.branches.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:30',
                'unique:branches,code',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'barangay_id' => [
                'nullable',
                'integer',
                'exists:psgc_barangay,id',
            ],

            'branch_type' => [
                'nullable',
                'string',
                'max:50',
            ],

            'branch_category' => [
                'nullable',
                'string',
                'max:50',
            ],

            'service_bay_count' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'boolean',
            ],
        ];
    }
}