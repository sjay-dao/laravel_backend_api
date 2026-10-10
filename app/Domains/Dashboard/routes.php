<?php

use App\Domains\Dashboard\Controllers\BusinessOverviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('dashboard/business-overview', BusinessOverviewController::class);
