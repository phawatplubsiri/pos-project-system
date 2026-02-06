<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        // สร้าง User: Admin
        User::create([
            'name' => 'Admin Manager',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin1234'),
            'role' => 'admin',
        ]);

        // สร้าง User: Staff (เอาไว้เทสอีกคน)
        User::create([
            'name' => 'First Staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('staff1234'),
            'role' => 'staff',
        ]);
    }
}