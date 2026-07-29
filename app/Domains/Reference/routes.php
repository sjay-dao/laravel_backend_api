<?php


use Illuminate\Support\Facades\Route;
use App\Domains\Reference\Controllers\ReferenceController;

Route::middleware('auth:sanctum')
    ->prefix('references')
    ->controller(ReferenceController::class)
    ->group(function () {

        Route::get('/', 'index');

        Route::get('/bootstrap', 'bootstrap');

        Route::get('/type/{type}', 'byType');

        Route::post('/', 'store');

        Route::patch('/{reference}', 'update');

        Route::delete('/{reference}', 'destroy');

    });