<?php

namespace App\Domains\Dashboard\Services;

use Illuminate\Support\Facades\DB;

class BusinessOverviewService
{
    public function get(): array
    {
        $closedId = $this->statusId('CLOSED');
        $confirmedId = $this->statusId('CONFIRMED');
        $closed = DB::table('sales_orders')->whereNull('deleted_at')->where('status_id', $closedId);
        $payments = DB::table('sales_order_payments')->selectRaw('sales_order_id, SUM(applied_amount_cents) as paid_cents')->groupBy('sales_order_id');
        $openBalances = DB::table('sales_orders as so')->leftJoinSub($payments, 'p', 'p.sales_order_id', '=', 'so.id')
            ->whereNull('so.deleted_at')->where('so.status_id', $confirmedId);

        return [
            'sales' => [
                'today_cents' => (int) round(((float) (clone $closed)->whereDate('order_date', now()->toDateString())->sum('total_amount')) * 100),
                'month_cents' => (int) round(((float) (clone $closed)->whereBetween('order_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('total_amount')) * 100),
                'order_count' => (int) DB::table('sales_orders')->whereNull('deleted_at')->count(),
                'closed_count' => (int) (clone $closed)->count(),
                'unpaid_count' => (int) (clone $openBalances)->whereRaw('COALESCE(p.paid_cents, 0) = 0')->count(),
                'partially_paid_count' => (int) (clone $openBalances)->whereRaw('COALESCE(p.paid_cents, 0) > 0 AND COALESCE(p.paid_cents, 0) < ROUND(so.total_amount * 100)')->count(),
            ],
            'inventory' => [
                'product_count' => (int) DB::table('inventory_objects')->where('is_active', true)->count(),
                'available_quantity' => (float) DB::table('inventory_lots')->sum('quantity_available'),
                'owned_quantity' => (float) DB::table('inventory_lots')->where('ownership', 'OWNED')->sum('quantity_available'),
                'consignment_quantity' => (float) DB::table('inventory_lots')->where('ownership', 'CONSIGNMENT')->sum('quantity_available'),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    private function statusId(string $code): int
    {
        return (int) DB::table('lookups')->where('code', $code)
            ->whereIn('lookup_type_id', DB::table('lookup_types')->select('id')->where('code', 'SALES_ORDER_STATUS'))
            ->value('id');
    }
}
