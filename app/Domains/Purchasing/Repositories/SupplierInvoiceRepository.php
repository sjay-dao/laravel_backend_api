<?php

namespace App\Domains\Purchasing\Repositories;

use App\Domains\Purchasing\Models\SupplierInvoice;
use App\Domains\Shared\Repositories\BaseRepository;

class SupplierInvoiceRepository extends BaseRepository
{
    protected array $with = ['supplier', 'lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit', 'lines.receiptAllocations.goodsReceiptLine.goodsReceipt', 'paymentAllocations.supplierPayment'];

    public function __construct() { $this->model = new SupplierInvoice(); }
}
