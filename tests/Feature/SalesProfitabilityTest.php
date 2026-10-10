<?php

namespace Tests\Feature;

use App\Domains\Inventory\Services\InventoryLotService;
use App\Domains\Sales\Models\SalesOrder;
use App\Domains\Sales\Repositories\SalesOrderRepository;
use App\Domains\Sales\Resources\SalesOrderResource;
use App\Domains\Sales\Services\SalesOrderPaymentService;
use App\Domains\Sales\Services\SalesOrderService;
use Illuminate\Support\Facades\DB;

class SalesProfitabilityTest extends SalesOrderPaymentTest
{
    public function test_owned_discounted_sale_uses_historical_acquisition_cost_and_final_revenue(): void
    {
        [$order, $lots] = $this->completeSale([
            ['OWNED', null, 1, 3000000],
        ], 1, 200000);

        $profitability = $this->resource($order)['profitability'];
        $this->assertSame(4300000, $profitability['revenue_cents']);
        $this->assertSame(3000000, $profitability['cogs_cents']);
        $this->assertSame(1300000, $profitability['gross_profit_cents']);
        $this->assertSame(30.23, $profitability['gross_margin_percent']);
        $this->assertSame(43.33, $profitability['markup_percent']);

        $allocation = $this->resource($order)['items'][0]['lot_allocations'][0];
        $this->assertSame('OWNED', $allocation['ownership']);
        $this->assertSame(3000000, $allocation['unit_cost_basis_cents']);
        $this->assertSame(3000000, $allocation['total_cost_basis_cents']);

        DB::table('inventory_lots')->where('id', $lots[0]->id)->update(['settlement_cost_cents' => 1, 'ownership' => 'CONSIGNMENT']);
        DB::table('inventory_objects')->where('id', 1)->update(['retail_price_cents' => 1]);

        $unchanged = $this->resource(app(SalesOrderRepository::class)->find($order->id));
        $this->assertSame(3000000, $unchanged['profitability']['cogs_cents']);
        $this->assertSame('OWNED', $unchanged['items'][0]['lot_allocations'][0]['ownership']);
    }

    public function test_consignment_sale_uses_historical_dealer_settlement_cost(): void
    {
        [$order] = $this->completeSale([
            ['CONSIGNMENT', 1, 1, 3500000],
        ], 1);

        $profitability = $this->resource($order)['profitability'];
        $this->assertSame(4500000, $profitability['revenue_cents']);
        $this->assertSame(3500000, $profitability['cogs_cents']);
        $this->assertSame(1000000, $profitability['gross_profit_cents']);
        $this->assertSame('CONSIGNMENT', $this->resource($order)['items'][0]['lot_allocations'][0]['ownership']);
    }

    public function test_mixed_lot_sale_sums_each_allocations_historical_unit_cost(): void
    {
        [$order] = $this->completeSale([
            ['OWNED', null, 1, 3000000],
            ['CONSIGNMENT', 1, 1, 3200000],
        ], 2);

        $profitability = $this->resource($order)['profitability'];
        $this->assertSame(9000000, $profitability['revenue_cents']);
        $this->assertSame(6200000, $profitability['cogs_cents']);
        $this->assertSame(2800000, $profitability['gross_profit_cents']);
        $this->assertSame(31.11, $profitability['gross_margin_percent']);
        $this->assertSame(45.16, $profitability['markup_percent']);
    }

    private function completeSale(array $lotSpecs, int $quantity, int $discountCents = 0): array
    {
        $order = app(SalesOrderService::class)->create([
            'customer_id' => null,
            'branch_id' => 1,
            'order_date' => now()->toDateString(),
            'sale_type' => 'RETAIL',
            'items' => [[
                'inventory_id' => 1,
                'unit_id' => 1,
                'quantity' => $quantity,
                'discount_cents' => $discountCents,
            ]],
        ]);
        $order = app(SalesOrderService::class)->confirm($order);
        app(SalesOrderPaymentService::class)->record($order, [
            'method' => 'BANK_TRANSFER',
            'tendered_amount_cents' => (int) round(((float) $order->total_amount) * 100),
            'reference' => 'BANK-PROFIT-'.$order->id,
        ], 1);

        $lots = [];
        $allocations = [];
        foreach ($lotSpecs as [$ownership, $supplierId, $lotQuantity, $unitCostCents]) {
            $lot = app(InventoryLotService::class)->receive([
                'inventory_object_id' => 1,
                'warehouse_id' => 1,
                'supplier_id' => $supplierId,
                'ownership' => $ownership,
                'quantity_received' => $lotQuantity,
                'settlement_cost_cents' => $unitCostCents,
                'received_date' => now()->toDateString(),
            ], 1);
            $lots[] = $lot;
            $allocations[] = [
                'sales_order_item_id' => $order->items->first()->id,
                'inventory_lot_id' => $lot->id,
                'quantity' => $lotQuantity,
            ];
        }

        $completed = app(SalesOrderService::class)->complete($order->fresh(), [
            'warehouse_id' => 1,
            'allocations' => $allocations,
        ], 1);

        return [$completed, $lots];
    }

    private function resource(SalesOrder $order): array
    {
        return (new SalesOrderResource($order))->toArray(request());
    }
}
