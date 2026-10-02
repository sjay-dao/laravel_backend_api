<?php

namespace Database\Seeders;

use App\Domains\Finance\Services\ExpenseService;
use App\Domains\Sales\Services\SalesOrderPaymentService;
use App\Domains\Sales\Services\SalesOrderService;
use App\Support\DemoDatabaseGuard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MicaDemoTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        app(DemoDatabaseGuard::class)->assertSafe();
        $branchId = (int) DB::table('branches')->where('code', 'MICA-DEMO')->value('id');
        $warehouseId = (int) DB::table('warehouses')->where('code', 'MICA-DEMO-STOCK')->value('id');
        $actorId = (int) DB::table('users')->where('email', 'cashier@mica.demo')->value('id');
        $unitId = (int) DB::table('units')->where('code', 'pc')->value('id');
        foreach (range(1, 4) as $index) {
            DB::table('customers')->updateOrInsert(['code' => $index === 1 ? 'WALK-IN' : 'MICA-DEMO-CUSTOMER-'.$index], ['name' => $index === 1 ? 'Walk-in Customer' : 'Fictional Demo Customer '.$index, 'remarks' => 'SYNTHETIC DEMO ONLY', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        $customerId = (int) DB::table('customers')->where('code', 'WALK-IN')->value('id');
        $anchor = Carbon::parse(config('demo.date').' 12:00:00', config('app.timezone'));
        $scenarios = [
            ['DEMO-EB-CITY-E1', 1, 'RETAIL', 'CLOSED', $anchor, [['CASH', 4000000]], [[1, 1]], 0],
            ['DEMO-EB-URBAN-X2', 3, 'WHOLESALE', 'CLOSED', $anchor, [['CASH', 5000000], ['GCASH', 7750000]], [[2, 1], [3, 2]], 0],
            ['DEMO-BAT-48-20', 1, 'RETAIL', 'CLOSED', $anchor->copy()->startOfMonth()->addHours(12), [['BANK_TRANSFER', 1850000]], [[6, 1]], 0],
            ['DEMO-EB-CARGO-C3', 1, 'RETAIL', 'CLOSED', $anchor->copy()->subMonthNoOverflow(), [['CASH', 5800000]], [[5, 1]], 0],
            ['DEMO-ACC-PHONE', 2, 'RETAIL', 'CONFIRMED', $anchor, [['CASH', 30000]], [], 0],
            ['DEMO-ACC-RAIN', 1, 'RETAIL', 'CONFIRMED', $anchor, [], [], 0],
            ['DEMO-SP-CTRL48', 1, 'RETAIL', 'DRAFT', $anchor, [], [], 20000],
            ['DEMO-SP-THROTTLE', 1, 'RETAIL', 'CANCELLED', $anchor, [], [], 0],
        ];
        foreach ($scenarios as $index => [$code, $quantity, $type, $status, $date, $payments, $sources, $discount]) {
            $marker = 'MICA-DEMO-SALE-'.($index + 1);
            if (DB::table('sales_orders')->where('remarks', $marker)->exists()) {
                continue;
            }
            Carbon::withTestNow($date, function () use ($code, $quantity, $type, $status, $date, $payments, $sources, $discount, $marker, $branchId, $warehouseId, $actorId, $unitId, $customerId) {
                $service = app(SalesOrderService::class);
                $order = $service->create(['customer_id' => $customerId, 'branch_id' => $branchId, 'order_date' => $date->toDateString(), 'sale_type' => $type, 'remarks' => $marker, 'items' => [
                    ['inventory_id' => (int) DB::table('inventory_objects')->where('code', $code)->value('id'), 'unit_id' => $unitId, 'quantity' => $quantity, 'discount_cents' => $discount],
                ]]);
                if ($status === 'DRAFT') {
                    return;
                }
                if ($status === 'CANCELLED') {
                    $service->cancel($order);

                    return;
                }
                $order = $service->confirm($order);
                foreach ($payments as $paymentIndex => [$method, $amount]) {
                    $order = app(SalesOrderPaymentService::class)->record($order, ['method' => $method, 'tendered_amount_cents' => $amount, 'reference' => $method === 'CASH' ? null : $marker.'-REF-'.($paymentIndex + 1)], $actorId);
                }
                if ($status === 'CLOSED') {
                    $allocations = [];
                    foreach ($sources as [$lotNumber, $amount]) {
                        $allocations[] = ['sales_order_item_id' => $order->items->first()->id, 'inventory_lot_id' => (int) DB::table('inventory_lots')->where('notes', 'MICA-DEMO-LOT-'.str_pad((string) $lotNumber, 2, '0', STR_PAD_LEFT))->value('id'), 'quantity' => $amount];
                    }
                    $service->complete($order, ['warehouse_id' => $warehouseId, 'allocations' => $allocations], $actorId);
                }
            });
        }
        foreach ([['Rent', 1500000], ['Utilities', 350000], ['Transport', 120000], ['Supplies', 80000], ['Maintenance', 250000]] as $index => [$category, $amount]) {
            $reference = 'MICA-DEMO-EXPENSE-'.($index + 1);
            if (! DB::table('expenses')->where('reference', $reference)->exists()) {
                app(ExpenseService::class)->create(['expense_date' => $anchor->toDateString(), 'category' => $category, 'description' => 'Fictional demo '.$category, 'amount_cents' => $amount, 'reference' => $reference, 'notes' => 'SYNTHETIC DEMO ONLY'], $actorId);
            }
        }
        DB::table('inventory_objects')->where('code', 'DEMO-SP-BRAKE')->update(['low_stock_threshold' => 30]);
        DB::table('inventory_objects')->where('code', 'DEMO-SP-CTRL48')->update(['low_stock_threshold' => 2]);
        DB::table('inventory_objects')->where('code', 'DEMO-ACC-RAIN')->update(['low_stock_threshold' => 12]);
    }
}
