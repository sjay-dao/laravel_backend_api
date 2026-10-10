<?php

namespace Tests\Feature;

use App\Domains\Dashboard\Services\BusinessOverviewService;
use App\Domains\Inventory\Resources\InventoryProductSummaryResource;
use App\Domains\Inventory\Services\InventoryLotService;
use App\Domains\Inventory\Services\InventoryReadService;
use Illuminate\Support\Facades\DB;

class LowStockManagementTest extends BusinessOverviewTest
{
    public function test_threshold_statuses_and_dashboard_count_use_lot_availability(): void
    {
        app(InventoryLotService::class)->receive([
            'inventory_object_id' => 1, 'warehouse_id' => 1, 'ownership' => 'OWNED',
            'quantity_received' => 6, 'settlement_cost_cents' => 1, 'received_date' => now()->toDateString(),
        ], 1);

        DB::table('inventory_objects')->where('id', 1)->update(['low_stock_threshold' => 5]);
        $this->assertSame('IN_STOCK', $this->summary(1)['stock_status']);
        DB::table('inventory_objects')->where('id', 1)->update(['low_stock_threshold' => 6]);
        $this->assertSame('LOW_STOCK', $this->summary(1)['stock_status']);
        DB::table('inventory_objects')->where('id', 1)->update(['low_stock_threshold' => 7]);
        $this->assertSame('LOW_STOCK', $this->summary(1)['stock_status']);

        DB::table('inventory_objects')->insert([
            'id' => 2, 'code' => 'ZERO', 'name' => 'Zero stock', 'inventory_category_id' => 1,
            'unit_id' => 1, 'track_inventory' => true, 'is_sellable' => true, 'is_active' => true,
            'low_stock_threshold' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $zero = $this->summary(2);
        $this->assertSame('OUT_OF_STOCK', $zero['stock_status']);
        $this->assertSame('0', $zero['stock']['total_available']);
        $this->assertSame(2, app(BusinessOverviewService::class)->get()['inventory']['low_stock_product_count']);
    }

    private function summary(int $id): array
    {
        $product = collect(app(InventoryReadService::class)->productSummaries(100)->items())->firstWhere('id', $id);

        return (new InventoryProductSummaryResource($product))->toArray(request());
    }
}
