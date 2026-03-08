<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Table; // <--- อย่าลืมบรรทัดนี้

class TableSeeder extends Seeder
{
    public function run()
    {
        // สร้างโต๊ะ T1 - T10
        for ($i = 1; $i <= 5; $i++) {
            Table::create([
                'name' => 'T' . $i,
                'seat_count' => 4,
                'is_available' => true,
                'is_active' => true,
            ]);
        }
    }
}