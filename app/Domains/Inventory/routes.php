<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Inventory\Controllers\InventoryObjectUnitController;
use App\Domains\Inventory\Controllers\InventoryObjectController;
use App\Domains\Inventory\Controllers\InventoryCategoryController;
use App\Domains\Inventory\Controllers\UnitController;


Route::prefix('inventory')->group(function () {

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

});