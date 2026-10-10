<?php

namespace Tests\Feature;

use App\Domains\Inventory\Resources\InventoryLotResource;
use App\Domains\Inventory\Resources\InventoryProductSummaryResource;
use App\Domains\Inventory\Services\InventoryReadService;
use Database\Seeders\MicaEbikeDemoSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MicaEbikeDemoInventoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.enabled' => true]);
        $this->createTables();
        (require database_path('migrations/2026_09_30_000000_add_inventory_ownership_lot_foundation.php'))->up();
        DB::table('inventory_movement_types')->insert(['code' => 'LOT_RECEIPT', 'name' => 'Lot receipt', 'direction' => 'IN', 'created_at' => now(), 'updated_at' => now()]);
        app(MicaEbikeDemoSeeder::class)->run();
    }

    public function test_demo_seeder_creates_products_dealers_stock_and_pricing(): void
    {
        foreach (['CityRide E1', 'UrbanVolt X2', 'CargoMax C3', '48V 20Ah Battery', '60V 20Ah Battery', 'Brake Pad Set', 'Controller 48V', 'Throttle Assembly', 'Rear Basket', 'Phone Holder', 'Rain Cover'] as $name) {
            $this->assertDatabaseHas('inventory_objects', ['name' => $name, 'brand' => 'Mica Demo']);
        }
        $urbanId = $this->urbanVoltId();
        $this->assertDatabaseHas('suppliers', ['code' => 'DEMO-DEALER-A']);
        $this->assertDatabaseHas('suppliers', ['code' => 'DEMO-DEALER-B']);
        $this->assertDatabaseHas('inventory_objects', ['id' => $urbanId, 'retail_price_cents' => 4500000]);
        $this->assertDatabaseHas('inventory_lots', ['inventory_object_id' => $urbanId, 'ownership' => 'OWNED', 'quantity_available' => 5]);
        $this->assertDatabaseHas('inventory_lots', ['inventory_object_id' => $urbanId, 'supplier_id' => $this->dealerId('DEMO-DEALER-A'), 'quantity_available' => 4]);
        $this->assertDatabaseHas('inventory_lots', ['inventory_object_id' => $urbanId, 'supplier_id' => $this->dealerId('DEMO-DEALER-B'), 'quantity_available' => 3]);
        $this->assertDatabaseHas('inventory_wholesale_price_tiers', ['inventory_object_id' => $urbanId, 'min_quantity' => 3, 'max_quantity' => 5, 'unit_price_cents' => 4250000]);
        $this->assertDatabaseHas('inventory_wholesale_price_tiers', ['inventory_object_id' => $urbanId, 'min_quantity' => 11, 'max_quantity' => null, 'unit_price_cents' => 3900000]);
        app(MicaEbikeDemoSeeder::class)->run();
        $this->assertSame(14, DB::table('inventory_lots')->where('notes', 'like', 'MICA-DEMO-LOT-%')->count());
        $this->assertSame(3, DB::table('inventory_wholesale_price_tiers')->where('inventory_object_id', $urbanId)->count());
    }

    public function test_summary_and_lot_reads_expose_expected_demo_data(): void
    {
        $service = app(InventoryReadService::class);
        $urban = collect($service->productSummaries(100)->items())->firstWhere('id', $this->urbanVoltId());
        $summary = (new InventoryProductSummaryResource($urban))->toArray(Request::create('/'));
        $this->assertSame(12.0, (float) $summary['stock']['total_available']);
        $this->assertSame(5.0, (float) $summary['stock']['owned_available']);
        $this->assertSame(7.0, (float) $summary['stock']['consignment_available']);
        $this->assertSame(4500000, $summary['retail_price_cents']);
        $this->assertCount(3, $summary['wholesale_price_tiers']);

        $lotData = $service->productLots($urban)->map(fn ($lot) => (new InventoryLotResource($lot))->toArray(Request::create('/')));
        $this->assertCount(3, $lotData);
        $this->assertTrue($lotData->contains(fn ($lot) => $lot['ownership'] === 'OWNED' && (float) $lot['quantity_available'] === 5.0));
        $this->assertTrue($lotData->contains(fn ($lot) => ($lot['supplier']['code'] ?? null) === 'DEMO-DEALER-A'));
        $this->assertTrue($lotData->contains(fn ($lot) => ($lot['supplier']['code'] ?? null) === 'DEMO-DEALER-B'));
    }

    private function urbanVoltId(): int
    {
        return (int) DB::table('inventory_objects')->where('code', 'DEMO-EB-URBAN-X2')->value('id');
    }

    private function dealerId(string $code): int
    {
        return (int) DB::table('suppliers')->where('code', $code)->value('id');
    }

    private function createTables(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->boolean('is_active');
            $t->timestamps();
        });
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('address')->nullable();
            $t->string('branch_type')->nullable();
            $t->string('branch_category')->nullable();
            $t->unsignedInteger('service_bay_count');
            $t->boolean('is_active');
            $t->timestamps();
        });
        Schema::create('warehouses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained();
            $t->string('code')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->boolean('is_active');
            $t->timestamps();
        });
        Schema::create('units', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('symbol');
            $t->string('type');
            $t->boolean('is_active');
            $t->timestamps();
        });
        Schema::create('inventory_categories', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('inventory_objects', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('brand')->nullable();
            $t->string('variant')->nullable();
            $t->foreignId('inventory_category_id')->constrained();
            $t->foreignId('unit_id')->constrained('units');
            $t->boolean('track_inventory');
            $t->boolean('is_sellable');
            $t->boolean('is_active');
            $t->unsignedBigInteger('retail_price_cents')->nullable();
            $t->timestamps();
        });
        Schema::create('inventory_object_units', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inventory_object_id')->constrained();
            $t->foreignId('unit_id')->constrained();
            $t->decimal('conversion_factor', 18, 6);
            $t->timestamps();
            $t->unique(['inventory_object_id', 'unit_id']);
        });
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->text('remarks')->nullable();
            $t->boolean('is_active');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('inventory_movement_types', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
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
        Schema::create('inventory_wholesale_price_tiers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inventory_object_id')->constrained();
            $t->decimal('min_quantity', 18, 6);
            $t->decimal('max_quantity', 18, 6)->nullable();
            $t->unsignedBigInteger('unit_price_cents');
            $t->timestamps();
        });
    }
}
