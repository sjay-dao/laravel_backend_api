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

    public function getCurrentStock(
        int $inventoryObjectId,
        ?int $warehouseId = null
    ): float {

        $query = $this->model
            ->newQuery()
            ->join(
                'inventory_movement_items',
                'inventory_movements.id',
                '=',
                'inventory_movement_items.inventory_movement_id'
            )
            ->join(
                'inventory_movement_types',
                'inventory_movements.movement_type_id',
                '=',
                'inventory_movement_types.id'
            )
            ->where(
                'inventory_movement_items.inventory_object_id',
                $inventoryObjectId
            );

        if ($warehouseId) {

            $query->where(
                'inventory_movements.warehouse_id',
                $warehouseId
            );

        }

        return (float) $query
            ->selectRaw("
                SUM(
                    CASE

                        WHEN inventory_movement_types.direction='IN'

                            THEN inventory_movement_items.base_quantity

                        WHEN inventory_movement_types.direction='OUT'

                            THEN -inventory_movement_items.base_quantity

                        ELSE 0

                    END
                ) as stock
            ")
            ->value('stock') ?? 0;
    }
}