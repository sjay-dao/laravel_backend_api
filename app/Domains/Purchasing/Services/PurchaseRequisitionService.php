<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Inventory\Models\InventoryObjectUnit;
use App\Domains\Purchasing\Models\PurchaseRequisition;
use App\Domains\Purchasing\Models\PurchaseRequisitionLine;
use App\Domains\Purchasing\Repositories\PurchaseRequisitionRepository;
use App\Domains\Purchasing\Services\Concerns\BuildsPurchasingDocuments;
use Illuminate\Support\Facades\DB;

class PurchaseRequisitionService
{
    use BuildsPurchasingDocuments;

    public function __construct(protected PurchaseRequisitionRepository $requisitions) {}

    public function paginate(int $perPage = 15) { return $this->requisitions->paginate($perPage); }
    public function find(int $id) { return $this->requisitions->findById($id); }

    public function createDraft(array $data, int $actorId): PurchaseRequisition
    {
        return DB::transaction(function () use ($data, $actorId) {
            $requisition = $this->requisitions->create([
                ...$this->header($data),
                'document_number' => $this->documentNumber('PR', PurchaseRequisition::class),
                'status' => PurchaseRequisition::DRAFT,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->replaceLines($requisition, $data['lines']);

            return $requisition->fresh(['lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit']);
        });
    }

    public function updateDraft(PurchaseRequisition $requisition, array $data, int $actorId): PurchaseRequisition
    {
        return DB::transaction(function () use ($requisition, $data, $actorId) {
            $this->requireStatus($requisition, [PurchaseRequisition::DRAFT], 'Updating a requisition');
            $requisition->update([...$this->header($data), 'updated_by' => $actorId]);
            $this->replaceLines($requisition, $data['lines']);

            return $requisition->fresh(['lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit']);
        });
    }

    public function submit(PurchaseRequisition $requisition, int $actorId): PurchaseRequisition
    {
        $this->requireStatus($requisition, [PurchaseRequisition::DRAFT], 'Submitting a requisition');
        abort_if(! $requisition->lines()->exists(), 422, 'A requisition requires at least one line.');

        $requisition->update(['status' => PurchaseRequisition::SUBMITTED, 'submitted_by' => $actorId, 'submitted_at' => now()]);
        return $requisition->fresh();
    }

    public function approve(PurchaseRequisition $requisition, int $actorId): PurchaseRequisition
    {
        $this->requireStatus($requisition, [PurchaseRequisition::SUBMITTED], 'Approving a requisition');
        $requisition->update(['status' => PurchaseRequisition::APPROVED, 'approved_by' => $actorId, 'approved_at' => now()]);
        return $requisition->fresh();
    }

    public function reject(PurchaseRequisition $requisition, string $reason, int $actorId): PurchaseRequisition
    {
        $this->requireStatus($requisition, [PurchaseRequisition::SUBMITTED], 'Rejecting a requisition');
        $requisition->update(['status' => PurchaseRequisition::REJECTED, 'rejected_by' => $actorId, 'rejected_at' => now(), 'rejection_reason' => $reason]);
        return $requisition->fresh();
    }

    public function cancel(PurchaseRequisition $requisition, string $reason, int $actorId): PurchaseRequisition
    {
        $this->requireStatus($requisition, [PurchaseRequisition::DRAFT, PurchaseRequisition::SUBMITTED, PurchaseRequisition::APPROVED, PurchaseRequisition::PARTIALLY_ORDERED], 'Cancelling a requisition');
        $requisition->update(['status' => PurchaseRequisition::CANCELLED, 'cancelled_by' => $actorId, 'cancelled_at' => now(), 'cancellation_reason' => $reason]);
        return $requisition->fresh();
    }

    public function refreshFulfillment(PurchaseRequisition $requisition): void
    {
        if (! in_array($requisition->status, [PurchaseRequisition::APPROVED, PurchaseRequisition::PARTIALLY_ORDERED, PurchaseRequisition::ORDERED], true)) {
            return;
        }

        $lines = $requisition->lines()->with('purchaseOrderAllocations.purchaseOrderLine.purchaseOrder')->get();
        $ordered = $lines->every(function (PurchaseRequisitionLine $line) {
            $allocated = $line->purchaseOrderAllocations
                ->filter(fn ($allocation) => in_array($allocation->purchaseOrderLine->purchaseOrder->status, [
                    'issued', 'partially_received', 'received', 'closed',
                ], true))
                ->sum(fn ($allocation) => (float) $allocation->base_quantity);

            return $allocated >= (float) $line->base_quantity;
        });
        $hasOrder = $lines->contains(fn (PurchaseRequisitionLine $line) => $line->purchaseOrderAllocations->isNotEmpty());

        $requisition->update(['status' => $ordered ? PurchaseRequisition::ORDERED : ($hasOrder ? PurchaseRequisition::PARTIALLY_ORDERED : PurchaseRequisition::APPROVED)]);
    }

    private function header(array $data): array
    {
        return [
            'branch_id' => $data['branch_id'], 'requester_id' => $data['requester_id'],
            'request_date' => $data['request_date'], 'needed_by_date' => $data['needed_by_date'] ?? null,
            'purpose' => $data['purpose'] ?? null,
        ];
    }

    private function replaceLines(PurchaseRequisition $requisition, array $lines): void
    {
        $requisition->lines()->delete();

        foreach ($lines as $line) {
            $objectUnit = InventoryObjectUnit::query()->with(['inventoryObject', 'unit'])->findOrFail($line['inventory_object_unit_id']);
            abort_unless((bool) $objectUnit->is_active, 422, 'An active Inventory Object Unit is required.');
            $snapshot = $this->snapshot($objectUnit);
            $quantity = $line['requested_quantity'];

            $requisition->lines()->create([
                'inventory_object_unit_id' => $objectUnit->id,
                'suggested_supplier_id' => $line['suggested_supplier_id'] ?? null,
                'requested_quantity' => $quantity,
                'base_quantity' => $this->baseQuantity($quantity, $snapshot['conversion_factor']),
                ...$snapshot,
                'description' => $line['description'] ?? null,
            ]);
        }
    }
}
