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
        $email = strtolower($request->input('email'));
        $attemptsKey = 'login_attempts|' . $email;
        $lockoutKey = 'login_lockout|' . $email;
        $maxAttempts = 5;

        // 1. ตรวจสอบก่อนว่าติด Lockout 30 วินาทีอยู่หรือไม่
        if (RateLimiter::tooManyAttempts($lockoutKey, 1)) {
            $seconds = RateLimiter::availableIn($lockoutKey);
            return response()->json([
                'message' => "พยายามเข้าสู่ระบบมากเกินไป กรุณาลองใหม่ภายใน $seconds วินาที",
                'retry_after' => $seconds,
                'attempts' => $maxAttempts,
                'max_attempts' => $maxAttempts
            ], 429);
        }

        $fields = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($fields)) {
            // 2. บันทึกความผิด (จำไว้ 1 ชม.)
            RateLimiter::hit($attemptsKey, 3600);
            $attempts = RateLimiter::attempts($attemptsKey);
            
            // 3. ถ้าผิดครบโควต้า (ทุกๆ 5 ครั้ง หรือตั้งแต่ครั้งที่ 5 เป็นต้นไป) ให้ติด Lockout
            if ($attempts >= $maxAttempts) {
                RateLimiter::hit($lockoutKey, 180);
            }

            return response()->json([
                'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
                'attempts' => $attempts > $maxAttempts ? $maxAttempts : $attempts,
                'max_attempts' => $maxAttempts
            ], 401);
        }

        // 4. เข้าสู่ระบบสำเร็จ ล้างทั้งคู่ออก
        RateLimiter::clear($attemptsKey);
        RateLimiter::clear($lockoutKey);
        
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
        $ip = $request->ip();
        $attemptsKey = 'pin_attempts|' . $ip;
        $lockoutKey = 'pin_lockout|' . $ip;
        $maxAttempts = 5;

        // 1. ตรวจสอบ Lockout 30 วินาที
        if (RateLimiter::tooManyAttempts($lockoutKey, 1)) {
            $seconds = RateLimiter::availableIn($lockoutKey);
            return response()->json([
                'message' => "กรอก PIN ผิดบ่อยเกินไป กรุณาลองใหม่ภายใน $seconds วินาที",
                'retry_after' => $seconds,
                'attempts' => $maxAttempts,
                'max_attempts' => $maxAttempts
            ], 429);
        }

        $request->validate([
            'pin' => 'required|string|size:6',
        ]);

        $users = User::whereNotNull('pin')->get();
        $user = $users->first(function ($u) use ($request) {
            return Hash::check($request->pin, $u->pin);
        });

        if (!$user) {
            // 2. บันทึกความผิด (จำไว้ 1 ชม.)
            RateLimiter::hit($attemptsKey, 3600);
            $attempts = RateLimiter::attempts($attemptsKey);

            // 3. ติด Lockout
            if ($attempts >= $maxAttempts) {
                RateLimiter::hit($lockoutKey, 180);
            }

            return response()->json([
                'message' => 'PIN ไม่ถูกต้อง',
                'attempts' => $attempts > $maxAttempts ? $maxAttempts : $attempts,
                'max_attempts' => $maxAttempts
            ], 401);
        }

        // 4. สำเร็จ ล้างทั้งคู่ออก
        RateLimiter::clear($attemptsKey);
        RateLimiter::clear($lockoutKey);
        
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