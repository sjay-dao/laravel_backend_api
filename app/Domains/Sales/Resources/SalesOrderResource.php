<?php
namespace App\Domains\Sales\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'code' => $this->customer->code,
                'name' => $this->customer->name,
            ] : null,
            'branch' => $this->branch ? [
                'id' => $this->branch->id,
                'code' => $this->branch->code,
                'name' => $this->branch->name,
            ] : null,
            'status' => $this->status ? [
                'id' => $this->status->id,
                'code' => $this->status->code,
                'name' => $this->status->name,
            ] : null,
            'order_date' => $this->order_date?->format('Y-m-d'),
            'requested_delivery_date' => $this->requested_delivery_date?->format('Y-m-d'),
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'remarks' => $this->remarks,
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'inventory' => [
                    'id' => $item->inventory->id,
                    'code' => $item->inventory->code,
                    'name' => $item->inventory->name,
                ],
                'unit' => [
                    'id' => $item->unit->id,
                    'name' => $item->unit->name,
                ],
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_amount' => $item->discount_amount,
                'tax_amount' => $item->tax_amount,
                'line_total' => $item->line_total,
                'remarks' => $item->remarks,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}