<?php

namespace App\Domains\Inventory\Repositories;

use App\Domains\Inventory\Models\InventoryObjectUnit;
use App\Domains\Shared\Repositories\BaseRepository;

class InventoryObjectUnitRepository extends BaseRepository
{

    public function __construct()
    {
        $this->model = new InventoryObjectUnit();
    }

    public function firstOrCreate(array $attributes, array $values = [])
    {
        return $this->model->firstOrCreate(
            $attributes,
            $values
        );
    }

    public function existsBaseUnit(
        int $inventoryObjectId,
        int $unitId
    ): bool {

        return $this->model
            ->where('inventory_object_id', $inventoryObjectId)
            ->where('unit_id', $unitId)
            ->exists();
    }

    public function byInventoryObject(int $inventoryObjectId)
    {
        return $this->model
            ->where('inventory_object_id', $inventoryObjectId)
            ->with('unit')
            ->get();
    }

    public function findByInventoryObjectAndUnit(
        int $inventoryObjectId,
        int $unitId
    )
    {
        return $this->model
            ->newQuery()
            ->where('inventory_object_id', $inventoryObjectId)
            ->where('unit_id', $unitId)
            ->first();
    }

    public function getActiveById(
        int $id
    )
    {
        return $this->model
            ->where('id', $id)
            ->where('is_active', true)
            ->first();
    }
}