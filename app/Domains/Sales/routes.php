<?php

use App\Domains\Sales\Controllers\SalesOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('sales')
    ->controller(SalesOrderController::class)
    ->group(function () {

        Route::apiResource('sales-orders', SalesOrderController::class);
        Route::post('sales-orders/{salesOrder}/confirm', 'confirm');
        Route::post('sales-orders/{salesOrder}/complete', 'complete');

    });
