<?php

namespace App\Domains\Purchasing\Repositories;

use App\Domains\Purchasing\Models\PurchaseRequisition;
use App\Domains\Shared\Repositories\BaseRepository;

class PurchaseRequisitionRepository extends BaseRepository
{
    protected array $with = ['branch', 'requester', 'lines.inventoryObjectUnit.inventoryObject', 'lines.inventoryObjectUnit.unit'];

    public function __construct() { $this->model = new PurchaseRequisition(); }
}
