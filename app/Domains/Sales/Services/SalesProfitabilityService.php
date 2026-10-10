<?php

namespace App\Domains\Sales\Services;

use App\Domains\Sales\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class SalesProfitabilityService
{
    public function forOrder(SalesOrder $order): ?array
    {
        $statusCode = $order->relationLoaded('status')
            ? $order->status?->code
            : DB::table('lookups')->where('id', $order->status_id)->value('code');

        if ($statusCode !== 'CLOSED') {
            return null;
        }

        $revenueCents = (int) round(((float) $order->total_amount) * 100);
        $cogsCents = (int) $this->cogsQuery()
            ->where('so.id', $order->id)
            ->sum(DB::raw('ROUND(sola.quantity * sola.settlement_cost_cents, 0)'));

        return $this->gross($revenueCents, $cogsCents);
    }

    public function forPeriod(string $from, string $to, int $operatingExpensesCents): array
    {
        $closedId = $this->statusId('CLOSED');
        $orders = DB::table('sales_orders')
            ->whereNull('deleted_at')
            ->where('status_id', $closedId)
            ->whereBetween('order_date', [$from, $to]);

        $revenueCents = (int) round(((float) (clone $orders)->sum('total_amount')) * 100);
        $cogsCents = (int) $this->cogsQuery()
            ->where('so.status_id', $closedId)
            ->whereNull('so.deleted_at')
            ->whereBetween('so.order_date', [$from, $to])
            ->sum(DB::raw('ROUND(sola.quantity * sola.settlement_cost_cents, 0)'));
        $gross = $this->gross($revenueCents, $cogsCents);

        return [
            ...$gross,
            'operating_expenses_cents' => $operatingExpensesCents,
            'operating_profit_cents' => $gross['gross_profit_cents'] - $operatingExpensesCents,
        ];
    }

    private function gross(int $revenueCents, int $cogsCents): array
    {
        $grossProfitCents = $revenueCents - $cogsCents;

        return [
            'revenue_cents' => $revenueCents,
            'cogs_cents' => $cogsCents,
            'gross_profit_cents' => $grossProfitCents,
            'gross_margin_percent' => $revenueCents === 0 ? null : round(($grossProfitCents / $revenueCents) * 100, 2),
            'markup_percent' => $cogsCents === 0 ? null : round(($grossProfitCents / $cogsCents) * 100, 2),
        ];
    }

    private function cogsQuery()
    {
        return DB::table('sales_order_lot_allocations as sola')
            ->join('sales_order_items as soi', 'soi.id', '=', 'sola.sales_order_item_id')
            ->join('sales_orders as so', 'so.id', '=', 'soi.sales_order_id');
    }

    private function statusId(string $code): int
    {
        return (int) DB::table('lookups')->where('code', $code)
            ->whereIn('lookup_type_id', DB::table('lookup_types')->select('id')->where('code', 'SALES_ORDER_STATUS'))
            ->value('id');
    }
}
