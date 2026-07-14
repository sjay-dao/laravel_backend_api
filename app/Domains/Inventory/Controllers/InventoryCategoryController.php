<?php

namespace App\Domains\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Inventory\Models\InventoryCategory;
use App\Domains\Inventory\Requests\StoreInventoryCategoryRequest;
use App\Domains\Inventory\Requests\UpdateInventoryCategoryRequest;
use App\Domains\Inventory\Resources\InventoryCategoryResource;
use App\Domains\Inventory\Services\InventoryCategoryService;

use App\Domains\Shared\Responses\ApiResponse;
use App\Domains\Shared\Responses\ApiPaginatedResponse;


class InventoryCategoryController extends Controller
{
    protected InventoryCategoryService $service;

    public function __construct(InventoryCategoryService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $paginator = $this->service->paginate();

        $paginator->setCollection(
            InventoryCategoryResource::collection(
                collect($paginator->items())
            )->collection
        );

        return ApiPaginatedResponse::make(
            $paginator,
            'Inventory Categories retrieved successfully.'
        );
    }

    public function create(
        StoreInventoryCategoryRequest $request
    ) {
        return ApiResponse::created(
            new InventoryCategoryResource(
                $this->service->create(
                    $request->validated()
                )
            ),
            'Inventory Category created successfully.'
        );
    }

    public function show(
        InventoryCategory $inventoryCategory    
    ) {
        return ApiResponse::success(
            new InventoryCategoryResource(
                $inventoryCategory
            )
        );
    }

    public function update(
        UpdateInventoryCategoryRequest $request,
        InventoryCategory $inventoryCategory
    ) {
        return ApiResponse::success(
            new InventoryCategoryResource(
                $this->service->update(
                    $inventoryCategory,
                    $request->validated()
                )
            ),
            'Inventory Category updated successfully.'
        );
    }

    public function delete(
        InventoryCategory $inventoryCategory
    ) {
        $this->service->delete(
            $inventoryCategory
        );

        return ApiResponse::deleted(
            'Inventory Category deleted successfully.'
        );
    }
}