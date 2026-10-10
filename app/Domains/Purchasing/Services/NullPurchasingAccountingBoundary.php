<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Purchasing\Contracts\PurchasingAccountingBoundary;
use App\Domains\Purchasing\Models\GoodsReceipt;
use App\Domains\Purchasing\Models\SupplierInvoice;
use App\Domains\Purchasing\Models\SupplierPayment;
use App\Domains\Purchasing\Models\SupplierReturn;

/**
 * Deliberate boundary while no accounting domain exists. It keeps P2P posting
 * code ready for a real journal integration without fabricating accounting data.
 */
class NullPurchasingAccountingBoundary implements PurchasingAccountingBoundary
{
    public function goodsReceiptPosted(GoodsReceipt $receipt): void {}
    public function supplierInvoicePosted(SupplierInvoice $invoice): void {}
    public function supplierPaymentPosted(SupplierPayment $payment): void {}
    public function supplierReturnPosted(SupplierReturn $return): void {}
}
