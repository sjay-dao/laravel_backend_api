<?php

namespace App\Domains\Inventory\Controllers;

use App\Domains\Inventory\Models\Warehouse;
use App\Domains\Inventory\Requests\StoreWarehouseRequest;
use App\Domains\Inventory\Requests\UpdateWarehouseRequest;
use App\Domains\Inventory\Resources\WarehouseResource;
use App\Domains\Inventory\Services\WarehouseService;
use App\Domains\Shared\Controllers\BaseApiController;
use App\Domains\Shared\Responses\ApiResponse;
use App\Domains\Shared\Responses\ApiPaginatedResponse;


class WarehouseController extends BaseApiController
{
    protected WarehouseService $service;

    public function __construct(WarehouseService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $paginator = $this->service->paginate();

        $paginator->setCollection(
            WarehouseResource::collection(
                collect($paginator->items())
            )->collection
        );

        return ApiPaginatedResponse::make(
            $paginator,
            'Warehouses retrieved successfully.'
        );
    }

    public function store(
        StoreWarehouseRequest $request
    ) {
        return ApiResponse::created(
            new WarehouseResource(
                $this->service->create(
                    $request->validated()
                )
            ),
            'Warehouse created successfully.'
        );
    }

    public function show(
        Warehouse $Warehouse    
    ) {
        return ApiResponse::success(
            new WarehouseResource(
                $Warehouse
            )
        );
    }

    public function update(
        UpdateWarehouseRequest $request,
        Warehouse $Warehouse
    ) {
        // dd($request->validated());
        return ApiResponse::success(
            new WarehouseResource(
                $this->service->update(
                    $Warehouse,
                    $request->validated()
                )
            ),
            'Warehouse updated successfully.'
        );
    }

    public function destroy(
        Warehouse $Warehouse
    ) {
        $this->service->delete(
            $Warehouse
        );

        return ApiResponse::deleted(
            'Warehouse deleted successfully.'
        );
    }
}