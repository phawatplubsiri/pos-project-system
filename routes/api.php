<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ReportController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authentication
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/login-pin', [AuthController::class, 'loginWithPin'])->middleware('throttle:login');

// Public Data
Route::get('/products', [ProductController::class, 'index']);
Route::get('/categories', [ProductController::class, 'categories']);
Route::get('/settings/{key}', [SettingController::class, 'get']);

// Session & Guest
Route::get('/sessions/validate/{token}', [App\Http\Controllers\SessionController::class, 'validateToken']);
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/guest/orders', [OrderController::class, 'storeGuestOrder']);
    Route::post('/guest/call-staff', [OrderController::class, 'callStaff']);
});

Route::middleware('auth:sanctum')->group(function () {
    
    // -------- Authentication --------
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // -------- User Management --------
    Route::resource('users', UserController::class);
    Route::post('/users/{id}/regenerate-pin', [UserController::class, 'regeneratePin']);

    // -------- Product Management --------
    Route::resource('products', ProductController::class)->except(['index']);
    
    // -------- Settings --------
    Route::post('/settings', [SettingController::class, 'update']);

    // -------- Table Management --------
    Route::resource('tables', TableController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::post('/tables/{id}/open', [TableController::class, 'open']);
    Route::get('/tables/{id}/bill', [TableController::class, 'getBill']);
    Route::post('/tables/{id}/checkout', [TableController::class, 'checkout']);

    // -------- Order Management --------
    Route::get('/tables/{id}/orders', [OrderController::class, 'index']);
    Route::get('/orders/pending-confirmations', [OrderController::class, 'getPendingConfirmations']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);

    // -------- Reports --------
    Route::get('/reports/daily', [ReportController::class, 'index']);
    Route::get('/reports/export-csv', [ReportController::class, 'exportCSV']);

});

