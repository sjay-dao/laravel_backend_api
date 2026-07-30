<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Payroll\Controllers\PayrollController;

Route::middleware('auth:sanctum')
    ->prefix('payroll-runs')
    ->controller(PayrollController::class)
    ->group(function () {

        Route::get('/', 'index');

        Route::post('/', 'store');

        Route::get('/{payrollRun}', 'show');

        Route::patch('/{payrollRun}', 'update');

        Route::delete('/{payrollRun}', 'destroy');

        Route::post('/{payrollRun}/compute', 'compute');

        Route::get(
            '/{payrollRun}/details',
            'details'
        );

        Route::post('/{payrollRun}/cancel', 'cancel');
});