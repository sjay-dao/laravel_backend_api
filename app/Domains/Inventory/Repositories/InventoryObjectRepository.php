<?php

namespace App\Domains\Inventory\Repositories;

use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\Shared\Repositories\BaseRepository;

class InventoryObjectRepository extends BaseRepository
{
    protected array $with = [
        'category',
        'baseUnit',
        'units.unit',
    ];

    public function __construct()
    {
        $this->model = new InventoryObject();
    }
    
}