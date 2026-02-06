<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Table;
use App\Models\Session; // <--- 1. อย่าลืม Import Model Session
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Str; // <--- ใช้สำหรับสร้าง guest_token มั่วๆ

class TableController extends Controller
{
    public function index()
    {
        return response()->json(Table::with(['sessions' => function($query) {
            $query->where('status', 'ongoing');
        }])->get());
    }

    // แก้ไขฟังก์ชันนี้ใหม่
    public function open(Request $request, $id)
    {
        $table = Table::find($id);
        
        if (!$table) {
            return response()->json(['message' => 'ไม่พบโต๊ะนี้'], 404);
        }

        if ($table->status === 'busy') {
            return response()->json(['message' => 'โต๊ะนี้มีลูกค้าอยู่แล้ว'], 400);
        }

        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($table, $request) {
                // 1. เปลี่ยนสถานะโต๊ะ
                $table->status = 'busy'; 
                $table->save();

                // 2. ✅ สร้าง Session ใหม่
                $session = Session::create([
                    'table_id' => $table->id,
                    'user_id' => $request->user()->id, // ดึง ID พนักงานที่ล็อกอินอยู่
                    'guest_amount' => $request->input('pax', 1), // ใช้ guest_amount ให้ตรงกับ Migration
                    'is_day_pass' => $request->input('is_day_pass', false),
                    'guest_token' => \Illuminate\Support\Str::random(10),
                    'start_time' => now(),
                    'status' => 'ongoing',
                    'total_amount' => 0
                ]);

                return response()->json([
                    'message' => 'เปิดโต๊ะและสร้าง Session สำเร็จ', 
                    'table' => $table,
                    'session' => $session
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
        }
    }

    public function getBill($id)
    {
        // หา Session ที่กำลังเล่นอยู่ (ongoing)
        $session = Session::where('table_id', $id)
                          ->where('status', 'ongoing')
                          ->with('orders.product') // ดึงรายการอาหารมาด้วย
                          ->first();

        if (!$session) {
            return response()->json(['message' => 'ไม่พบข้อมูล หรือโต๊ะนี้ว่างอยู่'], 404);
        }

        // --- คำนวณค่าชั่วโมง / Day Pass ---
        $pax = $session->guest_amount; // จำนวนคน
        $timeCost = 0;
        $minutes = 0;

        if ($session->is_day_pass) {
            $setting = Setting::where('key', 'day_pass_rate')->first();
            $dayPassRate = $setting ? (int)$setting->value : 199;
            $timeCost = $dayPassRate * $pax;
        } else {
            $start = Carbon::parse($session->start_time);
            $now = Carbon::now();
            $minutes = $start->diffInMinutes($now);
            $hours = ceil($minutes / 60); // ปัดเศษชั่วโมง
            if ($hours == 0) $hours = 1; // ขั้นต่ำ 1 ชม.

            $setting = Setting::where('key', 'hourly_rate')->first();
            $ratePerHour = $setting ? (int)$setting->value : 40; 
            $timeCost = $hours * $ratePerHour * $pax;
        }

        // --- คำนวณค่าอาหาร (เฉพาะรายการที่ไม่ได้ยกเลิก) ---
        $foodCost = $session->orders()
                            ->where('status', '!=', 'cancelled')
                            ->sum('total_price');

        return response()->json([
            'session_id' => $session->id,
            'pax' => $pax,
            'is_day_pass' => $session->is_day_pass,
            'duration_minutes' => $minutes,
            'costs' => [
                'food' => $foodCost,
                'time' => $timeCost,
                'total' => $foodCost + $timeCost
            ]
        ]);
    }

    // 3. ฟังก์ชันเช็คบิล (ปิดโต๊ะ + บันทึกยอด)
    public function checkout($id)
    {
        // ก็อปปี้ Logic การหา Session มาเหมือนเดิม
        $session = Session::where('table_id', $id)->where('status', 'ongoing')->first();

        if (!$session) {
            return response()->json(['message' => 'ไม่พบ Session'], 404);
        }

        // --- คำนวณยอดสุดท้าย ---
        $now = Carbon::now();
        $timeCost = 0;
        $pax = $session->guest_amount;

        if ($session->is_day_pass) {
            $setting = Setting::where('key', 'day_pass_rate')->first();
            $dayPassRate = $setting ? (int)$setting->value : 199;
            $timeCost = $dayPassRate * $pax;
        } else {
            $start = Carbon::parse($session->start_time);
            $hours = ceil($start->diffInMinutes($now) / 60);
            if ($hours == 0) $hours = 1;

            $setting = Setting::where('key', 'hourly_rate')->first();
            $ratePerHour = $setting ? (int)$setting->value : 40;
            $timeCost = $hours * $ratePerHour * $pax;
        }

        $foodCost = $session->orders()
                            ->where('status', '!=', 'cancelled')
                            ->sum('total_price');
        $grandTotal = $timeCost + $foodCost;

        // --- อัปเดต Database ---
        
        // 1. ปิด Session
        $session->update([
            'end_time' => $now,
            'total_amount' => $grandTotal,
            'status' => 'completed',
            'closed_by' => 'staff'
        ]);

        // 2. ปิดโต๊ะ (กลับเป็นว่าง)
        $table = Table::find($id);
        $table->update(['status' => 'available']);

        return response()->json([
            'message' => 'เช็คบิลเรียบร้อย',
            'grand_total' => $grandTotal
        ]);
    }

    public function show($id)
    {
        $table = Table::with(['sessions' => function($query) {
            $query->where('status', 'ongoing')->latest();
        }])->find($id);

        if (!$table) {
            return response()->json(['message' => 'ไม่พบโต๊ะนี้'], 404);
        }
        
        return response()->json($table);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tables,name,NULL,id,deleted_at,NULL',
            'seat_count' => 'required|integer|min:1',
        ]);

        $table = Table::create([
            'name' => $request->name,
            'seat_count' => $request->seat_count,
            'status' => 'available'
        ]);

        return response()->json([
            'message' => 'เพิ่มโต๊ะสำเร็จ',
            'table' => $table
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json(['message' => 'ไม่พบโต๊ะนี้'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:tables,name,' . $id . ',id,deleted_at,NULL',
            'seat_count' => 'required|integer|min:1',
        ]);

        $table->update($request->only(['name', 'seat_count']));

        return response()->json([
            'message' => 'แก้ไขข้อมูลโต๊ะสำเร็จ',
            'table' => $table
        ]);
    }

    public function destroy($id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json(['message' => 'ไม่พบโต๊ะนี้'], 404);
        }

        if ($table->status === 'busy') {
            return response()->json(['message' => 'ไม่สามารถลบโต๊ะที่มีลูกค้าอยู่ได้'], 400);
        }

        $table->delete();

        return response()->json(['message' => 'ลบโต๊ะสำเร็จ']);
    }
}