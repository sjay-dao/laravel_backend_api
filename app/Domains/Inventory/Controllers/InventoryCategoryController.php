<?php

namespace App\Domains\Inventory\Controllers;

use App\Domains\Inventory\Models\InventoryCategory;
use App\Domains\Inventory\Requests\StoreInventoryCategoryRequest;
use App\Domains\Inventory\Requests\UpdateInventoryCategoryRequest;
use App\Domains\Inventory\Resources\InventoryCategoryResource;
use App\Domains\Inventory\Services\InventoryCategoryService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class InventoryCategoryController extends BaseApiController
{
    public function __construct(
        protected InventoryCategoryService $service
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'inventory.categories.view');

        return $this->paginated(
            $this->service->paginate(),
            InventoryCategoryResource::class,
            'Inventory Categories retrieved successfully.'
        );
    }

    public function store(StoreInventoryCategoryRequest $request)
    {
        $this->authorizeAbility($request, 'inventory.categories.create');

        return $this->created(
            new InventoryCategoryResource(
                $this->service->create(
                    $request->validated()
                )
            ),
            'Inventory Category created successfully.'
        );
    }

    public function show(
        Request $request,
        InventoryCategory $inventoryCategory
    ) {
        $this->authorizeAbility($request, 'inventory.categories.view');

        return $this->resource(
            new InventoryCategoryResource(
                $inventoryCategory
            )
        );
    }

    public function update(
        UpdateInventoryCategoryRequest $request,
        InventoryCategory $inventoryCategory
    ) {
        $this->authorizeAbility($request, 'inventory.categories.update');

        return $this->resource(
            new InventoryCategoryResource(
                $this->service->update(
                    $inventoryCategory,
                    $request->validated()
                )
            ),
            'Inventory Category updated successfully.'
        );
    }

    public function destroy(
        Request $request,
        InventoryCategory $inventoryCategory
    ) {
        $this->authorizeAbility($request, 'inventory.categories.delete');

        $this->service->delete(
            $inventoryCategory
        );

        return $this->deleted(
            'Inventory Category deleted successfully.'
        );
    }
}