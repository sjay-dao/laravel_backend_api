<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Purchasing\Contracts\PurchasingAccountingBoundary;
use App\Domains\Purchasing\Models\SupplierInvoice;
use App\Domains\Purchasing\Models\SupplierPayment;
use App\Domains\Purchasing\Repositories\SupplierPaymentRepository;
use App\Domains\Purchasing\Services\Concerns\BuildsPurchasingDocuments;
use Illuminate\Support\Facades\DB;

class SupplierPaymentService
{
    use BuildsPurchasingDocuments;

    public function __construct(
        protected SupplierPaymentRepository $payments,
        protected SupplierInvoiceService $invoices,
        protected PurchasingAccountingBoundary $accounting,
    ) {}

    public function paginate(int $perPage = 15) { return $this->payments->paginate($perPage); }
    public function find(int $id) { return $this->payments->findById($id); }

    public function createDraft(array $data, int $actorId): SupplierPayment
    {
        return DB::transaction(function () use ($data, $actorId) {
            $payment = SupplierPayment::query()->create([
                'document_number' => $this->documentNumber('PAY', SupplierPayment::class),
                'supplier_id' => $data['supplier_id'], 'payment_date' => $data['payment_date'],
                'currency_code' => $data['currency_code'] ?? 'PHP',
                'payment_method' => $data['payment_method'],
                'cash_bank_account_reference' => $data['cash_bank_account_reference'],
                'external_reference' => $data['external_reference'] ?? null,
                'total_amount' => $data['total_amount'], 'remarks' => $data['remarks'] ?? null,
                'status' => SupplierPayment::DRAFT, 'created_by' => $actorId, 'updated_by' => $actorId,
            ]);
            $this->replaceAllocations($payment, $data['allocations']);
            return $payment->fresh(['supplier', 'allocations.supplierInvoice']);
        });
    }

    public function approve(SupplierPayment $payment, int $actorId): SupplierPayment
    {
        $this->requireStatus($payment, [SupplierPayment::DRAFT], 'Approving a supplier payment');
        abort_if(! $payment->allocations()->exists(), 422, 'A supplier payment requires at least one invoice allocation.');
        $payment->update(['status' => SupplierPayment::APPROVED, 'approved_by' => $actorId, 'approved_at' => now()]);
        return $payment->fresh();
    }

    public function post(SupplierPayment $payment, int $actorId): SupplierPayment
    {
        return DB::transaction(function () use ($payment, $actorId) {
            $this->requireStatus($payment, [SupplierPayment::APPROVED], 'Posting a supplier payment');
            $payment->load('allocations.supplierInvoice');
            foreach ($payment->allocations as $allocation) {
                $invoice = SupplierInvoice::query()->lockForUpdate()->findOrFail($allocation->supplier_invoice_id);
                abort_unless((int) $invoice->supplier_id === (int) $payment->supplier_id, 422, 'Payment and invoice suppliers must match.');
                abort_unless(in_array($invoice->status, [SupplierInvoice::POSTED, SupplierInvoice::PARTIALLY_PAID], true), 422, 'Payments can only be allocated to posted supplier invoices.');
                abort_if((float) $allocation->allocated_amount > $this->invoices->outstanding($invoice), 422, 'Payment allocation exceeds the supplier invoice balance.');
            }
            $payment->update(['status' => SupplierPayment::POSTED, 'posted_by' => $actorId, 'posted_at' => now()]);
            foreach ($payment->allocations as $allocation) {
                $this->invoices->refreshPaymentStatus(SupplierInvoice::query()->findOrFail($allocation->supplier_invoice_id));
            }
            $payment = $payment->fresh(['allocations.supplierInvoice']);
            $this->accounting->supplierPaymentPosted($payment);
            return $payment;
        });
    }

    public function reverse(SupplierPayment $payment, string $reason, int $actorId): SupplierPayment
    {
        return DB::transaction(function () use ($payment, $reason, $actorId) {
            $this->requireStatus($payment, [SupplierPayment::POSTED], 'Reversing a supplier payment');
            $payment->update(['status' => SupplierPayment::REVERSED, 'reversed_by' => $actorId, 'reversed_at' => now(), 'reversal_reason' => $reason]);
            $payment->load('allocations');
            foreach ($payment->allocations as $allocation) {
                $this->invoices->refreshPaymentStatus(SupplierInvoice::query()->findOrFail($allocation->supplier_invoice_id));
            }
            return $payment->fresh();
        });
    }

    private function replaceAllocations(SupplierPayment $payment, array $allocations): void
    {
        $allocatedTotal = 0;
        foreach ($allocations as $allocation) {
            $invoice = SupplierInvoice::query()->lockForUpdate()->findOrFail($allocation['supplier_invoice_id']);
            abort_unless((int) $invoice->supplier_id === (int) $payment->supplier_id, 422, 'Payment and invoice suppliers must match.');
            abort_unless(in_array($invoice->status, [SupplierInvoice::POSTED, SupplierInvoice::PARTIALLY_PAID], true), 422, 'Payment allocations require posted supplier invoices.');
            $amount = (float) $allocation['allocated_amount'];
            abort_if($amount > $this->invoices->outstanding($invoice), 422, 'Payment allocation exceeds the supplier invoice balance.');
            $payment->allocations()->create(['supplier_invoice_id' => $invoice->id, 'allocated_amount' => $amount]);
            $allocatedTotal += $amount;
        }
        abort_if(round($allocatedTotal, 6) !== round((float) $payment->total_amount, 6), 422, 'Payment total must equal the sum of its invoice allocations.');
    }
}
