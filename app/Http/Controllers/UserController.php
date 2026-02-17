<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:4',
            'role' => 'required'
        ]);

        // Check if user exists (including soft deleted)
        $existingUser = User::withTrashed()->where('email', $request->email)->first();

        if ($existingUser) {
            if ($existingUser->trashed()) {
                // Restore the user if they were soft-deleted
                $pin = User::generateUniquePin(6);
                $existingUser->restore();
                $existingUser->update([
                    'name' => $request->name,
                    'password' => Hash::make($request->password),
                    'role' => $request->role,
                    'pin' => Hash::make($pin)
                ]);

                return response()->json([
                    'user' => $existingUser,
                    'pin' => $pin,
                    'message' => 'กู้คืนข้อมูลพนักงานเดิมและเจน PIN ใหม่สำเร็จ'
                ], 201);
            } else {
                // If user is not trashed, it's a real duplicate
                return response()->json([
                    'message' => 'อีเมลนี้ถูกใช้งานอยู่ในระบบแล้ว'
                ], 422);
            }
        }

        $pin = User::generateUniquePin(6);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'pin' => Hash::make($pin)
        ]);

        return response()->json([
            'user' => $user,
            'pin' => $pin,
            'message' => 'สร้างพนักงานและเจน PIN สำเร็จ'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        
        $data = [
            'name' => $request->name,
            'email' => $request->email, // ✅ แก้เป็น email
            'role' => $request->role
        ];

        // ถ้ามีการส่ง password มาใหม่ค่อยแก้ (ถ้าว่างไว้คือไม่แก้)
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return $user;
    }

    public function destroy($id)
    {
        return User::destroy($id);
    }

    public function regeneratePin($id)
    {
        $user = User::findOrFail($id);
        $pin = User::generateUniquePin(6);
        $user->update(['pin' => Hash::make($pin)]);

        return response()->json([
            'pin' => $pin,
            'message' => 'รีเซ็ตรหัส PIN ใหม่สำเร็จ'
        ]);
    }
}