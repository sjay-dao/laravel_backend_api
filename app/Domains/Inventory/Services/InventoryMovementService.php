<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Inventory\Repositories\InventoryMovementItemRepository;
use App\Domains\Inventory\Repositories\InventoryMovementRepository;
use App\Domains\Inventory\Repositories\InventoryMovementTypeRepository;
use App\Domains\Inventory\Repositories\InventoryObjectUnitRepository;
use App\Domains\Inventory\Requests\UpdateInventoryMovementRequest;
use App\Domains\Inventory\Resources\InventoryMovementResource;
use App\Domains\Inventory\Repositories\InventoryReservationRepository;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryMovementService
{
    protected InventoryMovementRepository $movementRepository;

    protected InventoryMovementItemRepository $itemRepository;

    protected InventoryObjectUnitRepository $inventoryObjectUnitRepository;

    protected InventoryMovementTypeRepository $movementTypeRepository;


    protected InventoryReservationRepository $reservationRepository;

    public function __construct(
        InventoryMovementRepository $movementRepository,
        InventoryMovementItemRepository $itemRepository,
        InventoryObjectUnitRepository $inventoryObjectUnitRepository,
        InventoryMovementTypeRepository $movementTypeRepository,
        InventoryReservationRepository $reservationRepository

    ) {
        $this->movementRepository = $movementRepository;
        $this->itemRepository = $itemRepository;
        $this->inventoryObjectUnitRepository = $inventoryObjectUnitRepository;
        $this->movementTypeRepository = $movementTypeRepository;
        $this->reservationRepository = $reservationRepository;
    }

    public function paginate(int $perPage = 15)
    {
        return $this->movementRepository->paginate($perPage);
    }

    public function create(array $data): InventoryMovement
    {
        return DB::transaction(function () use ($data) {

        $this->validateStockAvailability(

            $data['movement_type_id'],

            $data['items'],

            $data['warehouse_id'] ?? null

        );
            /*
            |--------------------------------------------------------------------------
            | Create Header
            |--------------------------------------------------------------------------
            */

            $movement = $this->movementRepository->create([

                'transaction_no' => $this->generateTransactionNumber(),

                'movement_type_id' => $data['movement_type_id'],

                'movement_date' => $data['movement_date'],

                'branch_id' => $data['branch_id'] ?? null,

                'warehouse_id' => $data['warehouse_id'] ?? null,

                'reference_type' => $data['reference_type'] ?? null,

                'reference_id' => $data['reference_id'] ?? null,

                'remarks' => $data['remarks'] ?? null,

                'created_by' => Auth::id(),

            ]);
            /*
            |--------------------------------------------------------------------------
            | Save Items
            |--------------------------------------------------------------------------
            */

            foreach ($data['items'] as $item) {

               $objectUnit = $this->inventoryObjectUnitRepository
                    ->getActiveById(
                        $item['inventory_object_unit_id']
                    );

                if (!$objectUnit) {

                    throw new RuntimeException(
                        'Invalid or inactive Inventory Object Unit.'
                    );

                }

                $this->itemRepository->create([

                    'inventory_movement_id' => $movement->id,

                    'inventory_object_unit_id' => $objectUnit->id,

                    'quantity' => $item['quantity'],

                    'remarks' => $item['remarks'] ?? null,

                ]);

            }

            if (
                $movement
                    ->movementType
                    ->direction === 'OUT'
            ) {
                $this->reservationRepository->releaseByReference(
                        $movement->reference_type,
                        $movement->reference_id,
                        $objectUnit->inventory_object_id
                    );
            }
            return $movement->fresh();
        });
    }

    private function generateTransactionNumber(): string
    {
        $today = now()->format('Ymd');

        $count = $this->movementRepository
            ->paginate(100000)
            ->total() + 1;

        return sprintf(
            'TXN-%s-%06d',
            $today,
            $count
        );
    }

    public function update(
        InventoryMovement $movement,
        array $data): InventoryMovement 
    {

        return DB::transaction(function () use (
            $movement,
            $data
        ) {

            $this->validateStockAvailability(

                $data['movement_type_id'],

                $data['items'],

                $data['warehouse_id'] ?? null

            );

            $this->movementRepository->update(
                $movement,
                [

                    'movement_type_id' => $data['movement_type_id'],

                    'movement_date' => $data['movement_date'],

                    'branch_id' => $data['branch_id'] ?? null,

                    'warehouse_id' => $data['warehouse_id'] ?? null,

                    'reference_type' => $data['reference_type'] ?? null,

                    'reference_id' => $data['reference_id'] ?? null,

                    'remarks' => $data['remarks'] ?? null,

                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Replace Detail Items
            |--------------------------------------------------------------------------
            */

            $movement->items()->delete();

            foreach ($data['items'] as $item) {

               $objectUnit = $this->inventoryObjectUnitRepository
                    ->getActiveById(
                        $item['inventory_object_unit_id']
                    );

                if (!$objectUnit) {

                    throw new RuntimeException(
                        'Invalid or inactive Inventory Object Unit.'
                    );

                }

                $this->itemRepository->create([

                    'inventory_movement_id' => $movement->id,

                    'inventory_object_unit_id' => $objectUnit->id,

                    'quantity' => $item['quantity'],

                    'remarks' => $item['remarks'] ?? null,

                ]);

            }

            return $movement->fresh();

        });

    }
    
    public function getStock(
        int $inventoryObjectId,
        ?int $warehouseId = null )
    {

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
            'on_hand' => $onHand,
            'reserved' => $reserved,
            'available' => $onHand - $reserved,
        ];
    }

    private function validateStockAvailability(
        int $movementTypeId,
        array $items,
        ?int $warehouseId = null
    ): void {

        $movementType = $this->movementTypeRepository
            ->findById($movementTypeId);

        if ($movementType->direction !== 'OUT') {
            return;
        }

        foreach ($items as $item) {

            $objectUnit = $this->inventoryObjectUnitRepository
                ->getActiveById(
                    $item['inventory_object_unit_id']
                );

            if (!$objectUnit) {
                throw new \RuntimeException(
                    'Invalid Inventory Object Unit.'
                );
            }

            $currentStock = $this->getStock(
                $objectUnit->inventory_object_id,
                $warehouseId
            );

            $requested = $item['quantity']
                * $objectUnit->conversion_factor;

            if ($requested > $currentStock['available']) {

                throw new \RuntimeException(

                    sprintf(

                        '%s has only %.6f available.',

                        $objectUnit
                            ->inventoryObject
                            ->name,

                        $currentStock['available']

                    )

                );

            }
        }
    }

    public function ledger(
        int $inventoryObjectId,
        ?int $warehouseId = null
    )
    {
        $rows = $this->movementRepository
            ->getLedger(
                $inventoryObjectId,
                $warehouseId
            );

        $balance = 0;

        return $rows->map(function ($row) use (&$balance) {

            $baseQuantity =
                $row->quantity * $row->conversion_factor;

            if ($row->direction === 'IN') {

                $balance += $baseQuantity;

            } elseif ($row->direction === 'OUT') {

                $balance -= $baseQuantity;

            }

            return [

                'transaction_no' => $row->transaction_no,

                'date' => $row->movement_date,

                'movement_type' => $row->movement_type,

                'direction' => $row->direction,

                'quantity' => $row->quantity,

                'conversion_factor' => $row->conversion_factor,

                'base_quantity' => $baseQuantity,

                'running_balance' => $balance,

                'reference_type' => $row->reference_type,

                'reference_id' => $row->reference_id,

                'remarks' => $row->remarks,

            ];

        });

    }
}