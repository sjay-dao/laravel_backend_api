<?php

namespace App\Domains\Inventory\Controllers;

use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Inventory\Requests\StoreInventoryMovementRequest;
use App\Domains\Inventory\Requests\UpdateInventoryMovementRequest;
use App\Domains\Inventory\Resources\InventoryMovementResource;
use App\Domains\Inventory\Services\InventoryMovementService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class InventoryMovementController extends BaseApiController
{
    public function __construct(
        protected InventoryMovementService $service
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'inventory.movements.view');

        return $this->paginated(
            $this->service->paginate(),
            InventoryMovementResource::class,
            'Inventory Movements retrieved successfully.'
        );
    }

    public function store(StoreInventoryMovementRequest $request)
    {
        $this->authorizeAbility($request, 'inventory.movements.create');

        $movement = $this->service->create(
            $request->validated()
        );

        return $this->created(
            new InventoryMovementResource(
                $movement->load([
                    'movementType',
                    'warehouse',
                    'branch',
                    'items.inventoryObjectUnit.InventoryObject',
                    'items.inventoryObjectUnit.unit',
                ])
            ),
            'Inventory Movement created successfully.'
        );
    }

    public function show(
        Request $request,
        InventoryMovement $inventoryMovement
    ) {
        $this->authorizeAbility($request, 'inventory.movements.view');

        return $this->resource(
            new InventoryMovementResource(
                $inventoryMovement->load([
                    'movementType',
                    'warehouse',
                    'branch',
                    'items.inventoryObject',
                    'items.unit',
                ])
            ),
            'Inventory Movement retrieved successfully.'
        );
    }

    public function update(
        UpdateInventoryMovementRequest $request,
        InventoryMovement $movement
    ) {
        $this->authorizeAbility($request, 'inventory.movements.update');

        $movement = $this->service->update(
            $movement,
            $request->validated()
        );

        return $this->resource(
            new InventoryMovementResource(
                $movement->load([
                    'movementType',
                    'warehouse',
                    'branch',
                    'items.inventoryObject',
                    'items.unit',
                ])
            ),
            'Inventory Movement updated successfully.'
        );
    }

    public function destroy(Request $request)
    {
        $this->authorizeAbility($request, 'inventory.movements.delete');

        return response()->json([
            'success' => false,
            'message' => 'Inventory movements cannot be deleted.',
        ], 405);
    }

    public function stock(
        Request $request,
        int $inventoryObjectId
    ) {
        $this->authorizeAbility($request, 'inventory.movements.view');

        return $this->success(
            [
                'inventory_object_id' => $inventoryObjectId,
                'stock' => $this->service->getStock(
                    $inventoryObjectId
                ),
            ],
            'Current stock retrieved.'
        );
    }

    public function ledger(
        Request $request,
        int $inventoryObjectId
    ) {
        $this->authorizeAbility($request, 'inventory.movements.view');

        return $this->success(
            $this->service->ledger(
                $inventoryObjectId
            ),
            'Inventory ledger retrieved successfully.'
        );
    }
}