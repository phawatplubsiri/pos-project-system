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
        // ✅ แก้ Validate เป็น email
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users', // เช็คว่าเป็น email และห้ามซ้ำ
            'password' => 'required|min:4',
            'role' => 'required'
        ]);

        return User::create([
            'name' => $request->name,
            'email' => $request->email, // ✅ บันทึก email
            'password' => Hash::make($request->password),
            'role' => $request->role
        ]);
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
}