<?php

namespace Tests\Feature;

use App\Domains\Inventory\Services\InventoryLotService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryLotFoundationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createFoundationTables();
        (require database_path('migrations/2026_09_30_000000_add_inventory_ownership_lot_foundation.php'))->up();

        $now = now();
        DB::table('users')->insert(['id' => 1, 'name' => 'Stock Clerk', 'email' => 'stock@example.test', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('branches')->insert(['id' => 1, 'code' => 'MAIN', 'name' => 'Main', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('warehouses')->insert(['id' => 1, 'branch_id' => 1, 'code' => 'MAIN-STOCK', 'name' => 'Main stock', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('units')->insert(['id' => 1, 'code' => 'PC', 'name' => 'Piece', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('inventory_categories')->insert(['id' => 1, 'code' => 'EBIKE', 'name' => 'E-bike', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('inventory_objects')->insert(['id' => 1, 'code' => 'EB-ONE', 'name' => 'E-bike One', 'inventory_category_id' => 1, 'unit_id' => 1, 'track_inventory' => true, 'is_sellable' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('inventory_object_units')->insert(['id' => 1, 'inventory_object_id' => 1, 'unit_id' => 1, 'conversion_factor' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('suppliers')->insert([
            ['id' => 1, 'code' => 'DEALER-A', 'name' => 'Dealer A', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'DEALER-B', 'name' => 'Dealer B', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('inventory_movement_types')->insert(['id' => 1, 'code' => 'LOT_RECEIPT', 'name' => 'Lot receipt', 'direction' => 'IN', 'created_at' => $now, 'updated_at' => $now]);
    }

    public function test_product_can_hold_owned_and_multiple_dealer_consignment_lots_with_traceable_receipts(): void
    {
        $service = app(InventoryLotService::class);
        $service->receive($this->receipt('OWNED', null, 5, 500000), 1);
        $service->receive($this->receipt('CONSIGNMENT', 1, 2, 475000), 1);
        $service->receive($this->receipt('CONSIGNMENT', 2, 1, 480000), 1);

        $this->assertDatabaseCount('inventory_lots', 3);
        $this->assertDatabaseHas('inventory_lots', ['inventory_object_id' => 1, 'ownership' => 'OWNED', 'supplier_id' => null, 'quantity_received' => 5, 'quantity_available' => 5, 'settlement_cost_cents' => 500000]);
        $this->assertDatabaseHas('inventory_lots', ['inventory_object_id' => 1, 'ownership' => 'CONSIGNMENT', 'supplier_id' => 1, 'quantity_received' => 2, 'settlement_cost_cents' => 475000]);
        $this->assertDatabaseHas('inventory_lots', ['inventory_object_id' => 1, 'ownership' => 'CONSIGNMENT', 'supplier_id' => 2, 'quantity_received' => 1, 'settlement_cost_cents' => 480000]);
        $this->assertDatabaseCount('inventory_movements', 3);
        $this->assertDatabaseCount('inventory_movement_items', 3);
        $this->assertDatabaseCount('inventory_lot_movements', 3);
        $this->assertSame(8.0, (float) DB::table('inventory_lots')->where('inventory_object_id', 1)->sum('quantity_available'));
        $this->assertSame(3, DB::table('inventory_movements')->where('reference_type', 'App\\Domains\\Inventory\\Models\\InventoryLot')->count());
    }

    public function test_consignment_requires_a_supplier_and_does_not_write_a_partial_receipt(): void
    {
        try {
            app(InventoryLotService::class)->receive($this->receipt('CONSIGNMENT', null, 1, 400000), 1);
            $this->fail('Expected validation failure.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('supplier_id', $exception->errors());
        }
        $this->assertDatabaseCount('inventory_lots', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    private function receipt(string $ownership, ?int $supplierId, int $quantity, int $cost): array
    {
        return ['inventory_object_id' => 1, 'warehouse_id' => 1, 'supplier_id' => $supplierId, 'ownership' => $ownership, 'quantity_received' => $quantity, 'settlement_cost_cents' => $cost, 'received_date' => '2026-09-30'];
    }

    private function createFoundationTables(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->timestamps();
        });
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('name');
            $t->boolean('is_active');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('warehouses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained();
            $t->string('code');
            $t->string('name');
            $t->boolean('is_active');
            $t->timestamps();
        });
        Schema::create('units', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('inventory_categories', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('inventory_objects', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('name');
            $t->foreignId('inventory_category_id')->constrained();
            $t->foreignId('unit_id')->constrained('units');
            $t->boolean('track_inventory');
            $t->boolean('is_sellable');
            $t->boolean('is_active');
            $t->timestamps();
        });
        Schema::create('inventory_object_units', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inventory_object_id')->constrained();
            $t->foreignId('unit_id')->constrained('units');
            $t->decimal('conversion_factor', 18, 6);
            $t->timestamps();
        });
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('name');
            $t->boolean('is_active');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('inventory_movement_types', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('name');
            $t->string('direction');
            $t->timestamps();
        });
        Schema::create('inventory_movements', function (Blueprint $t) {
            $t->id();
            $t->string('transaction_no');
            $t->foreignId('movement_type_id')->constrained('inventory_movement_types');
            $t->dateTime('movement_date');
            $t->foreignId('branch_id')->nullable()->constrained();
            $t->foreignId('warehouse_id')->nullable()->constrained();
            $t->string('reference_type')->nullable();
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->text('remarks')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users');
            $t->timestamps();
        });
        Schema::create('inventory_movement_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inventory_movement_id')->constrained();
            $t->foreignId('inventory_object_unit_id')->constrained();
            $t->decimal('quantity', 18, 6);
            $t->text('remarks')->nullable();
            $t->timestamps();
        });
        Schema::create('sales_orders', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
        });
        Schema::create('sales_order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sales_order_id')->constrained();
            $t->timestamps();
        });
    }
}
