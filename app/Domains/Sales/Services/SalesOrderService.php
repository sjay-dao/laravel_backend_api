<?php

namespace App\Domains\Sales\Services;

use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Sales\Models\SalesOrder;
use App\Domains\Sales\Models\SalesOrderItem;
use App\Domains\Sales\Models\SalesOrderPayment;
use App\Domains\Sales\Repositories\SalesOrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SalesOrderService
{
    public function __construct(
        protected SalesOrderRepository $repository,
        protected SalesPriceResolver $priceResolver
    ) {}

    public function create(array $data): SalesOrder
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);
            if (empty($items)) {
                throw new InvalidArgumentException('A sales order must contain at least one item.');
            }
            $data['order_no'] = $this->repository->generateOrderNumber();
            $data['status_id'] = $this->getDraftStatusId();
            $data['sale_type'] = strtoupper($data['sale_type'] ?? 'RETAIL');
            $items = $this->priceItems($items, $data['sale_type']);
            $totals = $this->calculateTotals($items);
            $data = array_merge($data, $totals);
            $salesOrder = $this->repository->create($data);
            $this->createItems($salesOrder, $items);

            return $salesOrder->load([
                'customer',
                'branch',
                'status',
                'payments.receivedBy',
                'items.inventory',
                'items.unit',
            ]);
        });
    }

    public function update(SalesOrder $salesOrder, array $data): SalesOrder
    {
        $this->ensureEditable($salesOrder);

        return DB::transaction(function () use ($salesOrder, $data) {
            $items = $data['items'] ?? null;
            unset($data['items']);
            if ($items !== null) {
                if (empty($items)) {
                    throw new InvalidArgumentException('A sales order must contain at least one item.');
                }
                $saleType = strtoupper($data['sale_type'] ?? $salesOrder->sale_type ?? 'RETAIL');
                $data['sale_type'] = $saleType;
                $items = $this->priceItems($items, $saleType);
                $totals = $this->calculateTotals($items);
                $data = array_merge($data, $totals);
            }
            $salesOrder = $this->repository->update($salesOrder, $data);
            if ($items !== null) {
                $salesOrder->items()->delete();
                $this->createItems($salesOrder, $items);
            }

            return $salesOrder->load([
                'customer',
                'branch',
                'items.inventory',
                'items.unit',
            ]);
        });
    }

    public function confirm(SalesOrder $salesOrder): SalesOrder
    {
        $this->ensureStatus($salesOrder, 'DRAFT');
        $salesOrder->update([
            'status_id' => $this->getStatusId('CONFIRMED'),
        ]);

        return $salesOrder->refresh()->load([
            'customer',
            'branch',
            'items.inventory',
            'items.unit',
        ]);
    }

    public function cancel(SalesOrder $salesOrder): SalesOrder
    {
        if (! in_array($this->getStatusCode($salesOrder), ['DRAFT', 'CONFIRMED'], true)) {
            throw new InvalidArgumentException('This sales order cannot be cancelled.');
        }
        $salesOrder->update([
            'status_id' => $this->getStatusId('CANCELLED'),
        ]);

        return $salesOrder->refresh();
    }

    /**
     * Completes a confirmed sale from explicit lots. A later reversal must use
     * compensating IN movements; completed sales are never mutated in place.
     */
    public function complete(SalesOrder $salesOrder, array $data, int $actorId): SalesOrder
    {
        return DB::transaction(function () use ($salesOrder, $data, $actorId) {
            $order = SalesOrder::query()->whereKey($salesOrder->id)->lockForUpdate()->firstOrFail();
            $statusCode = (string) DB::table('lookups')->where('id', $order->status_id)->value('code');
            if ($statusCode !== 'CONFIRMED') {
                throw new InvalidArgumentException('Only confirmed sales orders can be completed.');
            }
            $totalCents = (int) round(((float) $order->total_amount) * 100);
            $paidCents = (int) SalesOrderPayment::query()->where('sales_order_id', $order->id)->lockForUpdate()->sum('applied_amount_cents');
            if ($paidCents < $totalCents) {
                $this->invalid('payments', 'The sales order must be fully paid before it can be completed.');
            }
            $warehouse = DB::table('warehouses')->where('id', $data['warehouse_id'])->where('is_active', true)->lockForUpdate()->first();
            if (! $warehouse || (int) $warehouse->branch_id !== (int) $order->branch_id) {
                $this->invalid('warehouse_id', 'Select an active warehouse in the sales order branch.');
            }
            $items = SalesOrderItem::query()->where('sales_order_id', $order->id)->lockForUpdate()->get()->keyBy('id');
            $allocations = $data['allocations'];
            $lotIds = array_values(array_unique(array_map(fn (array $allocation) => (int) $allocation['inventory_lot_id'], $allocations)));
            $lots = DB::table('inventory_lots')->whereIn('id', $lotIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($lots->count() !== count($lotIds)) {
                $this->invalid('allocations', 'One or more selected lots no longer exist.');
            }
            $itemTotals = [];
            $lotTotals = [];
            $pairs = [];
            foreach ($allocations as $index => $allocation) {
                $itemId = (int) $allocation['sales_order_item_id'];
                $lotId = (int) $allocation['inventory_lot_id'];
                $quantity = (float) $allocation['quantity'];
                $item = $items->get($itemId);
                $lot = $lots->get($lotId);
                if (! $item) {
                    $this->invalid("allocations.$index.sales_order_item_id", 'The selected line does not belong to this sales order.');
                }
                if (! $lot || (int) $lot->warehouse_id !== (int) $warehouse->id) {
                    $this->invalid("allocations.$index.inventory_lot_id", 'The selected lot is not available in this warehouse.');
                }
                if ((int) $lot->inventory_object_id !== (int) $item->inventory_id) {
                    $this->invalid("allocations.$index.inventory_lot_id", 'The selected lot does not contain this sales item product.');
                }
                if ((int) $lot->inventory_object_unit_id !== $this->inventoryObjectUnitId($item)) {
                    $this->invalid("allocations.$index.inventory_lot_id", 'The sales item unit must match the lot base unit.');
                }
                if ($lot->ownership === 'CONSIGNMENT' && ! $lot->supplier_id) {
                    $this->invalid("allocations.$index.inventory_lot_id", 'A consignment lot must retain its dealer association.');
                }
                $pair = "$itemId:$lotId";
                if (isset($pairs[$pair])) {
                    $this->invalid("allocations.$index", 'A sales item and lot may be allocated only once.');
                }
                $pairs[$pair] = true;
                $itemTotals[$itemId] = ($itemTotals[$itemId] ?? 0) + $quantity;
                $lotTotals[$lotId] = ($lotTotals[$lotId] ?? 0) + $quantity;
            }
            foreach ($items as $item) {
                if (! $this->sameQuantity($itemTotals[$item->id] ?? 0, (float) $item->quantity)) {
                    $this->invalid('allocations', "Allocations must equal the ordered quantity for sales item {$item->id}.");
                }
            }
            foreach ($lotTotals as $lotId => $quantity) {
                if ($quantity > (float) $lots[$lotId]->quantity_available + 0.0000001) {
                    $this->invalid('allocations', "Lot {$lotId} does not have enough available quantity.");
                }
            }
            $movementType = DB::table('inventory_movement_types')->where('code', 'LOT_SALE_OUT')->first();
            if (! $movementType || $movementType->direction !== 'OUT') {
                throw new \LogicException('The LOT_SALE_OUT inventory movement type is missing.');
            }
            $movement = InventoryMovement::create([
                'transaction_no' => 'SALE-'.Str::uuid(), 'movement_type_id' => $movementType->id,
                'movement_date' => now(), 'branch_id' => $warehouse->branch_id, 'warehouse_id' => $warehouse->id,
                'reference_type' => SalesOrder::class, 'reference_id' => $order->id,
                'remarks' => "Completed sales order {$order->order_no}", 'created_by' => $actorId,
            ]);
            foreach ($allocations as $allocation) {
                $item = $items[(int) $allocation['sales_order_item_id']];
                $lot = $lots[(int) $allocation['inventory_lot_id']];
                $quantity = (float) $allocation['quantity'];
                $movementItemId = DB::table('inventory_movement_items')->insertGetId([
                    'inventory_movement_id' => $movement->id, 'inventory_object_unit_id' => $lot->inventory_object_unit_id,
                    'quantity' => $quantity, 'remarks' => "Sales order {$order->order_no}", 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('inventory_lot_movements')->insert([
                    'inventory_lot_id' => $lot->id, 'inventory_movement_item_id' => $movementItemId, 'quantity' => $quantity,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('sales_order_lot_allocations')->insert([
                    'sales_order_item_id' => $item->id, 'inventory_lot_id' => $lot->id, 'inventory_movement_item_id' => $movementItemId,
                    'quantity' => $quantity, 'ownership_snapshot' => $lot->ownership,
                    'settlement_cost_cents' => $lot->settlement_cost_cents, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            foreach ($lotTotals as $lotId => $quantity) {
                $lot = $lots[$lotId];
                DB::table('inventory_lots')->where('id', $lotId)->update([
                    'quantity_available' => (float) $lot->quantity_available - $quantity, 'updated_at' => now(),
                ]);
            }
            $order->update(['status_id' => $this->getStatusId('CLOSED')]);

            return $order->fresh()->load([
                'customer',
                'branch',
                'status',
                'payments.receivedBy',
                'items.inventory',
                'items.unit',
                'items.lotAllocations.inventoryLot.supplier',
                'items.lotAllocations.inventoryLot.warehouse',
            ]);
        }, 3);
    }

    protected function createItems(SalesOrder $salesOrder, array $items): void
    {
        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPriceCents = (int) $item['list_unit_price_cents'];
            $discountCents = (int) $item['discount_cents'];
            $finalUnitPriceCents = (int) $item['final_unit_price_cents'];
            $lineTotalCents = (int) $item['line_total_cents'];
            $unitPrice = $unitPriceCents / 100;
            $discount = $discountCents / 100;
            $tax = (float) ($item['tax_amount'] ?? 0);
            $lineTotal = ($lineTotalCents / 100) + $tax;
            $salesOrder->items()->create([
                'inventory_id' => $item['inventory_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $quantity,
                'list_unit_price_cents' => $unitPriceCents,
                'discount_cents' => $discountCents,
                'final_unit_price_cents' => $finalUnitPriceCents,
                'line_total_cents' => $lineTotalCents,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'line_total' => $lineTotal,
                'remarks' => $item['remarks'] ?? null,
            ]);
        }
    }

    protected function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $discount = 0;
        $tax = 0;
        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $subtotal += ((int) $item['list_unit_price_cents'] * $quantity) / 100;
            $discount += ((int) $item['discount_cents'] * $quantity) / 100;
            $tax += (float) ($item['tax_amount'] ?? 0);
        }

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => $subtotal - $discount + $tax,
        ];
    }

    protected function ensureEditable(SalesOrder $salesOrder): void
    {
        if ($this->getStatusCode($salesOrder) !== 'DRAFT') {
            throw new InvalidArgumentException('Only draft sales orders can be edited.');
        }
    }

    protected function ensureStatus(SalesOrder $salesOrder, string $expectedStatus): void
    {
        if ($this->getStatusCode($salesOrder) !== $expectedStatus) {
            throw new InvalidArgumentException(
                "Sales order must be {$expectedStatus} before this action."
            );
        }
    }

    protected function getStatusCode(SalesOrder $salesOrder): string
    {
        return $salesOrder->status?->code ?? '';
    }

    protected function getDraftStatusId(): int
    {
        return $this->getStatusId('DRAFT');
    }

    protected function getStatusId(string $code): int
    {
        $lookupTypeId = DB::table('lookup_types')
            ->where('code', 'SALES_ORDER_STATUS')
            ->value('id');

        return (int) DB::table('lookups')
            ->where('lookup_type_id', $lookupTypeId)
            ->where('code', $code)
            ->where('is_active', true)
            ->value('id');
    }

    private function inventoryObjectUnitId(SalesOrderItem $item): int
    {
        $id = DB::table('inventory_object_units')->where('inventory_object_id', $item->inventory_id)->where('unit_id', $item->unit_id)->where('conversion_factor', 1)->value('id');
        if (! $id) {
            $this->invalid('allocations', "Sales item {$item->id} is not configured in its product base unit.");
        }

        return (int) $id;
    }

    private function priceItems(array $items, string $saleType): array
    {
        foreach ($items as $index => $item) {
            $quantity = (float) $item['quantity'];
            try {
                $listPriceCents = $this->priceResolver->resolve((int) $item['inventory_id'], $saleType, $quantity);
            } catch (InvalidArgumentException $exception) {
                $this->invalid("items.$index.quantity", $exception->getMessage());
            }
            $discountCents = array_key_exists('discount_cents', $item)
                ? (int) $item['discount_cents']
                : (int) round(((float) ($item['discount_amount'] ?? 0)) * 100);
            if ($discountCents > $listPriceCents) {
                $this->invalid("items.$index.discount_cents", 'Discount cannot make the final unit price negative.');
            }
            $finalUnitPriceCents = $listPriceCents - $discountCents;
            $items[$index] = [...$item,
                'list_unit_price_cents' => $listPriceCents,
                'discount_cents' => $discountCents,
                'final_unit_price_cents' => $finalUnitPriceCents,
                'line_total_cents' => (int) round($finalUnitPriceCents * $quantity),
            ];
        }

        return $items;
    }

    private function sameQuantity(float $left, float $right): bool
    {
        return abs($left - $right) < 0.0000001;
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
