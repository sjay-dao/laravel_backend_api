<?php


use Illuminate\Support\Facades\Route;
use App\Domains\Reference\Controllers\ReferenceController;
use App\Domains\Reference\Controllers\BranchController;

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


Route::middleware('auth:sanctum')
    ->prefix('system/branches')
    ->controller(BranchController::class)
    ->group(function () {

        Route::get('/', 'index');

        Route::get('/options', 'options');

        Route::get('/{branch}', 'show');

        Route::post('/', 'store');

        Route::patch('/{branch}', 'update');

        Route::delete('/{branch}', 'destroy');

    });