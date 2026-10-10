<?php

use Illuminate\Support\Facades\Route;
use App\Domains\System\Controllers\AuthController;
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
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\AddressController;

require app_path('Domains/routes.php');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send-test-email', [EmailController::class, 'sendTestEmail']);
Route::post('/email/otp/send', [EmailController::class, 'sendOtp']);
Route::post('/email/otp/verify', [EmailController::class, 'verifyOtp']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
Route::post('/rms/notify-new', [RmsController::class, 'notifyRmsNew']);
Route::get('/rms/v2', [RMSControllerV2::class, 'index']);
Route::get('/employee/lookup', [EmployeeLookupController::class, 'lookup']);

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
    Route::get('/branches/select', [BranchController::class, 'select']);

    
    Route::get('/orders/{order}/location-logs', [LocationLogController::class, 'orderLogs']);
    Route::get('/orders/{order}/latest-location', [LocationLogController::class, 'latestByOrder']);
    Route::get('/orders/{order}/official-receipt', [ReceiptController::class, 'officialReceipt']);
    Route::get('/orders/{order}/assign-branch', [OrderController::class, 'assignBranch']);
    Route::get('/orders/{order}/unassign-branch', [OrderController::class, 'unassignBranch']);
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    
    Route::apiResource('/products', ProductController::class);
    Route::apiResource('/orders', OrderController::class);
    Route::apiResource('/location-logs', LocationLogController::class);
    Route::apiResource('branches', BranchController::class);
    Route::apiResource('addresses', AddressController::class);
});
