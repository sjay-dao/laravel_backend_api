<?php

namespace App\Domains\Evidence\Requests;

use App\Domains\Evidence\Services\EvidenceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarketObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('evidence.market_observations.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'inventory_object_unit_id' => ['required', 'integer', 'exists:inventory_object_units,id'],
            'store_name' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'string', Rule::in(['retail', 'wholesale'])],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'package_size' => ['nullable', 'string', 'max:100'],
            'availability_status' => ['nullable', 'string', 'max:50'],
            'promotion' => ['nullable', 'string'],
            'location_name' => ['nullable', 'string', 'max:255'],
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
