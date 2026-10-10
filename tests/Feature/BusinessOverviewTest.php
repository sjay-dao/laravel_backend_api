<?php

namespace Tests\Feature;

use App\Domains\Dashboard\Services\BusinessOverviewService;
use App\Domains\Inventory\Services\InventoryLotService;
use Illuminate\Support\Facades\DB;

class BusinessOverviewTest extends SalesOrderPaymentTest
{
    public function test_overview_uses_real_sales_payment_and_inventory_records(): void
    {
        $now = now();
        $closedId = DB::table('sales_orders')->insertGetId([
            'order_no' => 'SO-DASH-CLOSED', 'branch_id' => 1, 'order_date' => $now->toDateString(), 'status_id' => 3,
            'subtotal' => 100, 'discount_amount' => 0, 'tax_amount' => 0, 'total_amount' => 100,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $partialId = DB::table('sales_orders')->insertGetId([
            'order_no' => 'SO-DASH-PARTIAL', 'branch_id' => 1, 'order_date' => $now->toDateString(), 'status_id' => 2,
            'subtotal' => 200, 'discount_amount' => 0, 'tax_amount' => 0, 'total_amount' => 200,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('sales_order_payments')->insert([
            'sales_order_id' => $partialId, 'payment_no' => fake()->uuid(), 'method' => 'CASH',
            'tendered_amount_cents' => 5000, 'applied_amount_cents' => 5000, 'change_cents' => 0,
            'received_at' => $now, 'received_by' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $ownedLot = app(InventoryLotService::class)->receive(['inventory_object_id' => 1, 'warehouse_id' => 1, 'ownership' => 'OWNED', 'quantity_received' => 4, 'settlement_cost_cents' => 4000, 'received_date' => $now->toDateString()], 1);
        app(InventoryLotService::class)->receive(['inventory_object_id' => 1, 'warehouse_id' => 1, 'supplier_id' => 1, 'ownership' => 'CONSIGNMENT', 'quantity_received' => 3, 'settlement_cost_cents' => 1, 'received_date' => $now->toDateString()], 1);
        DB::table('expenses')->insert([
            ['expense_date' => $now->toDateString(), 'category' => 'Utilities', 'description' => 'Electricity', 'amount_cents' => 2500, 'created_by' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['expense_date' => $now->copy()->subMonthNoOverflow()->toDateString(), 'category' => 'Other', 'description' => 'Previous month', 'amount_cents' => 1000, 'created_by' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $itemId = DB::table('sales_order_items')->insertGetId([
            'sales_order_id' => $closedId, 'inventory_id' => 1, 'unit_id' => 1, 'quantity' => 1,
            'list_unit_price_cents' => 10000, 'discount_cents' => 0, 'final_unit_price_cents' => 10000, 'line_total_cents' => 10000,
            'unit_price' => 100, 'discount_amount' => 0, 'tax_amount' => 0, 'line_total' => 100,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('sales_order_lot_allocations')->insert([
            'sales_order_item_id' => $itemId, 'inventory_lot_id' => $ownedLot->id, 'quantity' => 1,
            'ownership_snapshot' => 'OWNED', 'settlement_cost_cents' => 4000,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $overview = app(BusinessOverviewService::class)->get();
        $this->assertSame(10000, $overview['sales']['today_cents']);
        $this->assertSame(10000, $overview['sales']['month_cents']);
        $this->assertSame(1, $overview['sales']['partially_paid_count']);
        $this->assertSame(10000, $overview['profitability']['today']['revenue_cents']);
        $this->assertSame(4000, $overview['profitability']['today']['cogs_cents']);
        $this->assertSame(6000, $overview['profitability']['today']['gross_profit_cents']);
        $this->assertSame(60.0, $overview['profitability']['today']['gross_margin_percent']);
        $this->assertSame(150.0, $overview['profitability']['today']['markup_percent']);
        $this->assertSame(2500, $overview['profitability']['today']['operating_expenses_cents']);
        $this->assertSame(3500, $overview['profitability']['today']['operating_profit_cents']);
        $this->assertSame($overview['profitability']['today'], $overview['profitability']['month']);
        $this->assertSame(7.0, $overview['inventory']['available_quantity']);
        $this->assertSame(4.0, $overview['inventory']['owned_quantity']);
        $this->assertSame(3.0, $overview['inventory']['consignment_quantity']);
        $this->assertDatabaseHas('sales_orders', ['id' => $closedId]);
    }
}
