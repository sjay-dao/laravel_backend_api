<?php

namespace App\Domains\Inventory\Repositories;

use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Shared\Repositories\BaseRepository;

class InventoryMovementRepository extends BaseRepository
{
    protected array $with = [
        'movementType',
        'warehouse',
        'branch',
        'items.inventoryObject',
        'items.unit',
    ];

    public function __construct()
    {
        $this->model = new InventoryMovement();
    }
}