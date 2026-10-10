<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Inventory\Models\InventoryObjectUnit;
use App\Domains\Inventory\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderLine;
use App\Domains\Purchasing\Models\PurchaseRequisition;
use App\Domains\Purchasing\Models\PurchaseRequisitionLine;
use App\Domains\Purchasing\Repositories\PurchaseOrderRepository;
use App\Domains\Purchasing\Services\Concerns\BuildsPurchasingDocuments;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    use BuildsPurchasingDocuments;

    public function __construct(
        protected PurchaseOrderRepository $orders,
        protected PurchaseRequisitionService $requisitions,
    ) {}

    public function paginate(int $perPage = 15) { return $this->orders->paginate($perPage); }
    public function find(int $id) { return $this->orders->findById($id); }

    public function createDraft(array $data, int $actorId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $actorId) {
            $this->validateWarehouseBranch($data['warehouse_id'], $data['branch_id']);
            $order = $this->orders->create([
                ...$this->header($data),
                'document_number' => $this->documentNumber('PO', PurchaseOrder::class),
                'status' => PurchaseOrder::DRAFT,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $this->replaceLines($order, $data['lines']);

            return $order->fresh(['supplier', 'branch', 'warehouse', 'lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit', 'lines.requisitionAllocations.purchaseRequisitionLine']);
        });
    }

    public function updateDraft(PurchaseOrder $order, array $data, int $actorId): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $data, $actorId) {
            $this->requireStatus($order, [PurchaseOrder::DRAFT], 'Updating a purchase order');
            $this->validateWarehouseBranch($data['warehouse_id'], $data['branch_id']);
            $order->update([...$this->header($data), 'updated_by' => $actorId]);
            $order->lines()->delete();
            $this->replaceLines($order, $data['lines']);

            return $order->fresh(['lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit', 'lines.requisitionAllocations.purchaseRequisitionLine']);
        });
    }

    public function submit(PurchaseOrder $order, int $actorId): PurchaseOrder
    {
        $this->requireStatus($order, [PurchaseOrder::DRAFT], 'Submitting a purchase order');
        abort_if(! $order->lines()->exists(), 422, 'A purchase order requires at least one line.');
        $order->update(['status' => PurchaseOrder::SUBMITTED, 'submitted_by' => $actorId, 'submitted_at' => now()]);
        return $order->fresh();
    }

    public function approve(PurchaseOrder $order, int $actorId): PurchaseOrder
    {
        $this->requireStatus($order, [PurchaseOrder::SUBMITTED], 'Approving a purchase order');
        $order->update(['status' => PurchaseOrder::APPROVED, 'approved_by' => $actorId, 'approved_at' => now()]);
        return $order->fresh();
    }

    public function reject(PurchaseOrder $order, string $reason, int $actorId): PurchaseOrder
    {
        $this->requireStatus($order, [PurchaseOrder::SUBMITTED], 'Rejecting a purchase order');
        $order->update(['status' => PurchaseOrder::REJECTED, 'rejected_by' => $actorId, 'rejected_at' => now(), 'rejection_reason' => $reason]);
        return $order->fresh();
    }

    public function issue(PurchaseOrder $order, int $actorId): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $actorId) {
            $this->requireStatus($order, [PurchaseOrder::APPROVED], 'Issuing a purchase order');
            $order->update(['status' => PurchaseOrder::ISSUED, 'issued_by' => $actorId, 'issued_at' => now()]);
            $order->load('lines.requisitionAllocations.purchaseRequisitionLine.requisition');
            $order->lines->flatMap->requisitionAllocations
                ->map(fn ($allocation) => $allocation->purchaseRequisitionLine->requisition)
                ->unique('id')
                ->each(fn (PurchaseRequisition $requisition) => $this->requisitions->refreshFulfillment($requisition));

            return $order->fresh();
        });
    }

    public function cancel(PurchaseOrder $order, string $reason, int $actorId): PurchaseOrder
    {
        $this->requireStatus($order, [PurchaseOrder::DRAFT, PurchaseOrder::SUBMITTED, PurchaseOrder::APPROVED, PurchaseOrder::ISSUED, PurchaseOrder::PARTIALLY_RECEIVED], 'Cancelling a purchase order');
        $order->update(['status' => PurchaseOrder::CANCELLED, 'cancelled_by' => $actorId, 'cancelled_at' => now(), 'cancellation_reason' => $reason]);
        $order->load('lines.requisitionAllocations.purchaseRequisitionLine.requisition');
        $order->lines->flatMap->requisitionAllocations
            ->map(fn ($allocation) => $allocation->purchaseRequisitionLine->requisition)
            ->unique('id')
            ->each(fn (PurchaseRequisition $requisition) => $this->requisitions->refreshFulfillment($requisition));
        return $order->fresh();
    }

    public function refreshReceiptStatus(PurchaseOrder $order): void
    {
        if (! in_array($order->status, [PurchaseOrder::ISSUED, PurchaseOrder::PARTIALLY_RECEIVED, PurchaseOrder::RECEIVED], true)) {
            return;
        }

        $order->load('lines.goodsReceiptLines.goodsReceipt');
        $receivedAny = false;
        $complete = $order->lines->every(function (PurchaseOrderLine $line) use (&$receivedAny) {
            $received = $line->goodsReceiptLines
                ->filter(fn ($receiptLine) => $receiptLine->goodsReceipt->status === 'posted')
                ->sum(fn ($receiptLine) => (float) $receiptLine->base_quantity);
            $receivedAny = $receivedAny || $received > 0;
            return $received >= (float) $line->base_quantity;
        });

        $order->update(['status' => $complete ? PurchaseOrder::RECEIVED : ($receivedAny ? PurchaseOrder::PARTIALLY_RECEIVED : PurchaseOrder::ISSUED)]);
    }

    private function header(array $data): array
    {
        return [
            'supplier_id' => $data['supplier_id'], 'branch_id' => $data['branch_id'],
            'warehouse_id' => $data['warehouse_id'], 'order_date' => $data['order_date'],
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'currency_code' => $data['currency_code'] ?? 'PHP',
            'payment_terms' => $data['payment_terms'] ?? null,
            'supplier_reference' => $data['supplier_reference'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ];
    }

    private function validateWarehouseBranch(int $warehouseId, int $branchId): void
    {
        $warehouse = Warehouse::query()->findOrFail($warehouseId);
        abort_unless((int) $warehouse->branch_id === $branchId, 422, 'The delivery warehouse must belong to the purchase order branch.');
    }

    private function replaceLines(PurchaseOrder $order, array $lines): void
    {
        foreach ($lines as $line) {
            $objectUnit = InventoryObjectUnit::query()->with(['inventoryObject', 'unit'])->findOrFail($line['inventory_object_unit_id']);
            abort_unless((bool) $objectUnit->is_active, 422, 'An active Inventory Object Unit is required.');
            $snapshot = $this->snapshot($objectUnit);
            $quantity = $line['ordered_quantity'];
            $unitPrice = $line['unit_price'] ?? 0;
            $discount = $line['discount_amount'] ?? 0;
            $tax = $line['tax_amount'] ?? 0;
            $orderLine = $order->lines()->create([
                'inventory_object_unit_id' => $objectUnit->id,
                'ordered_quantity' => $quantity,
                'base_quantity' => $this->baseQuantity($quantity, $snapshot['conversion_factor']),
                ...$snapshot,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'line_total' => $this->lineTotal($quantity, $unitPrice, $discount, $tax),
                'promised_date' => $line['promised_date'] ?? null,
                'description' => $line['description'] ?? null,
            ]);

            foreach ($line['requisition_allocations'] ?? [] as $allocation) {
                $this->allocateRequisitionLine($orderLine, $allocation);
            }
        }
    }

    private function allocateRequisitionLine(PurchaseOrderLine $orderLine, array $allocation): void
    {
        $requisitionLine = PurchaseRequisitionLine::query()->with('requisition')->lockForUpdate()->findOrFail($allocation['purchase_requisition_line_id']);
        abort_unless($requisitionLine->requisition->status === PurchaseRequisition::APPROVED || $requisitionLine->requisition->status === PurchaseRequisition::PARTIALLY_ORDERED, 422, 'Purchase order allocations require an approved requisition.');
        $baseQuantity = $allocation['base_quantity'] ?? $this->baseQuantity(
            $allocation['allocated_quantity'],
            $requisitionLine->conversion_factor
        );
        $alreadyAllocated = (float) $requisitionLine->purchaseOrderAllocations()
            ->whereHas('purchaseOrderLine.purchaseOrder', fn ($query) => $query->whereNotIn('status', [PurchaseOrder::CANCELLED, PurchaseOrder::REJECTED]))
            ->sum('base_quantity');

        abort_if($alreadyAllocated + (float) $baseQuantity > (float) $requisitionLine->base_quantity, 422, 'Purchase order allocation exceeds the requisition balance.');
        abort_if((float) $baseQuantity > (float) $orderLine->base_quantity, 422, 'Requisition allocation exceeds the purchase order line quantity.');

        $orderLine->requisitionAllocations()->create([
            'purchase_requisition_line_id' => $requisitionLine->id,
            'allocated_quantity' => $allocation['allocated_quantity'],
            'base_quantity' => $baseQuantity,
            'conversion_factor' => $requisitionLine->conversion_factor,
        ]);
    }
}
