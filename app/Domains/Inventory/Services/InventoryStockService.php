<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Repositories\InventoryMovementRepository;
use App\Domains\Inventory\Repositories\InventoryObjectRepository;
use App\Domains\Inventory\Repositories\InventoryReservationRepository;

class InventoryStockService
{
    protected InventoryObjectRepository $objectRepository;

    protected InventoryMovementRepository $movementRepository;

    protected InventoryReservationRepository $reservationRepository;

    public function __construct(
        InventoryObjectRepository $objectRepository,
        InventoryMovementRepository $movementRepository,
        InventoryReservationRepository $reservationRepository
    ) {
        $this->objectRepository = $objectRepository;
        $this->movementRepository = $movementRepository;
        $this->reservationRepository = $reservationRepository;
    }

    public function getStock(
        int $inventoryObjectId,
        ?int $warehouseId = null
    ): array {

        $object = $this->objectRepository
            ->findById($inventoryObjectId);

        $onHand = $this->movementRepository
            ->calculateStock(
                $inventoryObjectId,
                $warehouseId
            );

        $reserved = $this->reservationRepository
            ->getReserved(
                $inventoryObjectId,
                $warehouseId
            );

        return [

            'inventory_object_id' => $object->id,

            'code' => $object->code,

            'name' => $object->name,

            'base_unit' => $object->baseUnit?->code,

            'on_hand' => $onHand,
            'reserved' => $reserved,
            'available' => $onHand - $reserved,

        ];

    }
}