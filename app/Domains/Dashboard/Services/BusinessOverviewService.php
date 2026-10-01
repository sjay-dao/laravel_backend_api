<?php

namespace App\Domains\Dashboard\Services;

use App\Domains\Sales\Services\SalesProfitabilityService;
use Illuminate\Support\Facades\DB;

class BusinessOverviewService
{
    public function __construct(private SalesProfitabilityService $profitability) {}

    public function get(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $closedId = $this->statusId('CLOSED');
        $confirmedId = $this->statusId('CONFIRMED');
        $closed = DB::table('sales_orders')->whereNull('deleted_at')->where('status_id', $closedId);
        $payments = DB::table('sales_order_payments')->selectRaw('sales_order_id, SUM(applied_amount_cents) as paid_cents')->groupBy('sales_order_id');
        $openBalances = DB::table('sales_orders as so')->leftJoinSub($payments, 'p', 'p.sales_order_id', '=', 'so.id')
            ->whereNull('so.deleted_at')->where('so.status_id', $confirmedId);
        $lotAvailability = DB::table('inventory_lots')->selectRaw('inventory_object_id, SUM(quantity_available) as available_quantity')->groupBy('inventory_object_id');

        $todayExpensesCents = (int) DB::table('expenses')->whereDate('expense_date', $today)->sum('amount_cents');
        $monthExpensesCents = (int) DB::table('expenses')->whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount_cents');

        return [
            'sales' => [
                'today_cents' => (int) round(((float) (clone $closed)->whereDate('order_date', $today)->sum('total_amount')) * 100),
                'month_cents' => (int) round(((float) (clone $closed)->whereBetween('order_date', [$monthStart, $monthEnd])->sum('total_amount')) * 100),
                'order_count' => (int) DB::table('sales_orders')->whereNull('deleted_at')->count(),
                'closed_count' => (int) (clone $closed)->count(),
                'unpaid_count' => (int) (clone $openBalances)->whereRaw('COALESCE(p.paid_cents, 0) = 0')->count(),
                'partially_paid_count' => (int) (clone $openBalances)->whereRaw('COALESCE(p.paid_cents, 0) > 0 AND COALESCE(p.paid_cents, 0) < ROUND(so.total_amount * 100)')->count(),
            ],
            'profitability' => [
                'today' => $this->profitability->forPeriod($today, $today, $todayExpensesCents),
                'month' => $this->profitability->forPeriod($monthStart, $monthEnd, $monthExpensesCents),
            ],
            'inventory' => [
                'product_count' => (int) DB::table('inventory_objects')->where('is_active', true)->count(),
                'available_quantity' => (float) DB::table('inventory_lots')->sum('quantity_available'),
                'owned_quantity' => (float) DB::table('inventory_lots')->where('ownership', 'OWNED')->sum('quantity_available'),
                'consignment_quantity' => (float) DB::table('inventory_lots')->where('ownership', 'CONSIGNMENT')->sum('quantity_available'),
                'low_stock_product_count' => (int) DB::table('inventory_objects as io')
                    ->leftJoinSub($lotAvailability, 'stock', 'stock.inventory_object_id', '=', 'io.id')
                    ->where('io.is_active', true)->whereNotNull('io.low_stock_threshold')
                    ->whereRaw('COALESCE(stock.available_quantity, 0) <= io.low_stock_threshold')->count(),
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
