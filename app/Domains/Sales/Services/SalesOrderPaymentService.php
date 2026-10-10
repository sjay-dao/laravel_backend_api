<?php

namespace App\Domains\Sales\Services;

use App\Domains\Sales\Models\SalesOrder;
use App\Domains\Sales\Models\SalesOrderPayment;
use App\Domains\Sales\Repositories\SalesOrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SalesOrderPaymentService
{
    public function record(SalesOrder $salesOrder, array $data, int $actorId): SalesOrder
    {
        return DB::transaction(function () use ($salesOrder, $data, $actorId) {
            $order = SalesOrder::query()->whereKey($salesOrder->id)->lockForUpdate()->firstOrFail();
            $status = (string) DB::table('lookups')->where('id', $order->status_id)->value('code');
            if ($status !== 'CONFIRMED') {
                $this->invalid('sales_order', 'Payments may only be recorded for confirmed sales orders.');
            }

            $total = (int) round(((float) $order->total_amount) * 100);
            $paid = (int) SalesOrderPayment::query()->where('sales_order_id', $order->id)->lockForUpdate()->sum('applied_amount_cents');
            $remaining = max(0, $total - $paid);
            if ($remaining === 0) {
                $this->invalid('sales_order', 'This sales order is already fully paid.');
            }

            $method = $data['method'];
            $tendered = (int) $data['tendered_amount_cents'];
            if (in_array($method, ['GCASH', 'BANK_TRANSFER'], true) && empty(trim((string) ($data['reference'] ?? '')))) {
                $this->invalid('reference', 'A payment reference is required for GCash and bank transfers.');
            }
            if ($method !== 'CASH' && $tendered > $remaining) {
                $this->invalid('tendered_amount_cents', 'Non-cash payment cannot exceed the remaining balance.');
            }
            $applied = min($tendered, $remaining);
            $change = $method === 'CASH' ? $tendered - $applied : 0;

            SalesOrderPayment::query()->create([
                'sales_order_id' => $order->id,
                'payment_no' => (string) Str::uuid(),
                'method' => $method,
                'tendered_amount_cents' => $tendered,
                'applied_amount_cents' => $applied,
                'change_cents' => $change,
                'reference' => $data['reference'] ?? null,
                'received_at' => $data['received_at'] ?? now(),
                'received_by' => $actorId,
            ]);

            return app(SalesOrderRepository::class)->find($order->id);
        }, 3);
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
