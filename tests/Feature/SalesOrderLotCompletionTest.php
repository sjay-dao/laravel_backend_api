<?php

namespace Tests\Feature;

use App\Domains\Inventory\Services\InventoryLotService;
use App\Domains\Sales\Models\SalesOrder;
use App\Domains\Sales\Services\SalesOrderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SalesOrderLotCompletionTest extends InventoryLotFoundationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSalesTables();
        Schema::table('inventory_movement_types', function (Blueprint $t) {
            $t->text('description')->nullable();
        });
        (require database_path('migrations/2026_09_30_010000_add_sales_lot_allocation_movement_link.php'))->up();
        $now = now();
        DB::table('lookup_types')->insert(['id' => 1, 'code' => 'SALES_ORDER_STATUS', 'name' => 'Sales order status', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('lookups')->insert([
            ['id' => 1, 'lookup_type_id' => 1, 'code' => 'DRAFT', 'name' => 'Draft', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'lookup_type_id' => 1, 'code' => 'CONFIRMED', 'name' => 'Confirmed', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'lookup_type_id' => 1, 'code' => 'CLOSED', 'name' => 'Closed', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function test_owned_lot_sale_deducts_stock_and_records_a_traceable_out_movement(): void
    {
        $lot = $this->receive('OWNED', null, 5, 500000);
        $order = $this->confirmedOrder([[1, 1, 2]]);
        $this->complete($order, [[$order->items->first()->id, $lot->id, 2]]);
        $this->assertDatabaseHas('inventory_lots', ['id' => $lot->id, 'quantity_available' => 3]);
        $this->assertDatabaseHas('sales_order_lot_allocations', ['inventory_lot_id' => $lot->id, 'quantity' => 2, 'settlement_cost_cents' => 500000]);
        $this->assertDatabaseHas('inventory_movements', ['reference_type' => SalesOrder::class, 'reference_id' => $order->id]);
        $this->assertDatabaseCount('inventory_lot_movements', 2);
        $this->assertDatabaseHas('sales_orders', ['id' => $order->id, 'status_id' => 3]);
    }

    public function test_consignment_sale_preserves_dealer_and_settlement_snapshot(): void
    {
        $lot = $this->receive('CONSIGNMENT', 1, 3, 475000);
        $order = $this->confirmedOrder([[1, 1, 1]]);
        $this->complete($order, [[$order->items->first()->id, $lot->id, 1]]);
        $this->assertDatabaseHas('inventory_lots', ['id' => $lot->id, 'supplier_id' => 1, 'ownership' => 'CONSIGNMENT', 'quantity_available' => 2]);
        $this->assertDatabaseHas('sales_order_lot_allocations', ['inventory_lot_id' => $lot->id, 'settlement_cost_cents' => 475000]);
    }

    public function test_one_sale_item_can_mix_owned_and_consignment_lots(): void
    {
        $owned = $this->receive('OWNED', null, 2, 500000);
        $consigned = $this->receive('CONSIGNMENT', 1, 2, 475000);
        $order = $this->confirmedOrder([[1, 1, 3]]);
        $this->complete($order, [[$order->items->first()->id, $owned->id, 2], [$order->items->first()->id, $consigned->id, 1]]);
        $this->assertDatabaseHas('inventory_lots', ['id' => $owned->id, 'quantity_available' => 0]);
        $this->assertDatabaseHas('inventory_lots', ['id' => $consigned->id, 'quantity_available' => 1]);
        $this->assertDatabaseCount('sales_order_lot_allocations', 2);
    }

    public function test_insufficient_lot_quantity_rolls_back_the_entire_sale(): void
    {
        $lot = $this->receive('OWNED', null, 1, 500000);
        $order = $this->confirmedOrder([[1, 1, 2]]);
        try {
            $this->complete($order, [[$order->items->first()->id, $lot->id, 2]]);
            $this->fail('Expected an insufficient stock validation error.');
        } catch (ValidationException) {
        }
        $this->assertDatabaseHas('inventory_lots', ['id' => $lot->id, 'quantity_available' => 1]);
        $this->assertDatabaseCount('sales_order_lot_allocations', 0);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseHas('sales_orders', ['id' => $order->id, 'status_id' => 2]);
    }

    public function test_wrong_product_lot_is_rejected_without_any_deduction(): void
    {
        DB::table('inventory_objects')->insert(['id' => 2, 'code' => 'EB-TWO', 'name' => 'E-bike Two', 'inventory_category_id' => 1, 'unit_id' => 1, 'track_inventory' => true, 'is_sellable' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('inventory_object_units')->insert(['id' => 2, 'inventory_object_id' => 2, 'unit_id' => 1, 'conversion_factor' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $wrongLot = app(InventoryLotService::class)->receive(['inventory_object_id' => 2, 'warehouse_id' => 1, 'ownership' => 'OWNED', 'quantity_received' => 2, 'settlement_cost_cents' => 400000, 'received_date' => '2026-09-30'], 1);
        $order = $this->confirmedOrder([[1, 1, 1]]);
        try {
            $this->complete($order, [[$order->items->first()->id, $wrongLot->id, 1]]);
            $this->fail('Expected wrong-product validation error.');
        } catch (ValidationException) {
        }
        $this->assertDatabaseHas('inventory_lots', ['id' => $wrongLot->id, 'quantity_available' => 2]);
    }

    public function test_second_completion_attempt_cannot_deduct_a_lot_twice(): void
    {
        $lot = $this->receive('OWNED', null, 5, 500000);
        $order = $this->confirmedOrder([[1, 1, 2]]);
        $allocations = [[$order->items->first()->id, $lot->id, 2]];
        $this->complete($order, $allocations);
        try {
            $this->complete($order, $allocations);
            $this->fail('Expected completed order rejection.');
        } catch (InvalidArgumentException) {
        }
        $this->assertDatabaseHas('inventory_lots', ['id' => $lot->id, 'quantity_available' => 3]);
        $this->assertDatabaseCount('sales_order_lot_allocations', 1);
    }

    public function test_selling_dealer_a_lot_does_not_modify_dealer_b_lot_for_the_same_product(): void
    {
        $dealerA = $this->receive('CONSIGNMENT', 1, 3, 475000);
        $dealerB = $this->receive('CONSIGNMENT', 2, 4, 480000);
        $order = $this->confirmedOrder([[1, 1, 1]]);
        $this->complete($order, [[$order->items->first()->id, $dealerA->id, 1]]);
        $this->assertDatabaseHas('inventory_lots', ['id' => $dealerA->id, 'supplier_id' => 1, 'quantity_available' => 2]);
        $this->assertDatabaseHas('inventory_lots', ['id' => $dealerB->id, 'supplier_id' => 2, 'quantity_available' => 4]);
    }

    private function receive(string $ownership, ?int $supplierId, int $quantity, int $cost)
    {
        return app(InventoryLotService::class)->receive(['inventory_object_id' => 1, 'warehouse_id' => 1, 'supplier_id' => $supplierId, 'ownership' => $ownership, 'quantity_received' => $quantity, 'settlement_cost_cents' => $cost, 'received_date' => '2026-09-30'], 1);
    }

    private function confirmedOrder(array $items): SalesOrder
    {
        $now = now();
        $id = DB::table('sales_orders')->insertGetId(['order_no' => 'SO-TEST-'.DB::table('sales_orders')->count(), 'branch_id' => 1, 'order_date' => '2026-09-30', 'status_id' => 2, 'subtotal' => 0, 'discount_amount' => 0, 'tax_amount' => 0, 'total_amount' => 0, 'created_at' => $now, 'updated_at' => $now]);
        foreach ($items as [$inventoryId, $unitId, $quantity]) {
            DB::table('sales_order_items')->insert(['sales_order_id' => $id, 'inventory_id' => $inventoryId, 'unit_id' => $unitId, 'quantity' => $quantity, 'unit_price' => 1, 'discount_amount' => 0, 'tax_amount' => 0, 'line_total' => $quantity, 'created_at' => $now, 'updated_at' => $now]);
        }

        return SalesOrder::with('items')->findOrFail($id);
    }

    private function complete(SalesOrder $order, array $allocations): void
    {
        app(SalesOrderService::class)->complete($order, ['warehouse_id' => 1, 'allocations' => array_map(fn ($row) => ['sales_order_item_id' => $row[0], 'inventory_lot_id' => $row[1], 'quantity' => $row[2]], $allocations)], 1);
    }

    private function createSalesTables(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('name');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('lookup_types', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('lookups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lookup_type_id')->constrained();
            $t->string('code');
            $t->string('name');
            $t->boolean('is_active');
            $t->timestamps();
        });
        Schema::table('sales_orders', function (Blueprint $t) {
            $t->string('order_no');
            $t->foreignId('customer_id')->nullable()->constrained();
            $t->foreignId('branch_id')->constrained();
            $t->date('order_date');
            $t->foreignId('status_id')->constrained('lookups');
            $t->decimal('subtotal', 18, 2);
            $t->decimal('discount_amount', 18, 2);
            $t->decimal('tax_amount', 18, 2);
            $t->decimal('total_amount', 18, 2);
            $t->softDeletes();
        });
        Schema::table('sales_order_items', function (Blueprint $t) {
            $t->foreignId('inventory_id')->constrained('inventory_objects');
            $t->foreignId('unit_id')->constrained('units');
            $t->decimal('quantity', 18, 6);
            $t->decimal('unit_price', 18, 4);
            $t->decimal('discount_amount', 18, 2);
            $t->decimal('tax_amount', 18, 2);
            $t->decimal('line_total', 18, 2);
            $t->text('remarks')->nullable();
        });
    }
}
