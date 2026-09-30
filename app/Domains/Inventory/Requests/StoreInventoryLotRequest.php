<?php

namespace App\Domains\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryLotRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'inventory_object_id' => ['required', 'integer', 'exists:inventory_objects,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'ownership' => ['required', Rule::in(['OWNED', 'CONSIGNMENT'])],
            'quantity_received' => ['required', 'numeric', 'gt:0'],
            'settlement_cost_cents' => ['required', 'integer', 'min:0'],
            'received_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
