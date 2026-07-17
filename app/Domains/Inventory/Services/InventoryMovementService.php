<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Inventory\Repositories\InventoryMovementItemRepository;
use App\Domains\Inventory\Repositories\InventoryMovementRepository;
use App\Domains\Inventory\Repositories\InventoryMovementTypeRepository;
use App\Domains\Inventory\Repositories\InventoryObjectUnitRepository;
use App\Domains\Inventory\Requests\UpdateInventoryMovementRequest;
use App\Domains\Inventory\Resources\InventoryMovementResource;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryMovementService
{
    protected InventoryMovementRepository $movementRepository;

    protected InventoryMovementItemRepository $itemRepository;

    protected InventoryObjectUnitRepository $inventoryObjectUnitRepository;

    protected InventoryMovementTypeRepository $movementTypeRepository;

    public function __construct(
        InventoryMovementRepository $movementRepository,
        InventoryMovementItemRepository $itemRepository,
        InventoryObjectUnitRepository $inventoryObjectUnitRepository,
        InventoryMovementTypeRepository $movementTypeRepository
    ) {
        $this->movementRepository = $movementRepository;
        $this->itemRepository = $itemRepository;
        $this->inventoryObjectUnitRepository = $inventoryObjectUnitRepository;
        $this->movementTypeRepository = $movementTypeRepository;
    }

    public function paginate(int $perPage = 15)
    {
        return $this->movementRepository->paginate($perPage);
    }

    public function create(array $data): InventoryMovement
    {
        return DB::transaction(function () use ($data) {

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

                $conversion = $this->inventoryObjectUnitRepository
                    ->findByInventoryObjectAndUnit(
                        $item['inventory_object_id'],
                        $item['unit_id']
                    );

                if (!$conversion) {

                    throw new RuntimeException(
                        'No conversion factor found for Inventory Object ID '
                        . $item['inventory_object_id']
                    );

                }

                $factor = (float) $conversion->conversion_factor;

                $baseQuantity = $item['quantity'] * $factor;

                $this->itemRepository->create([

                    'inventory_movement_id' => $movement->id,

                    'inventory_object_id' => $item['inventory_object_id'],

                    'unit_id' => $item['unit_id'],

                    'quantity' => $item['quantity'],

                    'unit_conversion_factor' => $factor,

                    'base_quantity' => $baseQuantity,

                    'remarks' => $item['remarks'] ?? null,

                ]);
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
        array $data
    ): InventoryMovement
    {
        $movement->update($data);

        return $movement;
    }
    
   public function getCurrentStock(
        int $inventoryObjectId,
        ?int $warehouseId = null
    ): float {

        return $this->movementRepository
            ->getCurrentStock(
                $inventoryObjectId,
                $warehouseId
            );
    }
}