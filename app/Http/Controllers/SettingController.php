<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function get($key)
    {
        $setting = Setting::where('key', $key)->first();
        return response()->json($setting);
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );

            // Sync with Products table for consistency
            if ($key === 'hourly_rate') {
                \App\Models\Product::where('name', '1 Hour Play')->update(['price' => $value]);
            }
            if ($key === 'day_pass_rate') {
                \App\Models\Product::where('name', 'Day Pass (Buffet)')->update(['price' => $value]);
            }
        }

        return response()->json([
            'message' => 'บันทึกการตั้งค่าสำเร็จ'
        ]);
    }
}