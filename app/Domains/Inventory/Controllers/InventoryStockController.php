<?php

namespace App\Domains\Inventory\Controllers;

use App\Domains\Inventory\Services\InventoryStockService;
use App\Domains\Shared\Controllers\BaseApiController;
use App\Domains\Shared\Responses\ApiResponse;
use App\Domains\Inventory\Resources\InventoryStockResource;


class InventoryStockController extends BaseApiController
{
    protected InventoryStockService $service;

    public function __construct(
        InventoryStockService $service
    ) {
        $this->service = $service;
    }

    public function show(
        int $inventoryObject
    ) {
        return ApiResponse::success(

            new InventoryStockResource(

                $this->service->getStock(
                    $inventoryObject
                )

            ),

            'Inventory stock retrieved successfully.'

        );
    }
}