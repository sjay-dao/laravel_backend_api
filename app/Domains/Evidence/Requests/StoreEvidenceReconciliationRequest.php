<?php

namespace App\Domains\Evidence\Requests;

use App\Domains\Evidence\Models\EvidenceReconciliation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvidenceReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('evidence.reconciliations.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'relationship_type' => [
                'required',
                'string',
                Rule::in(EvidenceReconciliation::RELATIONSHIP_TYPES),
            ],
            'inventory_object_id' => ['nullable', 'integer', 'exists:inventory_objects,id'],
            'inventory_object_unit_id' => ['nullable', 'integer', 'exists:inventory_object_units,id'],
            'notes' => ['required', 'string'],
            'rationale' => ['nullable', 'string'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
