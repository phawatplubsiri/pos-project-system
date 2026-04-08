<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// เปลี่ยนหน้าแรกให้เป็น JSON เพื่อเลี่ยง Error ของ Vite manifest
Route::get('/', function () {
    return response()->json([
        'app' => 'POS Project API',
        'status' => 'Online',
        'timestamp' => now()->toDateTimeString()
    ]);
});

// ถ้าเข้า URL อื่นๆ ให้ลองไปที่หน้า welcome (แต่ต้องแก้ไฟล์ welcome ด้วยถ้าจะใช้)
// หรือจะปิดส่วนนี้ไปก่อนก็ได้ครับถ้าไม่ได้ใช้ Blade
Route::get('/{any}', function () {
    return response()->json(['message' => 'API Endpoint Not Found'], 404);
})->where('any', '.*');
