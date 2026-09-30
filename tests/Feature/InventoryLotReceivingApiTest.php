<?php

namespace Tests\Feature;

use App\Domains\System\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryLotReceivingApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createFoundationTables();
        (require database_path('migrations/2026_09_30_000000_add_inventory_ownership_lot_foundation.php'))->up();
        $this->seedFoundationRecords();
    }

    public function test_authorized_user_can_receive_owned_inventory_with_a_traceable_movement(): void
    {
        $this->actingAsReceiver();

        $response = $this->postJson('/api/inventory/lots', $this->receipt('OWNED', null, 5, 500000));

        $response->assertCreated()
            ->assertJsonPath('data.ownership', 'OWNED')
            ->assertJsonPath('data.quantity_received', '5.000000')
            ->assertJsonPath('data.quantity_available', '5.000000');

        $this->assertDatabaseHas('inventory_lots', [
            'inventory_object_id' => 1,
            'warehouse_id' => 1,
            'supplier_id' => null,
            'ownership' => 'OWNED',
            'quantity_received' => 5,
            'quantity_available' => 5,
            'settlement_cost_cents' => 500000,
            'created_by' => 1,
        ]);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type_id' => 1,
            'warehouse_id' => 1,
            'reference_type' => 'App\\Domains\\Inventory\\Models\\InventoryLot',
            'reference_id' => 1,
            'created_by' => 1,
        ]);
        $this->assertDatabaseCount('inventory_movement_items', 1);
        $this->assertDatabaseCount('inventory_lot_movements', 1);
    }

    public function test_authorized_user_can_receive_consignment_inventory_and_summary_reflects_it(): void
    {
        $this->actingAsReceiver();

        $this->postJson('/api/inventory/lots', $this->receipt('CONSIGNMENT', 1, 10, 3500000))
            ->assertCreated()
            ->assertJsonPath('data.ownership', 'CONSIGNMENT')
            ->assertJsonPath('data.supplier.id', 1);

        $this->assertDatabaseHas('inventory_lots', [
            'inventory_object_id' => 1,
            'supplier_id' => 1,
            'ownership' => 'CONSIGNMENT',
            'quantity_received' => 10,
            'quantity_available' => 10,
            'settlement_cost_cents' => 3500000,
            'created_by' => 1,
        ]);

        $summary = $this->getJson('/api/inventory/inventory-objects/summary?per_page=100')
            ->assertOk()
            ->json('data.0.stock');

        $this->assertSame(10.0, (float) $summary['total_available']);
        $this->assertSame(0.0, (float) $summary['owned_available']);
        $this->assertSame(10.0, (float) $summary['consignment_available']);
    }

    public function test_receipt_rejects_zero_quantity(): void
    {
        $this->actingAsReceiver();

        $this->postJson('/api/inventory/lots', $this->receipt('OWNED', null, 0, 500000))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity_received');

        $this->assertDatabaseCount('inventory_lots', 0);
    }

    public function test_receipt_rejects_negative_quantity(): void
    {
        $this->actingAsReceiver();

        $this->postJson('/api/inventory/lots', $this->receipt('OWNED', null, -1, 500000))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity_received');

        $this->assertDatabaseCount('inventory_lots', 0);
    }

    public function test_receipt_rejects_an_invalid_product(): void
    {
        $this->actingAsReceiver();
        $payload = $this->receipt('OWNED', null, 1, 500000);
        $payload['inventory_object_id'] = 999;

        $this->postJson('/api/inventory/lots', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('inventory_object_id');
    }

    public function test_receipt_rejects_an_invalid_warehouse(): void
    {
        $this->actingAsReceiver();
        $payload = $this->receipt('OWNED', null, 1, 500000);
        $payload['warehouse_id'] = 999;

        $this->postJson('/api/inventory/lots', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('warehouse_id');
    }

    public function test_consignment_receipt_requires_a_supplier_or_dealer(): void
    {
        $this->actingAsReceiver();

        $this->postJson('/api/inventory/lots', $this->receipt('CONSIGNMENT', null, 1, 3500000))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('supplier_id');

        $this->assertDatabaseCount('inventory_lots', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_user_without_lot_create_permission_cannot_receive_inventory(): void
    {
        Sanctum::actingAs(User::findOrFail(2));

        $this->postJson('/api/inventory/lots', $this->receipt('OWNED', null, 1, 500000))
            ->assertForbidden();

        $this->assertDatabaseCount('inventory_lots', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    private function actingAsReceiver(): void
    {
        Sanctum::actingAs(User::findOrFail(1));
    }

    private function receipt(string $ownership, ?int $supplierId, int $quantity, int $cost): array
    {
        return [
            'inventory_object_id' => 1,
            'warehouse_id' => 1,
            'supplier_id' => $supplierId,
            'ownership' => $ownership,
            'quantity_received' => $quantity,
            'settlement_cost_cents' => $cost,
            'received_date' => '2026-09-30',
            'notes' => 'Receiving API test',
        ];
    }

    private function seedFoundationRecords(): void
    {
        $now = now();
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Stock Clerk', 'email' => 'receiver@example.test', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Viewer', 'email' => 'viewer@example.test', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('roles')->insert(['id' => 1, 'code' => 'stock-clerk', 'name' => 'Stock Clerk', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('permissions')->insert([
            ['id' => 1, 'module' => 'inventory', 'resource' => 'lots', 'action' => 'create', 'code' => 'inventory.lots.create', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'module' => 'inventory', 'resource' => 'products', 'action' => 'view', 'code' => 'inventory.products.view', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('user_roles')->insert(['user_id' => 1, 'role_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_permissions')->insert([
            ['role_id' => 1, 'permission_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['role_id' => 1, 'permission_id' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('branches')->insert(['id' => 1, 'code' => 'MAIN', 'name' => 'Main', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('warehouses')->insert(['id' => 1, 'branch_id' => 1, 'code' => 'MAIN-STOCK', 'name' => 'Main stock', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('units')->insert(['id' => 1, 'code' => 'PC', 'name' => 'Piece', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('inventory_categories')->insert(['id' => 1, 'code' => 'EBIKE', 'name' => 'E-bike', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('inventory_objects')->insert(['id' => 1, 'code' => 'EB-ONE', 'name' => 'E-bike One', 'inventory_category_id' => 1, 'unit_id' => 1, 'track_inventory' => true, 'is_sellable' => true, 'is_active' => true, 'retail_price_cents' => 4500000, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('inventory_object_units')->insert(['id' => 1, 'inventory_object_id' => 1, 'unit_id' => 1, 'conversion_factor' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('suppliers')->insert(['id' => 1, 'code' => 'DEALER-A', 'name' => 'Dealer A', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('inventory_movement_types')->insert(['id' => 1, 'code' => 'LOT_RECEIPT', 'name' => 'Lot receipt', 'direction' => 'IN', 'created_at' => $now, 'updated_at' => $now]);
    }

    private function createFoundationTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->boolean('is_active');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('resource');
            $table->string('action');
            $table->string('code');
            $table->boolean('is_active');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained();
            $table->foreignId('permission_id')->constrained();
            $table->timestamps();
        });
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('role_id')->constrained();
            $table->timestamps();
        });
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->boolean('is_active');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->string('code');
            $table->string('name');
            $table->boolean('is_active');
            $table->timestamps();
        });
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('inventory_objects', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->foreignId('inventory_category_id')->constrained();
            $table->foreignId('unit_id')->constrained('units');
            $table->boolean('track_inventory');
            $table->boolean('is_sellable');
            $table->boolean('is_active');
            $table->unsignedBigInteger('retail_price_cents')->nullable();
            $table->timestamps();
        });
        Schema::create('inventory_object_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_object_id')->constrained();
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('conversion_factor', 18, 6);
            $table->timestamps();
        });
        Schema::create('inventory_wholesale_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_object_id')->constrained();
            $table->decimal('min_quantity', 18, 6);
            $table->decimal('max_quantity', 18, 6)->nullable();
            $table->unsignedBigInteger('unit_price_cents');
            $table->timestamps();
        });
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->boolean('is_active');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('inventory_movement_types', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('direction');
            $table->timestamps();
        });
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_no');
            $table->foreignId('movement_type_id')->constrained('inventory_movement_types');
            $table->dateTime('movement_date');
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('warehouse_id')->nullable()->constrained();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
        Schema::create('inventory_movement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_movement_id')->constrained();
            $table->foreignId('inventory_object_unit_id')->constrained();
            $table->decimal('quantity', 18, 6);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained();
            $table->timestamps();
        });
    }
}
