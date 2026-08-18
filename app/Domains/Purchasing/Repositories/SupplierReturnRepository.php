<?php

namespace App\Domains\Purchasing\Repositories;

use App\Domains\Purchasing\Models\SupplierReturn;
use App\Domains\Shared\Repositories\BaseRepository;

class SupplierReturnRepository extends BaseRepository
{
    protected array $with = ['supplier', 'branch', 'warehouse', 'goodsReceipt', 'inventoryMovement', 'lines.goodsReceiptLine', 'lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit'];

    public function __construct() { $this->model = new SupplierReturn(); }
}
