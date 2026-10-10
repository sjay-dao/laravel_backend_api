<?php

use App\Domains\Sales\Controllers\SalesOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('sales')
    ->controller(SalesOrderController::class)
    ->group(function () {

        Route::apiResource('sales-orders', SalesOrderController::class)
            ->middlewareFor(['index', 'show'], 'can:sales.sales.view')
            ->middlewareFor('destroy', 'can:sales.sales.update');
        Route::post('sales-orders/{salesOrder}/confirm', 'confirm')->middleware('can:sales.sales.update');
        Route::post('sales-orders/{salesOrder}/complete', 'complete')->middleware('can:sales.sales.update');
        Route::post('sales-orders/{salesOrder}/payments', 'recordPayment')->middleware('can:sales.sales.update');

    });
