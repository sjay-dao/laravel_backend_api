<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\InventoryReservation;
use App\Domains\Inventory\Repositories\InventoryReservationRepository;
use App\Domains\Shared\Services\BaseCrudService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryReservationService extends BaseCrudService
{
    private InventoryReservationRepository $reservationRepository;
    public function __construct(
        InventoryReservationRepository $reservationRepository,
    ) {
        $this->reservationRepository = $reservationRepository;
        parent::__construct($reservationRepository);
    }

    public function reserve(array $data): InventoryReservation
    {
        return DB::transaction(function () use ($data) {

            return $this->repository->create([

                'inventory_object_id' => $data['inventory_object_id'],

                'warehouse_id' => $data['warehouse_id'] ?? null,

                'quantity' => $data['quantity'],

                'reference_type' => $data['reference_type'] ?? null,

                'reference_id' => $data['reference_id'] ?? null,

                'remarks' => $data['remarks'] ?? null,

                'created_by' => Auth::id(),

            ]);

        });
    }

    public function release(
        InventoryReservation $reservation
    ): InventoryReservation {

        return DB::transaction(function () use ($reservation) {

            return $this->repository->update($reservation, [

                'is_released' => true,

                'released_by' => Auth::id(),

                'released_at' => now(),

            ]);

        });

    }

    public function reservedQuantity(
        int $inventoryObjectId,
        ?int $warehouseId = null
    ): float {

        return $this->reservationRepository->getReserved(
            $inventoryObjectId,
            $warehouseId
        );

    }
}