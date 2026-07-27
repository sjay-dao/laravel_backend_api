<?php

use App\Domains\Employee\Controllers\EmployeeController;
use App\Domains\Employee\Controllers\EmployeeReferenceController;
use App\Domains\Employee\Controllers\EmployeeReportController;
use App\Domains\Employee\Controllers\EmployeeTransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('employees')->group(function () {
    Route::get('references/departments', [EmployeeReferenceController::class, 'departments']);
    Route::post('references/departments', [EmployeeReferenceController::class, 'storeDepartment']);
    Route::put('references/departments/{department}', [EmployeeReferenceController::class, 'updateDepartment']);
    Route::get('references/positions', [EmployeeReferenceController::class, 'positions']);
    Route::post('references/positions', [EmployeeReferenceController::class, 'storePosition']);
    Route::put('references/positions/{position}', [EmployeeReferenceController::class, 'updatePosition']);
    Route::get('reports/employees', [EmployeeReportController::class, 'employees']);
    Route::get('reports/advances', fn (\Illuminate\Http\Request $request) => app(EmployeeReportController::class)->transactions($request, 'advance'));
    Route::get('reports/deductions', fn (\Illuminate\Http\Request $request) => app(EmployeeReportController::class)->transactions($request, 'deduction'));
    Route::get('{employee}/reports/salary-history', [EmployeeReportController::class, 'salaryHistory']);
    Route::get('{employee}/activities', [EmployeeController::class, 'activities']);
    Route::post('{employee}/salary-setups', [EmployeeController::class, 'storeSalarySetup']);
    Route::get('{employee}/ledger', [EmployeeTransactionController::class, 'ledger']);
    Route::get('/', [EmployeeController::class, 'index']);
    Route::post('/', [EmployeeController::class, 'store']);
    Route::get('{employee}', [EmployeeController::class, 'show']);
    Route::put('{employee}', [EmployeeController::class, 'update']);
    Route::delete('{employee}', [EmployeeController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->prefix('employee-transactions')->group(function () {
    Route::get('/', [EmployeeTransactionController::class, 'index']);
    Route::post('/', [EmployeeTransactionController::class, 'store']);
    Route::get('{employeeTransaction}', [EmployeeTransactionController::class, 'show']);
    Route::post('{employeeTransaction}/void', [EmployeeTransactionController::class, 'void']);
});
