<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Inventory\Controllers\InventoryObjectUnitController;
use App\Domains\Inventory\Controllers\InventoryObjectController;
use App\Domains\Inventory\Controllers\InventoryCategoryController;
use App\Domains\Inventory\Controllers\UnitController;
use App\Domains\Inventory\Controllers\WarehouseController;
use App\Domains\Inventory\Controllers\InventoryMovementController;
use App\Domains\Inventory\Controllers\InventoryReservationController;
use App\Domains\Inventory\Controllers\InventoryStockController;

Route::middleware('auth:sanctum')
->prefix('inventory')->group(function () {

    Route::get(
        'movements/stocks/{inventoryObject}',
        [
            InventoryMovementController::class,
            'stock'
        ]
    );

    Route::get(
        'movements/stocks/{inventoryObject}/ledger',
        [
            InventoryMovementController::class,
            'ledger'
        ]
    );
    
    Route::get('inventory-objects/options', 
         [
            InventoryObjectController::class,
            'options'
        ]
    );

    Route::apiResource(
        'units',
        UnitController::class
    );

    Route::apiResource(
        'inventory-categories',
        InventoryCategoryController::class
    );

    Route::apiResource(
        'object-units',
        InventoryObjectUnitController::class
    );

    Route::apiResource(
        'inventory-objects',
        InventoryObjectController::class
    );

    Route::apiResource(
        'warehouse',
        WarehouseController::class
    );

    Route::apiResource(
        'movements',
        InventoryMovementController::class
    );

    // Route::apiResource(
    //     'reservations',
    //     InventoryReservationController::class
    // );

});