<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // 1. ดึง ID ของหมวดหมู่ต่างๆ มาเตรียมไว้
        // (ใช้ first() เพื่อเอา ID ของหมวดแรกที่เจอใน Type นั้นๆ มาเป็นตัวอย่าง)
        $drinkCategoryId = Category::where('type', 'drink')->first()->id; 
        $foodCategoryId = Category::where('type', 'food')->first()->id;
        $retailCategoryId = Category::where('type', 'retail')->first()->id;
        $serviceCategoryId = Category::where('type', 'service')->first()->id;

        $products = [
            // --- เครื่องดื่ม ---
            [
                'category_id' => $drinkCategoryId,
                'name' => 'Ice Latte',
                'description' => 'กาแฟลาเต้',
                'price' => 60.00,
                'stock_qty' => 100,
                'is_active' => true
            ],
            [
                'category_id' => $drinkCategoryId,
                'name' => 'Coke Zero',
                'description' => 'โค้กซีโร่',
                'price' => 25.00,
                'stock_qty' => 48,
                'is_active' => true
            ],
            [
                'category_id' => $drinkCategoryId,
                'name' => 'Drinking Water',
                'description' => 'น้ำดื่ม',
                'price' => 15.00,
                'stock_qty' => 120,
                'is_active' => true
            ],

            // --- อาหาร ---
            [
                'category_id' => $foodCategoryId,
                'name' => 'French Fries',
                'description' => 'เฟรนช์ฟรายส์',
                'price' => 79.00,
                'stock_qty' => 50, // ตัดสต็อกตามจำนวนเสิร์ฟที่ทำได้
                'is_active' => true
            ],
            [
                'category_id' => $foodCategoryId,
                'name' => 'Chicken Nuggets',
                'description' => 'นักเก็ตไก่ 5 ชิ้น',
                'price' => 89.00,
                'stock_qty' => 30,
                'is_active' => true
            ],
            [
                'category_id' => $retailCategoryId,
                'name' => 'Werewolf (TH)',
                'description' => 'บอร์ดเกม Werewolf เวอร์ชั่นภาษาไทย',
                'price' => 200.00,
                'stock_qty' => 25,
                'is_active' => true
            ],
            [
                'category_id' => $serviceCategoryId,
                'name' => '1 Hour Play',
                'description' => 'ค่าชั่วโมงเล่นเกม (ต่อคน)',
                'price' => 40.00,
                'stock_qty' => 99999, // ใส่เยอะๆ เพราะไม่มีวันหมด
                'is_active' => true
            ],
            [
                'category_id' => $serviceCategoryId,
                'name' => 'Day Pass (Buffet)',
                'description' => 'เหมาจ่ายเล่นทั้งวัน',
                'price' => 199.00,
                'stock_qty' => 99999,
                'is_active' => true
            ],
        ];

        foreach ($products as $item) {
            Product::create($item);
        }
    }
}