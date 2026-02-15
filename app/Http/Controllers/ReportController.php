<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * ดึงสรุปรายได้รายวัน
     */
    public function index(Request $request)
    {
        // ตรวจสอบสิทธิ์ Admin (ถ้าต้องการ)
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนี้'], 403);
        }

        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', $startDate);

        $sessions = Session::whereBetween('end_time', [
                               Carbon::parse($startDate)->startOfDay(),
                               Carbon::parse($endDate)->endOfDay()
                           ])
                           ->where('status', 'completed')
                           ->get();

        $summary = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_revenue' => $sessions->sum('total_amount'),
            'total_tables' => $sessions->count(),
            'closed_by_system' => $sessions->where('closed_by', 'system')->count(),
            'closed_by_staff' => $sessions->where('closed_by', 'staff')->count(),
        ];

        // รายละเอียด Sessions ทั้งหมดในช่วงเวลานั้น
        $details = $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'table_name' => $session->table->name ?? 'N/A',
                'pax' => $session->guest_amount,
                'start_time' => $session->start_time->format('Y-m-d H:i'),
                'end_time' => $session->end_time->format('Y-m-d H:i'),
                'total_amount' => $session->total_amount,
                'closed_by' => $session->closed_by,
            ];
        });

        return response()->json([
            'summary' => $summary,
            'details' => $details
        ]);
    }

    /**
     * ส่งออกข้อมูลเป็น CSV เพื่อใช้กับ Google Sheets ตามช่วงวันที่
     */
    public function exportCSV(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'ไม่มีสิทธิ์'], 403);
        }

        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', $startDate);

        $sessions = Session::whereBetween('end_time', [
                               Carbon::parse($startDate)->startOfDay(),
                               Carbon::parse($endDate)->endOfDay()
                           ])
                           ->where('status', 'completed')
                           ->with('table')
                           ->get();

        $filename = "report-{$startDate}-to-{$endDate}.csv";
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'โต๊ะ', 'จำนวนลูกค้า', 'เวลาเริ่ม', 'เวลาจบ', 'ยอดรวม', 'ปิดบิลโดย'];

        $callback = function() use ($sessions, $columns) {
            $file = fopen('php://output', 'w');
            
            // ใส่ BOM เพื่อให้ Excel/Google Sheets อ่านภาษาไทยได้
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, $columns);

            foreach ($sessions as $session) {
                fputcsv($file, [
                    $session->id,
                    $session->table->name ?? '',
                    $session->guest_amount,
                    $session->start_time->format('Y-m-d H:i'),
                    $session->end_time->format('Y-m-d H:i'),
                    $session->total_amount,
                    $session->closed_by
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}