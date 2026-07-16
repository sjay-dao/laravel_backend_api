<?php

namespace App\Domains\Inventory\Repositories;

use App\Domains\Inventory\Models\InventoryMovementType;
use App\Domains\Shared\Repositories\BaseRepository;

class InventoryMovementTypeRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new InventoryMovementType();
    }
}