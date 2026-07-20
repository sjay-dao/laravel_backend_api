<?php

namespace App\Domains\Inventory\Controllers;

use App\Domains\Inventory\Models\InventoryMovement;
use App\Domains\Inventory\Requests\StoreInventoryMovementRequest;
use App\Domains\Inventory\Resources\InventoryMovementResource;
use App\Domains\Inventory\Services\InventoryMovementService;
use App\Domains\Shared\Controllers\BaseApiController;
use App\Domains\Shared\Responses\ApiPaginatedResponse;
use App\Domains\Shared\Responses\ApiResponse;
use App\Domains\Inventory\Requests\UpdateInventoryMovementRequest;

class InventoryMovementController extends BaseApiController
{
    protected InventoryMovementService $service;

    public function __construct(
        InventoryMovementService $service
    ) {
        $this->service = $service;
    }

    public function index()
    {
        return ApiPaginatedResponse::make(
            $this->service->paginate(),
            'Inventory Movements retrieved successfully.'
        );
    }

    public function store(
        StoreInventoryMovementRequest $request
    ) {
        $movement = $this->service->create(
            $request->validated()
        );

        return ApiResponse::created(
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
        InventoryMovement $inventoryMovement
    ) {
        return ApiResponse::success(
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
    )
    {
        $movement = $this->service->update(
            $movement,
            $request->validated()
        );

        return ApiResponse::success(
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

    public function destroy()
    {
        return ApiResponse::error(
            'Inventory movements cannot be deleted.',
            null,
            405
        );
    }

    public function stock(
        int $inventoryObjectId
    )
    {
        return ApiResponse::success(

            [

                'inventory_object_id' => $inventoryObjectId,

                'stock' => $this->service
                    ->getStock(
                        $inventoryObjectId
                    )

            ],

            'Current stock retrieved.'

        );
    }

    public function ledger(
        int $inventoryObjectId
    )
    {
        return ApiResponse::success(

            $this->service->ledger(
                $inventoryObjectId
            ),

            'Inventory ledger retrieved successfully.'

        );
    }
    
}