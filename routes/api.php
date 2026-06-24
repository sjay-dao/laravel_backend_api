<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\LocationLogController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\RmsController;
use App\Http\Controllers\Api\RMSControllerV2;
use App\Http\Controllers\Api\RmsNotificationController;
use App\Http\Controllers\BypassLoginController;
use App\Http\Controllers\Api\EmployeeLookupController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send-test-email', [EmailController::class, 'sendTestEmail']);
Route::post('/email/otp/send', [EmailController::class, 'sendOtp']);
Route::post('/email/otp/verify', [EmailController::class, 'verifyOtp']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
Route::post('/rms/notify-new', [RmsController::class, 'notifyRmsNew']);
Route::get('/rms/v2', [RMSControllerV2::class, 'index']);
Route::post('/employee/lookup', [EmployeeLookupController::class, 'lookup']);

//especially made for RMS integration testing, not for production use
Route::get('/rms', [RmsController::class, 'index']);
Route::get('/rms/forms', [RMSController::class, 'forms']);
Route::get('/rms/forms/{formId}/columns', [RMSController::class, 'columns']);
Route::post('/rms/send-tag-notification', [RmsNotificationController::class, 'send']);
Route::post('/rms/bypass-login', [BypassLoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/orders/map', [OrderController::class, 'map']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('/products', ProductController::class);
    Route::apiResource('/orders', OrderController::class);
    Route::apiResource('/location-logs', LocationLogController::class);

    Route::get('/orders/{order}/location-logs', [LocationLogController::class, 'orderLogs']);
    Route::get('/orders/{order}/latest-location', [LocationLogController::class, 'latestByOrder']);
    Route::get('/orders/{order}/official-receipt', [ReceiptController::class, 'officialReceipt']);
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel']);
});
