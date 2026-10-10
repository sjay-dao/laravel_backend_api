<?php

use App\Domains\System\Controllers\MeController;
use Illuminate\Support\Facades\Route;
use App\Domains\System\Controllers\RoleController;
use App\Domains\System\Controllers\PermissionController;    
use App\Domains\System\Controllers\UserController;
use App\Domains\System\Controllers\PsgcBarangayController;

Route::middleware('auth:sanctum')
    ->prefix('system')
    ->controller(MeController::class)
    ->group(function () {

    Route::get('/me', [MeController::class, 'index']);

    Route::get('/permissions', [
        PermissionController::class,
        'index'
    ]);

    Route::get('/roles/options', [
        RoleController::class,
        'options'
    ]);

    Route::get('/roles/{role}/permissions', [
        RoleController::class,
        'permissions'
    ]);

    Route::put('/roles/{role}/permissions', [
        RoleController::class,
        'syncPermissions'
    ]);

    Route::get('/users/options', [
        UserController::class,
        'options',
    ]);

    Route::get('/users/{user}/roles', [
        UserController::class,
        'roles',
    ]);

    Route::put('/users/{user}/roles', [
        UserController::class,
        'syncRoles',
    ]);

    Route::get(
        'permissions/options',
        [PermissionController::class, 'options']
    );

    Route::get(
        'permissions/{permission}/roles',
        [PermissionController::class, 'roles']
    );

    Route::post(
        'permissions/{permission}/roles',
        [PermissionController::class, 'syncRoles']
    );

    Route::patch(
        'permissions/{permission}/toggle-status',
        [PermissionController::class, 'toggleStatus']
    );

    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);


});


Route::prefix('system')
    ->controller(PsgcBarangayController::class)
    ->group(function () {

        Route::get(
            '/barangay/options',
            'options'
        );

    });