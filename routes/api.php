<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController; // <--- 1. ต้อง Import Controller เข้ามา
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

// <--- 2. เพิ่ม Route Login ตรงนี้ครับ (Public Route)
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/categories', [ProductController::class, 'categories']);
Route::get('/settings/{key}', [SettingController::class, 'get']);
Route::get('/sessions/validate/{token}', [App\Http\Controllers\SessionController::class, 'validateToken']);
Route::post('/guest/orders', [OrderController::class, 'storeGuestOrder']);

// ส่วนที่ต้อง Login แล้วถึงเข้าได้ (Protected Routes)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']); // เพิ่ม logout เผื่อไว้เลย
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::resource('users', UserController::class);
    Route::resource('products', ProductController::class)->except(['index']);
    Route::post('/settings', [SettingController::class, 'update']);

    Route::resource('tables', TableController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::post('/tables/{id}/open', [TableController::class, 'open']);
    Route::get('/tables/{id}/bill', [TableController::class, 'getBill']);
    Route::post('/tables/{id}/checkout', [TableController::class, 'checkout']);

    Route::get('/tables/{id}/orders', [OrderController::class, 'index']);
    Route::get('/orders/pending-confirmations', [OrderController::class, 'getPendingConfirmations']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);

    // Reports
    Route::get('/reports/daily', [ReportController::class, 'index']);
    Route::get('/reports/export-csv', [ReportController::class, 'exportCSV']);
});

