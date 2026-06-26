<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public API - Captive Portal
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('packages', [App\Http\Controllers\Api\Portal\PackageController::class, 'index']);
    Route::get('packages/{id}', [App\Http\Controllers\Api\Portal\PackageController::class, 'show']);
    Route::post('orders', [App\Http\Controllers\Api\Portal\OrderController::class, 'store']);
    Route::get('orders/{reference}', [App\Http\Controllers\Api\Portal\OrderController::class, 'show']);
    Route::get('session/{mac}', [App\Http\Controllers\Api\Portal\SessionController::class, 'showByMac']);
    Route::post('auth/check', [App\Http\Controllers\Api\Portal\SessionController::class, 'checkAuth']);
    Route::get('user/{mac}', [App\Http\Controllers\Api\Portal\UserController::class, 'showByMac']);
    Route::post('user/update', [App\Http\Controllers\Api\Portal\UserController::class, 'update']);
});

// Authenticated Customer API
Route::prefix('customer')->name('customer.')->middleware('auth:sanctum')->group(function () {
    Route::get('profile', [App\Http\Controllers\Api\Customer\ProfileController::class, 'show']);
    Route::put('profile', [App\Http\Controllers\Api\Customer\ProfileController::class, 'update']);
    Route::get('sessions', [App\Http\Controllers\Api\Customer\SessionController::class, 'index']);
    Route::get('sessions/{id}', [App\Http\Controllers\Api\Customer\SessionController::class, 'show']);
    Route::get('orders', [App\Http\Controllers\Api\Customer\OrderController::class, 'index']);
    Route::get('orders/{id}', [App\Http\Controllers\Api\Customer\OrderController::class, 'show']);
    Route::post('orders', [App\Http\Controllers\Api\Customer\OrderController::class, 'store']);
    Route::get('devices', [App\Http\Controllers\Api\Customer\DeviceController::class, 'index']);
});

// Admin API
Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'can:admin-access'])->group(function () {
    Route::get('dashboard/metrics', [App\Http\Controllers\Api\Admin\DashboardController::class, 'metrics']);
    Route::get('dashboard/charts', [App\Http\Controllers\Api\Admin\DashboardController::class, 'charts']);
    Route::get('routers/{id}/test', [App\Http\Controllers\Api\Admin\RouterController::class, 'test']);
    Route::post('routers/{id}/sync', [App\Http\Controllers\Api\Admin\RouterController::class, 'sync']);
    Route::post('sessions/{id}/suspend', [App\Http\Controllers\Api\Admin\SessionController::class, 'suspend']);
    Route::post('sessions/{id}/extend', [App\Http\Controllers\Api\Admin\SessionController::class, 'extend']);
    Route::post('payments/{id}/refund', [App\Http\Controllers\Api\Admin\PaymentController::class, 'refund']);
});
