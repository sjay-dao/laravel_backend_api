<?php

namespace App\Domains\Reference\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reference.branches.update') ?? false;
    }

    public function rules(): array
    {
        $branch = $this->route('branch');

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('branches', 'code')->ignore($branch),
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