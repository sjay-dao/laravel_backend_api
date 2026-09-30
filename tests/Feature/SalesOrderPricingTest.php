<?php

namespace Tests\Feature;

use App\Domains\Sales\Models\SalesOrder;
use App\Domains\Sales\Services\SalesOrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SalesOrderPricingTest extends SalesOrderLotCompletionTest
{
    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_09_30_020000_add_sales_pricing_foundation.php'))->up();
        DB::table('inventory_objects')->where('id', 1)->update(['retail_price_cents' => 4500000]);
    }

    public function test_retail_price_is_resolved_and_snapshotted_in_cents(): void
    {
        $order = $this->createPricedOrder('RETAIL', 1);
        $this->assertDatabaseHas('sales_order_items', ['sales_order_id' => $order->id, 'list_unit_price_cents' => 4500000, 'final_unit_price_cents' => 4500000, 'line_total_cents' => 4500000]);
    }

    public function test_wholesale_tiers_resolve_for_each_quantity_range(): void
    {
        $this->tiers();
        $this->assertSame(4200000, $this->createPricedOrder('WHOLESALE', 2)->items->first()->list_unit_price_cents);
        $this->assertSame(4050000, $this->createPricedOrder('WHOLESALE', 4)->items->first()->list_unit_price_cents);
        $this->assertSame(3900000, $this->createPricedOrder('WHOLESALE', 6)->items->first()->list_unit_price_cents);
    }

    public function test_manual_discount_is_preserved_and_cannot_make_price_negative(): void
    {
        $order = $this->createPricedOrder('RETAIL', 1, 200000);
        $this->assertDatabaseHas('sales_order_items', ['sales_order_id' => $order->id, 'discount_cents' => 200000, 'final_unit_price_cents' => 4300000, 'line_total_cents' => 4300000]);
        try {
            $this->createPricedOrder('RETAIL', 1, 4500001);
            $this->fail('Expected discount validation failure.');
        } catch (ValidationException) {
        }
    }

    public function test_later_retail_or_tier_changes_do_not_change_historical_line_snapshots(): void
    {
        $retail = $this->createPricedOrder('RETAIL', 1);
        DB::table('inventory_objects')->where('id', 1)->update(['retail_price_cents' => 4800000]);
        $this->assertSame(4500000, (int) DB::table('sales_order_items')->where('sales_order_id', $retail->id)->value('final_unit_price_cents'));
        $this->tiers();
        $wholesale = $this->createPricedOrder('WHOLESALE', 3);
        DB::table('inventory_wholesale_price_tiers')->where('inventory_object_id', 1)->where('min_quantity', 3)->update(['unit_price_cents' => 3500000]);
        $this->assertSame(4050000, (int) DB::table('sales_order_items')->where('sales_order_id', $wholesale->id)->value('final_unit_price_cents'));
    }

    public function test_no_matching_wholesale_tier_and_closed_order_repricing_are_rejected(): void
    {
        try {
            $this->createPricedOrder('WHOLESALE', 1);
            $this->fail('Expected missing-tier failure.');
        } catch (InvalidArgumentException) {
        }
        $this->assertDatabaseCount('sales_orders', 0);
        $order = $this->createPricedOrder('RETAIL', 1);
        DB::table('sales_orders')->where('id', $order->id)->update(['status_id' => 3]);
        try {
            app(SalesOrderService::class)->update(SalesOrder::findOrFail($order->id), $this->payload('RETAIL', 1));
            $this->fail('Expected closed order repricing rejection.');
        } catch (InvalidArgumentException) {
        }
        $this->assertSame(4500000, (int) DB::table('sales_order_items')->where('sales_order_id', $order->id)->value('final_unit_price_cents'));
    }

    private function createPricedOrder(string $saleType, int $quantity, int $discountCents = 0): SalesOrder
    {
        return app(SalesOrderService::class)->create($this->payload($saleType, $quantity, $discountCents));
    }

    private function payload(string $saleType, int $quantity, int $discountCents = 0): array
    {
        return ['customer_id' => null, 'branch_id' => 1, 'order_date' => '2026-09-30', 'sale_type' => $saleType, 'items' => [['inventory_id' => 1, 'unit_id' => 1, 'quantity' => $quantity, 'discount_cents' => $discountCents]]];
    }

    private function tiers(): void
    {
        DB::table('inventory_wholesale_price_tiers')->delete();
        DB::table('inventory_wholesale_price_tiers')->insert([
            ['inventory_object_id' => 1, 'min_quantity' => 1, 'max_quantity' => 2, 'unit_price_cents' => 4200000, 'created_at' => now(), 'updated_at' => now()],
            ['inventory_object_id' => 1, 'min_quantity' => 3, 'max_quantity' => 5, 'unit_price_cents' => 4050000, 'created_at' => now(), 'updated_at' => now()],
            ['inventory_object_id' => 1, 'min_quantity' => 6, 'max_quantity' => null, 'unit_price_cents' => 3900000, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
