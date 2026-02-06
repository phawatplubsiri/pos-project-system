<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($fields)) {
            return response()->json([
                'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'
            ], 401);
        }

        $user = Auth::user();

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