<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        // สร้าง User: Admin (ใช้ firstOrCreate ป้องกันซ้ำตอน deploy ใหม่)
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Manager',
                'password' => Hash::make('admin1234'),
                'role' => 'admin',
                'pin' => Hash::make('123456'),
            ]
        );

        // สร้าง User: Staff
        User::firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'First Staff',
                'password' => Hash::make('staff1234'),
                'role' => 'staff',
                'pin' => Hash::make('111111'),
            ]
        );
    }
}