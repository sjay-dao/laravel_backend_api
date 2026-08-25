<?php


use Illuminate\Support\Facades\Route;
use App\Domains\Sales\Controllers\SalesOrderController;

Route::middleware('auth:sanctum')
    ->prefix('sales')
    ->controller(SalesOrderController::class)
    ->group(function () {

       
    Route::apiResource('sales-orders', SalesOrderController::class);
    Route::post('sales-orders/{salesOrder}/confirm','confirm');

    });
