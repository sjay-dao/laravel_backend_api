<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Inventory\Models\InventoryObjectUnit;
use App\Domains\Purchasing\Contracts\PurchasingAccountingBoundary;
use App\Domains\Purchasing\Models\GoodsReceipt;
use App\Domains\Purchasing\Models\GoodsReceiptLine;
use App\Domains\Purchasing\Models\SupplierInvoice;
use App\Domains\Purchasing\Models\SupplierInvoiceLine;
use App\Domains\Purchasing\Models\SupplierInvoiceLineReceiptAllocation;
use App\Domains\Purchasing\Models\SupplierPayment;
use App\Domains\Purchasing\Repositories\SupplierInvoiceRepository;
use App\Domains\Purchasing\Services\Concerns\BuildsPurchasingDocuments;
use Illuminate\Support\Facades\DB;

class SupplierInvoiceService
{
    use BuildsPurchasingDocuments;

    public function __construct(
        protected SupplierInvoiceRepository $invoices,
        protected PurchasingAccountingBoundary $accounting,
    ) {}

    public function paginate(int $perPage = 15) { return $this->invoices->paginate($perPage); }
    public function find(int $id) { return $this->invoices->findById($id); }

    public function createDraft(array $data, int $actorId): SupplierInvoice
    {
        return DB::transaction(function () use ($data, $actorId) {
            $invoice = SupplierInvoice::query()->create([
                ...$this->header($data),
                'document_number' => $this->documentNumber('SI', SupplierInvoice::class),
                'status' => SupplierInvoice::DRAFT,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $this->replaceLines($invoice, $data['lines']);
            $this->recalculateTotals($invoice);
            return $invoice->fresh(['supplier', 'lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit', 'lines.receiptAllocations.goodsReceiptLine.goodsReceipt']);
        });
    }

    public function updateDraft(SupplierInvoice $invoice, array $data, int $actorId): SupplierInvoice
    {
        return DB::transaction(function () use ($invoice, $data, $actorId) {
            $this->requireStatus($invoice, [SupplierInvoice::DRAFT], 'Updating a supplier invoice');
            $invoice->update([...$this->header($data), 'updated_by' => $actorId]);
            $invoice->lines()->delete();
            $this->replaceLines($invoice, $data['lines']);
            $this->recalculateTotals($invoice);
            return $invoice->fresh(['lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit', 'lines.receiptAllocations.goodsReceiptLine.goodsReceipt']);
        });
    }

    public function match(SupplierInvoice $invoice, int $actorId): SupplierInvoice
    {
        return DB::transaction(function () use ($invoice, $actorId) {
            $this->requireStatus($invoice, [SupplierInvoice::DRAFT], 'Matching a supplier invoice');
            $invoice->load('lines.inventoryObjectUnit.inventoryObject', 'lines.receiptAllocations.goodsReceiptLine.goodsReceipt');
            abort_if($invoice->lines->isEmpty(), 422, 'A supplier invoice requires at least one line.');

            $currentReceiptQuantities = [];
            foreach ($invoice->lines as $line) {
                $allocations = $line->receiptAllocations;
                if ($line->inventory_object_unit_id && (bool) $line->inventoryObjectUnit->inventoryObject->track_inventory && $allocations->isEmpty()) {
                    abort_unless($invoice->allow_without_receipt && filled($invoice->receipt_exception_reason), 422, 'Stock invoice lines require a posted goods receipt allocation or an explicit exception reason.');
                }
                foreach ($allocations as $allocation) {
                    $receiptLine = $allocation->goodsReceiptLine;
                    abort_unless($receiptLine->goodsReceipt->status === GoodsReceipt::POSTED, 422, 'Invoice allocations require posted goods receipt lines.');
                    abort_unless((int) $receiptLine->goodsReceipt->supplier_id === (int) $invoice->supplier_id, 422, 'Invoice and goods receipt suppliers must match.');
                    abort_unless(! $line->inventory_object_unit_id || (int) $line->inventory_object_unit_id === (int) $receiptLine->inventory_object_unit_id, 422, 'Invoice and receipt Inventory Object UOMs must match.');
                    $currentReceiptQuantities[$receiptLine->id] = ($currentReceiptQuantities[$receiptLine->id] ?? 0) + (float) $allocation->base_quantity;

                    $appliedAmount = $this->lineTotal($allocation->matched_quantity, $line->unit_price);
                    $receiptAmount = $this->lineTotal($allocation->matched_quantity, $receiptLine->unit_cost);
                    $allocation->update([
                        'applied_unit_cost' => $line->unit_price,
                        'applied_amount' => $appliedAmount,
                        'price_variance_amount' => number_format(round((float) $appliedAmount - (float) $receiptAmount, 6), 6, '.', ''),
                    ]);
                }
            }

            foreach ($currentReceiptQuantities as $receiptLineId => $currentQuantity) {
                $receiptLine = GoodsReceiptLine::query()->lockForUpdate()->findOrFail($receiptLineId);
                $previouslyInvoiced = (float) SupplierInvoiceLineReceiptAllocation::query()
                    ->where('goods_receipt_item_id', $receiptLineId)
                    ->whereHas('supplierInvoiceLine.supplierInvoice', function ($query) use ($invoice) {
                        $query->where('id', '!=', $invoice->id)
                            ->whereIn('status', [SupplierInvoice::MATCHED, SupplierInvoice::APPROVED, SupplierInvoice::POSTED, SupplierInvoice::PARTIALLY_PAID, SupplierInvoice::PAID]);
                    })
                    ->sum('base_quantity');
                abort_if($previouslyInvoiced + $currentQuantity > (float) $receiptLine->base_quantity, 422, 'Invoice quantity exceeds the received quantity.');
            }

            $invoice->update(['status' => SupplierInvoice::MATCHED, 'matched_by' => $actorId, 'matched_at' => now()]);
            return $invoice->fresh();
        });
    }

    public function approve(SupplierInvoice $invoice, int $actorId): SupplierInvoice
    {
        $this->requireStatus($invoice, [SupplierInvoice::MATCHED], 'Approving a supplier invoice');
        $invoice->update(['status' => SupplierInvoice::APPROVED, 'approved_by' => $actorId, 'approved_at' => now()]);
        return $invoice->fresh();
    }

    public function post(SupplierInvoice $invoice, int $actorId): SupplierInvoice
    {
        return DB::transaction(function () use ($invoice, $actorId) {
            $this->requireStatus($invoice, [SupplierInvoice::APPROVED], 'Posting a supplier invoice');
            $invoice->update(['status' => SupplierInvoice::POSTED, 'posted_by' => $actorId, 'posted_at' => now()]);
            $invoice = $invoice->fresh(['lines.receiptAllocations.goodsReceiptLine']);
            $this->accounting->supplierInvoicePosted($invoice);
            return $invoice;
        });
    }

    public function cancel(SupplierInvoice $invoice, string $reason, int $actorId): SupplierInvoice
    {
        $this->requireStatus($invoice, [SupplierInvoice::DRAFT, SupplierInvoice::MATCHED, SupplierInvoice::APPROVED], 'Cancelling a supplier invoice');
        $invoice->update(['status' => SupplierInvoice::CANCELLED, 'cancelled_by' => $actorId, 'cancelled_at' => now(), 'cancellation_reason' => $reason]);
        return $invoice->fresh();
    }

    public function outstanding(SupplierInvoice $invoice): float
    {
        $paid = (float) $invoice->paymentAllocations()
            ->whereHas('supplierPayment', fn ($query) => $query->where('status', SupplierPayment::POSTED))
            ->sum('allocated_amount');
        return max(0, round((float) $invoice->total_amount - $paid, 6));
    }

    public function refreshPaymentStatus(SupplierInvoice $invoice): void
    {
        if (! in_array($invoice->status, [SupplierInvoice::POSTED, SupplierInvoice::PARTIALLY_PAID, SupplierInvoice::PAID], true)) {
            return;
        }
        $outstanding = $this->outstanding($invoice);
        $invoice->update(['status' => $outstanding <= 0 ? SupplierInvoice::PAID : ($outstanding < (float) $invoice->total_amount ? SupplierInvoice::PARTIALLY_PAID : SupplierInvoice::POSTED)]);
    }

    private function header(array $data): array
    {
        return [
            'supplier_id' => $data['supplier_id'], 'supplier_invoice_number' => $data['supplier_invoice_number'],
            'invoice_date' => $data['invoice_date'], 'due_date' => $data['due_date'] ?? null,
            'currency_code' => $data['currency_code'] ?? 'PHP',
            'allow_without_receipt' => $data['allow_without_receipt'] ?? false,
            'receipt_exception_reason' => $data['receipt_exception_reason'] ?? null,
        ];
    }

    private function replaceLines(SupplierInvoice $invoice, array $lines): void
    {
        foreach ($lines as $line) {
            $objectUnit = isset($line['inventory_object_unit_id'])
                ? InventoryObjectUnit::query()->with(['inventoryObject', 'unit'])->findOrFail($line['inventory_object_unit_id'])
                : null;
            $quantity = $line['quantity'] ?? 1;
            $unitPrice = $line['unit_price'];
            $discount = $line['discount_amount'] ?? 0;
            $tax = $line['tax_amount'] ?? 0;
            $snapshot = $objectUnit ? $this->snapshot($objectUnit) : [];
            $invoiceLine = $invoice->lines()->create([
                'inventory_object_unit_id' => $objectUnit?->id,
                'description' => $line['description'],
                'account_treatment' => $line['account_treatment'] ?? 'inventory_or_expense',
                'quantity' => $quantity,
                'base_quantity' => $objectUnit ? $this->baseQuantity($quantity, $snapshot['conversion_factor']) : null,
                ...$snapshot,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'line_total' => $this->lineTotal($quantity, $unitPrice, $discount, $tax),
            ]);

            foreach ($line['receipt_allocations'] ?? [] as $allocation) {
                $receiptLine = GoodsReceiptLine::query()->findOrFail($allocation['goods_receipt_item_id']);
                $matchedQuantity = $allocation['matched_quantity'];
                $conversion = $objectUnit?->conversion_factor ?? $receiptLine->conversion_factor;
                $invoiceLine->receiptAllocations()->create([
                    'goods_receipt_item_id' => $receiptLine->id,
                    'matched_quantity' => $matchedQuantity,
                    'base_quantity' => $this->baseQuantity($matchedQuantity, $conversion),
                    'applied_unit_cost' => $unitPrice,
                    'applied_amount' => $this->lineTotal($matchedQuantity, $unitPrice),
                    'price_variance_amount' => 0,
                ]);
            }
        }
    }

    private function recalculateTotals(SupplierInvoice $invoice): void
    {
        $invoice->load('lines');
        $invoice->update([
            'subtotal' => $invoice->lines->sum(fn (SupplierInvoiceLine $line) => (float) $line->quantity * (float) $line->unit_price),
            'discount_total' => $invoice->lines->sum('discount_amount'),
            'tax_total' => $invoice->lines->sum('tax_amount'),
            'total_amount' => $invoice->lines->sum('line_total'),
        ]);
    }
}
