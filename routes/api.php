<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\LocationLogController;
use App\Http\Controllers\Api\ReceiptController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send-test-email', [EmailController::class, 'sendTestEmail']);
Route::post('/email/otp/send', [EmailController::class, 'sendOtp']);
Route::post('/email/otp/verify', [EmailController::class, 'verifyOtp']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('/products', ProductController::class);
    Route::apiResource('/orders', OrderController::class);
    Route::apiResource('/location-logs', LocationLogController::class);

    Route::get('/orders/{order}/location-logs', [LocationLogController::class, 'orderLogs']);
    Route::get('/orders/{order}/latest-location', [LocationLogController::class, 'latestByOrder']);
    Route::get('/orders/{order}/official-receipt', [ReceiptController::class, 'officialReceipt']);
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel']);
});
