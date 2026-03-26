<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $throttleKey = strtolower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => "พยายามเข้าสู่ระบบมากเกินไป กรุณาลองใหม่ภายใน $seconds วินาที",
                'retry_after' => $seconds
            ], 429);
        }

        $fields = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($fields)) {
            RateLimiter::hit($throttleKey, 60);
            return response()->json([
                'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'
            ], 401);
        }

        RateLimiter::clear($throttleKey);
        $user = Auth::user();
        $token = $user->createToken('staff-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'เข้าสู่ระบบสำเร็จ'
        ], 200);
    }

    public function loginWithPin(Request $request)
    {
        $throttleKey = 'pin_login|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => "กรอก PIN ผิดบ่อยเกินไป กรุณาลองใหม่ภายใน $seconds วินาที",
                'retry_after' => $seconds
            ], 429);
        }

        $request->validate([
            'pin' => 'required|string|size:6',
        ]);

        // ค้นหา User ทั้งหมดที่มี PIN แล้วตรวจสอบด้วย Hash::check
        // กรองเฉพาะ User ที่ไม่ถูกลบ
        $users = User::whereNotNull('pin')->get();
        
        $user = $users->first(function ($u) use ($request) {
            return Hash::check($request->pin, $u->pin);
        });

        if (!$user) {
            RateLimiter::hit($throttleKey, 60);
            return response()->json([
                'message' => 'PIN ไม่ถูกต้อง'
            ], 401);
        }

        RateLimiter::clear($throttleKey);
        $token = $user->createToken('staff-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'เข้าสู่ระบบสำเร็จ'
        ], 200);
    }
    
    public function logout(Request $request) {

        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logged out'
        ]);
    }
}