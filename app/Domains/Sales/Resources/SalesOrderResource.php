<?php

namespace App\Domains\Sales\Resources;

use App\Domains\Sales\Services\SalesProfitabilityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payments = $this->relationLoaded('payments') ? $this->payments : collect();
        $totalCents = (int) round(((float) $this->total_amount) * 100);
        $paidCents = (int) $payments->sum('applied_amount_cents');
        $balanceCents = max(0, $totalCents - $paidCents);

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
            'sale_type' => $this->sale_type,
            'order_date' => $this->order_date?->format('Y-m-d'),
            'requested_delivery_date' => $this->requested_delivery_date?->format('Y-m-d'),
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'profitability' => app(SalesProfitabilityService::class)->forOrder($this->resource),
            'remarks' => $this->remarks,
            'payment_summary' => [
                'status' => $paidCents === 0 ? 'UNPAID' : ($balanceCents > 0 ? 'PARTIALLY_PAID' : 'PAID'),
                'paid_cents' => $paidCents,
                'balance_cents' => $balanceCents,
            ],
            'payments' => $payments->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_no' => $payment->payment_no,
                'method' => $payment->method,
                'tendered_amount_cents' => $payment->tendered_amount_cents,
                'applied_amount_cents' => $payment->applied_amount_cents,
                'change_cents' => $payment->change_cents,
                'reference' => $payment->reference,
                'received_at' => $payment->received_at?->toISOString(),
                'received_by' => $payment->receivedBy ? ['id' => $payment->receivedBy->id, 'name' => $payment->receivedBy->name] : null,
            ]),
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
                'sale_type' => $item->sale_type ?? $this->sale_type,
                'list_unit_price_cents' => $item->list_unit_price_cents,
                'discount_cents' => $item->discount_cents,
                'final_unit_price_cents' => $item->final_unit_price_cents,
                'line_total_cents' => $item->line_total_cents,
                'unit_price' => $item->unit_price,
                'discount_amount' => $item->discount_amount,
                'tax_amount' => $item->tax_amount,
                'line_total' => $item->line_total,
                'remarks' => $item->remarks,
                'lot_allocations' => $item->relationLoaded('lotAllocations')
                    ? $item->lotAllocations->map(fn ($allocation) => [
                        'id' => $allocation->id,
                        'quantity' => $allocation->quantity,
                        'ownership' => $allocation->ownership_snapshot,
                        'unit_cost_basis_cents' => $allocation->settlement_cost_cents,
                        'total_cost_basis_cents' => (int) round(((float) $allocation->quantity) * $allocation->settlement_cost_cents),
                        'settlement_cost_cents' => $allocation->settlement_cost_cents,
                        'lot' => [
                            'id' => $allocation->inventoryLot->id,
                            'ownership' => $allocation->inventoryLot->ownership,
                            'supplier' => $allocation->inventoryLot->supplier ? [
                                'id' => $allocation->inventoryLot->supplier->id,
                                'code' => $allocation->inventoryLot->supplier->code,
                                'name' => $allocation->inventoryLot->supplier->name,
                            ] : null,
                            'warehouse' => $allocation->inventoryLot->warehouse ? [
                                'id' => $allocation->inventoryLot->warehouse->id,
                                'code' => $allocation->inventoryLot->warehouse->code,
                                'name' => $allocation->inventoryLot->warehouse->name,
                            ] : null,
                        ],
                    ])
                    : [],
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
