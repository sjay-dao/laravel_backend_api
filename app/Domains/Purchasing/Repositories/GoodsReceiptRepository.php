<?php

namespace App\Domains\Purchasing\Repositories;

use App\Domains\Purchasing\Models\GoodsReceipt;
use App\Domains\Shared\Repositories\BaseRepository;

class GoodsReceiptRepository extends BaseRepository
{
    protected array $with = ['purchaseOrder', 'supplier', 'branch', 'warehouse', 'receiver', 'inventoryMovement', 'lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit', 'lines.purchaseOrderLine', 'lines.valuation'];

    public function __construct() { $this->model = new GoodsReceipt(); }
}
