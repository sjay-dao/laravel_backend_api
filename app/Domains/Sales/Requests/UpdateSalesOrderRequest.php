<?php

namespace App\Domains\Sales\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'order_date' => ['required', 'date'],
            'requested_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'remarks' => ['nullable', 'string'],
            'sale_type' => ['sometimes', Rule::in(['RETAIL', 'WHOLESALE'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_id' => ['required', 'integer', 'exists:inventory_objects,id'],
            'items.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'gte:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.discount_cents' => ['nullable', 'integer', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }
}
