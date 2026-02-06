<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        // สร้างแค่ 4 หมวดหลัก ตาม Type เลย
        $categories = [
            ['name' => 'เครื่องดื่ม (Drinks)', 'type' => 'drink'],
            ['name' => 'อาหาร (Food)', 'type' => 'food'],
            ['name' => 'สินค้า (Retail)', 'type' => 'retail'],
            ['name' => 'บริการ (Services)', 'type' => 'service'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }
    }
}