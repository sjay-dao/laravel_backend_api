<?php

namespace App\Domains\Inventory\Repositories;

use App\Domains\Inventory\Models\InventoryMovementItem;
use App\Domains\Shared\Repositories\BaseRepository;

class InventoryMovementItemRepository extends BaseRepository
{
    protected array $with = [
        'inventoryObject',
        'unit',
    ];

    public function __construct()
    {
        $this->model = new InventoryMovementItem();
    }
}