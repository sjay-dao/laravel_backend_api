<?php

namespace App\Domains\Inventory\Controllers;

use App\Domains\Inventory\Models\InventoryObject;
use App\Domains\Inventory\Requests\StoreInventoryObjectRequest;
use App\Domains\Inventory\Requests\UpdateInventoryObjectRequest;
use App\Domains\Inventory\Resources\InventoryObjectResource;
use App\Domains\Inventory\Services\InventoryObjectService;
use App\Domains\Shared\Controllers\BaseApiController;

class InventoryObjectController extends BaseApiController
{
    public function __construct(
        protected InventoryObjectService $service
    ) {}

    public function index()
    {
        return $this->paginated(
            $this->service->paginate(),
            InventoryObjectResource::class,
            'Inventory objects retrieved successfully.'
        );
    }

    public function store(StoreInventoryObjectRequest $request)
    {
        $inventoryObject = $this->service->create(
            $request->validated()
        );

        return $this->created(
            new InventoryObjectResource($inventoryObject),
            'Inventory object created successfully.'
        );
    }

    public function show(InventoryObject $inventoryObject)
    {
        return $this->resource(
            new InventoryObjectResource($inventoryObject->load([
                'category',
                'baseUnit',
                'units.unit',
            ]))
        );
    }

    public function update(
        UpdateInventoryObjectRequest $request,
        InventoryObject $inventoryObject
    ) {
        $inventoryObject = $this->service->update(
            $inventoryObject,
            $request->validated()
        );

        return $this->resource(
            new InventoryObjectResource($inventoryObject),
            'Inventory object updated successfully.'
        );
    }

    public function destroy(InventoryObject $inventoryObject)
    {
        $this->service->delete($inventoryObject);

        return $this->deleted(
            'Inventory object deleted successfully.'
        );
    }
}