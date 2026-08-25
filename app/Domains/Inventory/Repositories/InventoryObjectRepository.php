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

    public function options()
    {
        return $this->model
            ->with([
                'category:id,name',
                'baseUnit:id,name',
            ])
            ->where('is_active', true)
            ->where('is_sellable', true)
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'inventory_category_id',
                'unit_id',
                'track_inventory',
                'is_sellable',
            ]);
    }
    
}