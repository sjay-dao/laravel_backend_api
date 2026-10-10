<?php

use App\Domains\Evidence\Controllers\EvidenceReconciliationController;
use App\Domains\Evidence\Controllers\MarketObservationController;
use App\Domains\Evidence\Controllers\ProductEvidenceController;
use App\Domains\Evidence\Controllers\PublicSurveyController;
use App\Domains\Evidence\Controllers\SupplierObservationController;
use App\Domains\Evidence\Controllers\SurveyController;
use Illuminate\Support\Facades\Route;

/* Public routes expose a published questionnaire only. They intentionally do
 * not expose supplier, market, product-evidence, or internal survey management.
 */
Route::prefix('evidence/public')
    ->controller(PublicSurveyController::class)
    ->group(function () {
        Route::get('surveys/{survey:code}', 'show');
        Route::post('surveys/{survey:code}/responses', 'submit');
    });

Route::middleware('auth:sanctum')
    ->prefix('evidence')
    ->group(function () {
        Route::controller(EvidenceReconciliationController::class)->group(function () {
            Route::get('reconciliations', 'index');
            Route::get('reconciliations/{evidenceRecord}', 'show');
            Route::get('reconciliations/{evidenceRecord}/candidates', 'candidates');
            Route::post('reconciliations/{evidenceRecord}', 'store');
        });

        Route::controller(SupplierObservationController::class)->group(function () {
            Route::get('supplier-observations', 'index');
            Route::post('supplier-observations', 'store');
        });

        Route::controller(MarketObservationController::class)->group(function () {
            Route::get('market-observations', 'index');
            Route::post('market-observations', 'store');
        });

        Route::controller(SurveyController::class)->group(function () {
            Route::get('surveys', 'index');
            Route::post('surveys', 'store');
            Route::get('surveys/{survey}', 'show');
            Route::patch('surveys/{survey}', 'update');
            Route::post('surveys/{survey}/publish', 'publish');
            Route::get('surveys/{survey}/responses', 'responses');
            Route::post('surveys/{survey}/responses', 'submitAssisted');
        });

        Route::get('inventory-objects', [ProductEvidenceController::class, 'index']);
        Route::get(
            'inventory-objects/{inventoryObject}/evidence',
            [ProductEvidenceController::class, 'show']
        );
    });
