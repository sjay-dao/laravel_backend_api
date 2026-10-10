<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Inventory\Models\InventoryMovementType;
use App\Domains\Inventory\Models\InventoryObjectUnit;
use App\Domains\Inventory\Models\Warehouse;
use App\Domains\Inventory\Services\InventoryMovementService;
use App\Domains\Purchasing\Contracts\PurchasingAccountingBoundary;
use App\Domains\Purchasing\Models\GoodsReceipt;
use App\Domains\Purchasing\Models\GoodsReceiptLine;
use App\Domains\Purchasing\Models\GoodsReceiptLineValuation;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderLine;
use App\Domains\Purchasing\Repositories\GoodsReceiptRepository;
use App\Domains\Purchasing\Services\Concerns\BuildsPurchasingDocuments;
use Illuminate\Support\Facades\DB;

class GoodsReceiptService
{
    use BuildsPurchasingDocuments;

    public function __construct(
        protected GoodsReceiptRepository $receipts,
        protected InventoryMovementService $inventoryMovements,
        protected PurchaseOrderService $purchaseOrders,
        protected PurchasingAccountingBoundary $accounting,
    ) {}

    public function paginate(int $perPage = 15) { return $this->receipts->paginate($perPage); }
    public function find(int $id) { return $this->receipts->findById($id); }

    public function createDraft(array $data, int $actorId): GoodsReceipt
    {
        return DB::transaction(function () use ($data, $actorId) {
            $this->validateHeader($data);
            $receipt = GoodsReceipt::query()->create([
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'receipt_number' => $this->documentNumber('GR', GoodsReceipt::class),
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'warehouse_id' => $data['warehouse_id'],
                'received_date' => $data['received_date'],
                'received_at' => $data['received_at'] ?? now(),
                'delivery_reference' => $data['delivery_reference'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'inspection_notes' => $data['inspection_notes'] ?? null,
                'received_by' => $actorId,
                'status' => GoodsReceipt::DRAFT,
            ]);
            $this->replaceLines($receipt, $data['lines']);

            return $receipt->fresh(['purchaseOrder', 'supplier', 'branch', 'warehouse', 'lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit', 'lines.purchaseOrderLine']);
        });
    }

    public function updateDraft(GoodsReceipt $receipt, array $data, int $actorId): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $data, $actorId) {
            $this->requireStatus($receipt, [GoodsReceipt::DRAFT], 'Updating a goods receipt');
            $this->validateHeader($data);
            $receipt->update([
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'supplier_id' => $data['supplier_id'], 'branch_id' => $data['branch_id'],
                'warehouse_id' => $data['warehouse_id'], 'received_date' => $data['received_date'],
                'received_at' => $data['received_at'] ?? $receipt->received_at,
                'delivery_reference' => $data['delivery_reference'] ?? null,
                'remarks' => $data['remarks'] ?? null, 'inspection_notes' => $data['inspection_notes'] ?? null,
                'received_by' => $actorId,
            ]);
            $receipt->lines()->delete();
            $this->replaceLines($receipt, $data['lines']);
            return $receipt->fresh(['lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit', 'lines.purchaseOrderLine']);
        });
    }

    public function post(GoodsReceipt $receipt, int $actorId): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $actorId) {
            $this->requireStatus($receipt, [GoodsReceipt::DRAFT], 'Posting a goods receipt');
            $receipt->load('lines.inventoryObjectUnit.inventoryObject');
            abort_if($receipt->lines->isEmpty(), 422, 'A goods receipt requires at least one line.');

            foreach ($receipt->lines as $line) {
                $this->assertReceivableBalance($receipt, $line);
                GoodsReceiptLineValuation::query()->updateOrCreate(
                    ['goods_receipt_item_id' => $line->id],
                    [
                        'goods_receipt_id' => $receipt->id,
                        'inventory_object_id' => $line->inventory_object_id,
                        'received_quantity' => $line->quantity,
                        'base_quantity' => $line->base_quantity,
                        'conversion_factor' => $line->conversion_factor,
                        'unit_cost' => $line->unit_cost,
                        'total_cost' => $line->total_cost,
                        'valuation_basis' => 'provisional_receipt_cost',
                        'is_inventory_valued' => (bool) $line->inventoryObjectUnit->inventoryObject->track_inventory,
                        'recognized_at' => now(),
                    ]
                );
            }

            $trackedLines = $receipt->lines->filter(
                fn (GoodsReceiptLine $line) => (bool) $line->inventoryObjectUnit->inventoryObject->track_inventory
            );
            $movement = null;
            if ($trackedLines->isNotEmpty()) {
                $type = $this->movementType(['PURCHASE_RECEIPT', 'PURCHASE'], 'IN');
                $movement = $this->inventoryMovements->create([
                    'movement_type_id' => $type->id,
                    'movement_date' => $receipt->received_at ?? now(),
                    'branch_id' => $receipt->branch_id,
                    'warehouse_id' => $receipt->warehouse_id,
                    'reference_type' => GoodsReceipt::class,
                    'reference_id' => $receipt->id,
                    'remarks' => "Goods receipt {$receipt->receipt_number}",
                    'items' => $trackedLines->map(fn (GoodsReceiptLine $line) => [
                        'inventory_object_unit_id' => $line->inventory_object_unit_id,
                        'quantity' => $line->quantity,
                        'remarks' => $line->remarks,
                    ])->values()->all(),
                ]);
            }

            $receipt->update([
                'status' => GoodsReceipt::POSTED,
                'inventory_movement_id' => $movement?->id,
                'posted_by' => $actorId,
                'posted_at' => now(),
            ]);
            if ($receipt->purchase_order_id) {
                $this->purchaseOrders->refreshReceiptStatus($receipt->purchaseOrder()->lockForUpdate()->firstOrFail());
            }
            $receipt = $receipt->fresh(['lines.valuation', 'inventoryMovement']);
            $this->accounting->goodsReceiptPosted($receipt);

            return $receipt;
        });
    }

    public function reverse(GoodsReceipt $receipt, string $reason, int $actorId): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $reason, $actorId) {
            $this->requireStatus($receipt, [GoodsReceipt::POSTED], 'Reversing a goods receipt');
            $receipt->load('lines.inventoryObjectUnit.inventoryObject', 'lines.invoiceAllocations');
            abort_if($receipt->lines->contains(fn (GoodsReceiptLine $line) => $line->invoiceAllocations()->exists()), 422, 'A receipt with supplier-invoice allocations must be corrected through a supplier return and credit-note workflow.');

            $trackedLines = $receipt->lines->filter(
                fn (GoodsReceiptLine $line) => (bool) $line->inventoryObjectUnit->inventoryObject->track_inventory
            );
            if ($trackedLines->isNotEmpty()) {
                $type = $this->movementType(['SUPPLIER_RETURN', 'RETURN'], 'OUT');
                $this->inventoryMovements->create([
                    'movement_type_id' => $type->id,
                    'movement_date' => now(),
                    'branch_id' => $receipt->branch_id,
                    'warehouse_id' => $receipt->warehouse_id,
                    'reference_type' => GoodsReceipt::class . ':reversal',
                    'reference_id' => $receipt->id,
                    'remarks' => "Reversal of goods receipt {$receipt->receipt_number}: {$reason}",
                    'items' => $trackedLines->map(fn (GoodsReceiptLine $line) => [
                        'inventory_object_unit_id' => $line->inventory_object_unit_id,
                        'quantity' => $line->quantity,
                        'remarks' => $reason,
                    ])->values()->all(),
                ]);
            }
            $receipt->update(['status' => GoodsReceipt::REVERSED, 'reversed_by' => $actorId, 'reversed_at' => now(), 'reversal_reason' => $reason]);
            if ($receipt->purchase_order_id) {
                $this->purchaseOrders->refreshReceiptStatus($receipt->purchaseOrder()->lockForUpdate()->firstOrFail());
            }
            return $receipt->fresh();
        });
    }

    private function validateHeader(array $data): void
    {
        $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);
        abort_unless((int) $warehouse->branch_id === (int) $data['branch_id'], 422, 'The receiving warehouse must belong to the receiving branch.');

        if (! empty($data['purchase_order_id'])) {
            $order = PurchaseOrder::query()->findOrFail($data['purchase_order_id']);
            abort_unless($order->supplier_id === (int) $data['supplier_id'] && $order->branch_id === (int) $data['branch_id'] && $order->warehouse_id === (int) $data['warehouse_id'], 422, 'Goods receipt supplier, branch, and warehouse must match its purchase order.');
            abort_unless(in_array($order->status, [PurchaseOrder::ISSUED, PurchaseOrder::PARTIALLY_RECEIVED], true), 422, 'Goods receipts require an issued purchase order.');
        }
    }

    private function replaceLines(GoodsReceipt $receipt, array $lines): void
    {
        foreach ($lines as $line) {
            $objectUnit = InventoryObjectUnit::query()->with(['inventoryObject', 'unit'])->findOrFail($line['inventory_object_unit_id']);
            abort_unless((bool) $objectUnit->is_active, 422, 'An active Inventory Object Unit is required.');
            $snapshot = $this->snapshot($objectUnit);
            $quantity = $line['accepted_quantity'];
            $purchaseOrderLineId = $line['purchase_order_line_id'] ?? null;
            if ($receipt->purchase_order_id) {
                $purchaseOrderLine = PurchaseOrderLine::query()->findOrFail($purchaseOrderLineId);
                abort_unless((int) $purchaseOrderLine->purchase_order_id === (int) $receipt->purchase_order_id, 422, 'Each receipt line must belong to the selected purchase order.');
                abort_unless((int) $purchaseOrderLine->inventory_object_unit_id === $objectUnit->id, 422, 'Receipt line UOM must match its purchase order line.');
            }
            $unitCost = $line['provisional_unit_cost'];

            $receipt->lines()->create([
                'purchase_order_line_id' => $purchaseOrderLineId,
                'inventory_object_id' => $objectUnit->inventory_object_id,
                'inventory_object_unit_id' => $objectUnit->id,
                'quantity' => $quantity,
                'base_quantity' => $this->baseQuantity($quantity, $snapshot['conversion_factor']),
                ...$snapshot,
                'rejected_quantity' => $line['rejected_quantity'] ?? 0,
                'damaged_quantity' => $line['damaged_quantity'] ?? 0,
                'unit_id' => $objectUnit->unit_id,
                'unit_cost' => $unitCost,
                'total_cost' => $this->lineTotal($quantity, $unitCost),
                'remarks' => $line['remarks'] ?? null,
            ]);
        }
    }

    private function assertReceivableBalance(GoodsReceipt $receipt, GoodsReceiptLine $line): void
    {
        if (! $line->purchase_order_line_id) {
            return;
        }
        $orderLine = PurchaseOrderLine::query()->lockForUpdate()->findOrFail($line->purchase_order_line_id);
        $received = GoodsReceiptLine::query()
            ->where('purchase_order_line_id', $orderLine->id)
            ->whereHas('goodsReceipt', fn ($query) => $query->where('status', GoodsReceipt::POSTED))
            ->sum('base_quantity');

        abort_if($received + (float) $line->base_quantity > (float) $orderLine->base_quantity, 422, 'Receiving quantity exceeds the open purchase order quantity.');
    }

    private function movementType(array $codes, string $direction): InventoryMovementType
    {
        $type = InventoryMovementType::query()->whereIn('code', $codes)->where('direction', $direction)->first();
        abort_if(! $type, 422, "An {$direction} inventory movement type for purchasing must be configured before posting.");
        return $type;
    }
}
