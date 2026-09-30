<?php

namespace Tests\Feature;

use App\Domains\Inventory\Services\InventoryLotService;
use App\Domains\Sales\Models\SalesOrder;
use App\Domains\Sales\Resources\SalesOrderResource;
use App\Domains\Sales\Services\SalesOrderPaymentService;
use App\Domains\Sales\Services\SalesOrderService;
use Illuminate\Validation\ValidationException;

class SalesOrderPaymentTest extends SalesOrderPricingTest
{
    public function test_cash_payment_applies_only_balance_and_records_change(): void
    {
        $order = $this->confirmedOrder(1, 200000);
        $updated = app(SalesOrderPaymentService::class)->record($order, ['method' => 'CASH', 'tendered_amount_cents' => 4500000], 1);

        $this->assertSame('PAID', $this->summary($updated)['status']);
        $this->assertDatabaseHas('sales_order_payments', ['sales_order_id' => $order->id, 'applied_amount_cents' => 4300000, 'change_cents' => 200000]);
    }

    public function test_partial_and_split_payments_derive_payment_state(): void
    {
        $order = $this->confirmedOrder(1);
        $partial = app(SalesOrderPaymentService::class)->record($order, ['method' => 'CASH', 'tendered_amount_cents' => 2000000], 1);
        $this->assertSame('PARTIALLY_PAID', $this->summary($partial)['status']);
        $paid = app(SalesOrderPaymentService::class)->record($order, ['method' => 'GCASH', 'tendered_amount_cents' => 2500000, 'reference' => 'GCASH-DEMO-001'], 1);
        $this->assertSame('PAID', $this->summary($paid)['status']);
        $this->assertDatabaseCount('sales_order_payments', 2);
    }

    public function test_non_cash_overpayment_and_missing_reference_are_rejected(): void
    {
        $order = $this->confirmedOrder(1);
        foreach ([
            ['method' => 'GCASH', 'tendered_amount_cents' => 4500000],
            ['method' => 'BANK_TRANSFER', 'tendered_amount_cents' => 4500001, 'reference' => 'BANK-001'],
        ] as $payment) {
            try {
                app(SalesOrderPaymentService::class)->record($order, $payment, 1);
                $this->fail('Expected payment validation failure.');
            } catch (ValidationException) {
            }
        }
        $this->assertDatabaseCount('sales_order_payments', 0);
    }

    public function test_unpaid_order_cannot_complete_but_paid_order_can(): void
    {
        $lot = app(InventoryLotService::class)->receive(['inventory_object_id' => 1, 'warehouse_id' => 1, 'ownership' => 'OWNED', 'quantity_received' => 1, 'settlement_cost_cents' => 3000000, 'received_date' => '2026-10-01'], 1);
        $order = $this->confirmedOrder(1);
        $payload = ['warehouse_id' => 1, 'allocations' => [['sales_order_item_id' => $order->items->first()->id, 'inventory_lot_id' => $lot->id, 'quantity' => 1]]];
        try {
            app(SalesOrderService::class)->complete($order, $payload, 1);
            $this->fail('Expected unpaid completion failure.');
        } catch (ValidationException) {
        }
        $this->assertDatabaseHas('inventory_lots', ['id' => $lot->id, 'quantity_available' => 1]);
        app(SalesOrderPaymentService::class)->record($order, ['method' => 'BANK_TRANSFER', 'tendered_amount_cents' => 4500000, 'reference' => 'BANK-PAID'], 1);
        app(SalesOrderService::class)->complete($order->fresh(), $payload, 1);
        $this->assertDatabaseHas('inventory_lots', ['id' => $lot->id, 'quantity_available' => 0]);
    }

    private function confirmedOrder(int $quantity, int $discountCents = 0): SalesOrder
    {
        $order = app(SalesOrderService::class)->create([
            'customer_id' => null, 'branch_id' => 1, 'order_date' => '2026-10-01', 'sale_type' => 'RETAIL',
            'items' => [['inventory_id' => 1, 'unit_id' => 1, 'quantity' => $quantity, 'discount_cents' => $discountCents]],
        ]);

        return app(SalesOrderService::class)->confirm($order);
    }

    private function summary(SalesOrder $order): array
    {
        return (new SalesOrderResource($order))->toArray(request())['payment_summary'];
    }
}
