<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            [
                'key' => 'hourly_rate',
                'value' => '40',
            ],
            [
                'key' => 'day_pass_rate',
                'value' => '199',
            ],
            [
                'key' => 'shop_name',
                'value' => 'My Board Game Cafe',
            ],
            [
                'key' => 'closing_time',
                'value' => '22:00',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}