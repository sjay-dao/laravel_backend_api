<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Inventory\Models\InventoryMovementType;
use App\Domains\Inventory\Services\InventoryMovementService;
use App\Domains\Purchasing\Contracts\PurchasingAccountingBoundary;
use App\Domains\Purchasing\Models\GoodsReceipt;
use App\Domains\Purchasing\Models\GoodsReceiptLine;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\SupplierReturn;
use App\Domains\Purchasing\Models\SupplierReturnLine;
use App\Domains\Purchasing\Repositories\SupplierReturnRepository;
use App\Domains\Purchasing\Services\Concerns\BuildsPurchasingDocuments;
use Illuminate\Support\Facades\DB;

class SupplierReturnService
{
    use BuildsPurchasingDocuments;

    public function __construct(
        protected SupplierReturnRepository $returns,
        protected InventoryMovementService $inventoryMovements,
        protected PurchaseOrderService $purchaseOrders,
        protected PurchasingAccountingBoundary $accounting,
    ) {}

    public function paginate(int $perPage = 15) { return $this->returns->paginate($perPage); }
    public function find(int $id) { return $this->returns->findById($id); }

    public function createDraft(array $data, int $actorId): SupplierReturn
    {
        return DB::transaction(function () use ($data, $actorId) {
            $receipt = GoodsReceipt::query()->findOrFail($data['goods_receipt_id']);
            abort_unless($receipt->status === GoodsReceipt::POSTED, 422, 'Supplier returns require a posted goods receipt.');
            $return = SupplierReturn::query()->create([
                'document_number' => $this->documentNumber('SRT', SupplierReturn::class),
                'supplier_id' => $receipt->supplier_id, 'branch_id' => $receipt->branch_id,
                'warehouse_id' => $receipt->warehouse_id, 'goods_receipt_id' => $receipt->id,
                'return_at' => $data['return_at'] ?? now(), 'remarks' => $data['remarks'] ?? null,
                'status' => SupplierReturn::DRAFT, 'created_by' => $actorId, 'updated_by' => $actorId,
            ]);
            $this->createLines($return, $data['lines']);
            return $return->fresh(['goodsReceipt', 'lines.goodsReceiptLine', 'lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit']);
        });
    }

    public function post(SupplierReturn $return, int $actorId): SupplierReturn
    {
        return DB::transaction(function () use ($return, $actorId) {
            $this->requireStatus($return, [SupplierReturn::DRAFT], 'Posting a supplier return');
            $return->load('lines.goodsReceiptLine.inventoryObjectUnit.inventoryObject');
            abort_if($return->lines->isEmpty(), 422, 'A supplier return requires at least one line.');
            foreach ($return->lines as $line) {
                $this->assertReturnableBalance($line);
            }
            $tracked = $return->lines->filter(fn (SupplierReturnLine $line) => (bool) $line->goodsReceiptLine->inventoryObjectUnit->inventoryObject->track_inventory);
            $movement = null;
            if ($tracked->isNotEmpty()) {
                $type = InventoryMovementType::query()->whereIn('code', ['SUPPLIER_RETURN', 'RETURN'])->where('direction', 'OUT')->first();
                abort_if(! $type, 422, 'An OUT inventory movement type for supplier returns must be configured before posting.');
                $movement = $this->inventoryMovements->create([
                    'movement_type_id' => $type->id, 'movement_date' => $return->return_at,
                    'branch_id' => $return->branch_id, 'warehouse_id' => $return->warehouse_id,
                    'reference_type' => SupplierReturn::class, 'reference_id' => $return->id,
                    'remarks' => "Supplier return {$return->document_number}",
                    'items' => $tracked->map(fn (SupplierReturnLine $line) => [
                        'inventory_object_unit_id' => $line->inventory_object_unit_id,
                        'quantity' => $line->return_quantity, 'remarks' => $line->reason,
                    ])->values()->all(),
                ]);
            }
            $return->update(['status' => SupplierReturn::POSTED, 'inventory_movement_id' => $movement?->id, 'posted_by' => $actorId, 'posted_at' => now()]);
            $receipt = $return->goodsReceipt()->with('purchaseOrder')->first();
            if ($receipt?->purchase_order_id) {
                $this->purchaseOrders->refreshReceiptStatus(PurchaseOrder::query()->lockForUpdate()->findOrFail($receipt->purchase_order_id));
            }
            $return = $return->fresh(['lines.goodsReceiptLine']);
            $this->accounting->supplierReturnPosted($return);
            return $return;
        });
    }

    public function cancel(SupplierReturn $return, string $reason, int $actorId): SupplierReturn
    {
        $this->requireStatus($return, [SupplierReturn::DRAFT], 'Cancelling a supplier return');
        $return->update(['status' => SupplierReturn::CANCELLED, 'cancelled_by' => $actorId, 'cancelled_at' => now(), 'cancellation_reason' => $reason]);
        return $return->fresh();
    }

    private function createLines(SupplierReturn $return, array $lines): void
    {
        foreach ($lines as $line) {
            $receiptLine = GoodsReceiptLine::query()->with(['goodsReceipt', 'inventoryObjectUnit.inventoryObject', 'inventoryObjectUnit.unit'])->findOrFail($line['goods_receipt_item_id']);
            abort_unless((int) $receiptLine->goods_receipt_id === (int) $return->goods_receipt_id, 422, 'Supplier return lines must reference the selected goods receipt.');
            abort_unless($receiptLine->inventory_object_unit_id, 422, 'Legacy receipt lines without an Inventory Object Unit cannot be returned through P2P.');
            $snapshot = $this->snapshot($receiptLine->inventoryObjectUnit);
            $quantity = $line['return_quantity'];
            $return->lines()->create([
                'goods_receipt_item_id' => $receiptLine->id,
                'inventory_object_unit_id' => $receiptLine->inventory_object_unit_id,
                'return_quantity' => $quantity,
                'base_quantity' => $this->baseQuantity($quantity, $snapshot['conversion_factor']),
                ...$snapshot,
                'unit_cost' => $receiptLine->unit_cost,
                'total_cost' => $this->lineTotal($quantity, $receiptLine->unit_cost),
                'reason' => $line['reason'] ?? null,
            ]);
        }
    }

    private function assertReturnableBalance(SupplierReturnLine $line): void
    {
        $previousReturns = (float) SupplierReturnLine::query()
            ->where('goods_receipt_item_id', $line->goods_receipt_item_id)
            ->whereHas('supplierReturn', fn ($query) => $query->where('status', SupplierReturn::POSTED))
            ->sum('base_quantity');
        abort_if($previousReturns + (float) $line->base_quantity > (float) $line->goodsReceiptLine->base_quantity, 422, 'Return quantity exceeds the quantity still held from the goods receipt.');
    }
}
