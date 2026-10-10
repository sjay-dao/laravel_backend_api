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
        'items.inventoryObjectUnit.inventoryObject',
        'items.inventoryObjectUnit.unit',
    ];

    public function __construct()
    {
        $this->model = new InventoryMovement();
    }


    public function calculateStock(
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
                'inventory_object_units',
                'inventory_movement_items.inventory_object_unit_id',
                '=',
                'inventory_object_units.id'
            )

            ->join(
                'inventory_movement_types',
                'inventory_movements.movement_type_id',
                '=',
                'inventory_movement_types.id'
            )

            ->where(
                'inventory_object_units.inventory_object_id',
                $inventoryObjectId
            );

        if ($warehouseId) {

            $query->where(
                'inventory_movements.warehouse_id',
                $warehouseId
            );

        }

        return (float) ($query
            ->selectRaw("
                SUM(
                    CASE

                        WHEN inventory_movement_types.direction = 'IN'

                            THEN inventory_movement_items.quantity
                                * inventory_object_units.conversion_factor

                        WHEN inventory_movement_types.direction = 'OUT'

                            THEN -(inventory_movement_items.quantity
                                * inventory_object_units.conversion_factor)

                        ELSE 0

                    END
                ) AS stock
            ")
            ->value('stock') ?? 0);

    }

    public function getLedger(
        int $inventoryObjectId,
        ?int $warehouseId = null
    ){
        return $this->model
            ->newQuery()

            ->join(
                'inventory_movement_items',
                'inventory_movements.id',
                '=',
                'inventory_movement_items.inventory_movement_id'
            )

            ->join(
                'inventory_object_units',
                'inventory_movement_items.inventory_object_unit_id',
                '=',
                'inventory_object_units.id'
            )

            ->join(
                'inventory_movement_types',
                'inventory_movements.movement_type_id',
                '=',
                'inventory_movement_types.id'
            )

            ->where(
                'inventory_object_units.inventory_object_id',
                $inventoryObjectId
            )

            ->when(
                $warehouseId,
                fn ($q) => $q->where(
                    'inventory_movements.warehouse_id',
                    $warehouseId
                )
            )

            ->orderBy('inventory_movements.movement_date')

            ->orderBy('inventory_movements.id')

            ->select([

                'inventory_movements.id',

                'inventory_movements.transaction_no',

                'inventory_movements.movement_date',

                'inventory_movement_types.name as movement_type',

                'inventory_movement_types.direction',

                'inventory_movement_items.quantity',

                'inventory_object_units.conversion_factor',

                'inventory_movements.reference_type',

                'inventory_movements.reference_id',

                'inventory_movements.remarks'

            ])

            ->get();
    }

}