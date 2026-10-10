<?php

namespace App\Domains\Purchasing\Repositories;

use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Shared\Repositories\BaseRepository;

class PurchaseOrderRepository extends BaseRepository
{
    protected array $with = ['supplier', 'branch', 'warehouse', 'lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit', 'lines.requisitionAllocations.purchaseRequisitionLine'];

    public function __construct() { $this->model = new PurchaseOrder(); }
}
