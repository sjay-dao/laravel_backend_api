<?php

namespace App\Domains\Purchasing\Contracts;

use App\Domains\Purchasing\Models\GoodsReceipt;
use App\Domains\Purchasing\Models\SupplierInvoice;
use App\Domains\Purchasing\Models\SupplierPayment;
use App\Domains\Purchasing\Models\SupplierReturn;

interface PurchasingAccountingBoundary
{
    public function goodsReceiptPosted(GoodsReceipt $receipt): void;
    public function supplierInvoicePosted(SupplierInvoice $invoice): void;
    public function supplierPaymentPosted(SupplierPayment $payment): void;
    public function supplierReturnPosted(SupplierReturn $return): void;
}
