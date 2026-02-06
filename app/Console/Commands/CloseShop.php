<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Session;
use App\Models\Table;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CloseShop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close all ongoing tables and calculate final bills at shop closing time';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to close all ongoing sessions...');

        $sessions = Session::where('status', 'ongoing')->get();

        if ($sessions->isEmpty()) {
            $this->info('No ongoing sessions found.');
            return 0;
        }

        $now = Carbon::now();
        
        // ดึง Settings มาเตรียมไว้ก่อนเพื่อลดการ Query ใน Loop
        $hourlyRateSetting = Setting::where('key', 'hourly_rate')->first();
        $ratePerHour = $hourlyRateSetting ? (int)$hourlyRateSetting->value : 40;

        $dayPassSetting = Setting::where('key', 'day_pass_rate')->first();
        $dayPassRate = $dayPassSetting ? (int)$dayPassSetting->value : 150;

        foreach ($sessions as $session) {
            DB::transaction(function () use ($session, $now, $ratePerHour, $dayPassRate) {
                // --- คำนวณค่าชั่วโมง / Day Pass ---
                $pax = $session->guest_amount;
                $timeCost = 0;

                if ($session->is_day_pass) {
                    $timeCost = $dayPassRate * $pax;
                } else {
                    $start = Carbon::parse($session->start_time);
                    $hours = ceil($start->diffInMinutes($now) / 60);
                    if ($hours == 0) $hours = 1;
                    $timeCost = $hours * $ratePerHour * $pax;
                }

                // --- คำนวณค่าอาหาร (กรองรายการที่ยกเลิกออก) ---
                $foodCost = $session->orders()
                                    ->where('status', '!=', 'cancelled')
                                    ->sum('total_price');

                $grandTotal = $timeCost + $foodCost;

                // 1. ปิด Session
                $session->update([
                    'end_time' => $now,
                    'total_amount' => $grandTotal,
                    'status' => 'completed',
                    'closed_by' => 'system'
                ]);

                // 2. ปิดโต๊ะ
                $session->table->update(['status' => 'available']);

                $this->info("Closed Session ID: {$session->id} for Table: {$session->table->name} | Total: {$grandTotal}");
            });
        }

        $this->info('All tables have been closed successfully.');
        return 0;
    }
}
