<?php

namespace App\Domains\Inventory\Controllers;

use App\Domains\Inventory\Models\InventoryObjectUnit;
use App\Domains\Inventory\Requests\StoreInventoryObjectUnitRequest;
use App\Domains\Inventory\Requests\UpdateInventoryObjectUnitRequest;
use App\Domains\Inventory\Resources\InventoryObjectUnitResource;
use App\Domains\Inventory\Services\InventoryObjectUnitService;
use App\Domains\Shared\Controllers\BaseApiController;
use App\Domains\Shared\Responses\ApiResponse;
use App\Domains\Shared\Responses\ApiPaginatedResponse;


class InventoryObjectUnitController extends BaseApiController
{
    protected InventoryObjectUnitService $service;

    public function __construct(InventoryObjectUnitService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return ApiPaginatedResponse::make(
            $this->service->paginate(),
            "Inventory Object Units retrieved successfully."
        );
    }

    public function store(
        StoreInventoryObjectUnitRequest $request
    )
    {
        $unit = $this->service->create(
            $request->validated()
        );

        return ApiResponse::created(
            new InventoryObjectUnitResource($unit),
            'Inventory Object Unit created successfully.'
        );
    }

    public function show(
        InventoryObjectUnit $inventoryObjectUnit
    )
    {
        return ApiResponse::success(
            new InventoryObjectUnitResource($inventoryObjectUnit),
            'Inventory Object Unit retrieved successfully.'
        );
    }

    public function update(
        UpdateInventoryObjectUnitRequest $request,
        InventoryObjectUnit $object_unit
    )
    {
        $unit = $this->service->update(
            $object_unit,
            $request->validated()
        );

        return ApiResponse::success(
            new InventoryObjectUnitResource($unit),
            'Inventory Object Unit updated successfully.'
        );
    }

    public function destroy(
        InventoryObjectUnit $object_unit
    )
    {
        //  dd($inventoryObjectUnit);
        $this->service->delete($object_unit);

        return ApiResponse::deleted(
            "Inventory Object Unit deleted successfully."
        );
    }
}