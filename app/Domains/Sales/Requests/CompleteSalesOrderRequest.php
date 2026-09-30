<?php

namespace App\Domains\Sales\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.sales_order_item_id' => ['required', 'integer', 'exists:sales_order_items,id'],
            'allocations.*.inventory_lot_id' => ['required', 'integer', 'exists:inventory_lots,id'],
            'allocations.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
