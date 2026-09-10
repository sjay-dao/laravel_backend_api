<?php

namespace App\Domains\Evidence\Requests;

use App\Domains\Evidence\Services\EvidenceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('evidence.supplier_observations.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'inventory_object_unit_id' => ['required', 'integer', 'exists:inventory_object_units,id'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'minimum_order_quantity' => ['nullable', 'numeric', 'min:0'],
            'available_quantity' => ['nullable', 'numeric', 'min:0'],
            'product_specification' => ['nullable', 'string'],
            'freight_amount' => ['nullable', 'numeric', 'min:0'],
            'freight_terms' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:150'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_description' => ['nullable', 'string'],
            'supplier_location' => ['nullable', 'string', 'max:255'],
            'collection_method' => ['required', 'string', 'max:50'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'observed_at' => ['required', 'date'],
            'epistemic_status' => ['nullable', 'string', Rule::in(EvidenceService::EPISTEMIC_STATUSES)],
            'raw_payload' => ['nullable', 'array'],
            'context_payload' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
