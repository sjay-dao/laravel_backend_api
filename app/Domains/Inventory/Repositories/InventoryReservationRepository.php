<?php

namespace App\Domains\Inventory\Repositories;

use App\Domains\Inventory\Models\InventoryReservation;
use App\Domains\Shared\Repositories\BaseRepository;

class InventoryReservationRepository
    extends BaseRepository
{
    public function __construct()
    {
        $this->model =
            new InventoryReservation();
    }

    public function getReserved(
        int $inventoryObjectId,
        ?int $warehouseId = null
    ): float {

        return (float)$this->query()

            ->where(
                'inventory_object_id',
                $inventoryObjectId
            )

            ->when(
                $warehouseId,
                fn($q)=>$q->where(
                    'warehouse_id',
                    $warehouseId
                )
            )

            ->where(
                'is_released',
                false
            )

            ->sum('quantity');

    }

      public function releaseByReference(
        string $referenceType,
        int $referenceId,
        int $inventoryObjectId): void {

        $this->query()

            ->where(

                'reference_type',

                $referenceType

            )

            ->where(

                'reference_id',

                $referenceId

            )

            ->where(

                'inventory_object_id',

                $inventoryObjectId

            )

            ->where(

                'is_released',

                false

            )

            ->update([

                'is_released' => true,

                'released_at' => now(),

                'released_by' => auth()->id,

            ]);

    }
}