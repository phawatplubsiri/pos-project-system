<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index($tableId)
    {
        $session = Session::where('table_id', $tableId)
                          ->where('status', 'ongoing')
                          ->first();

        if (!$session) {
            return response()->json([]);
        }

        $orders = Order::where('session_id', $session->id)
                       ->with('product')
                       ->orderBy('created_at', 'desc')
                       ->get();

        return response()->json($orders);
    }

    public function getPendingConfirmations()
    {
        $orders = Order::where('status', 'confirming')
                       ->with(['product', 'session.table'])
                       ->orderBy('created_at', 'desc')
                       ->get();
        return response()->json($orders);
    }

    public function storeGuestOrder(Request $request)
    {
        $request->validate([
            'token' => 'required|exists:sessions,guest_token',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $session = Session::where('guest_token', $request->token)
                          ->where('status', 'ongoing')
                          ->first();

        if (!$session) {
            return response()->json(['message' => 'รอบการใช้งานไม่ถูกต้องหรือสิ้นสุดแล้ว'], 400);
        }

        try {
            DB::beginTransaction();
            foreach ($request->items as $item) {
                $product = Product::find($item['id']);
                Order::create([
                    'session_id' => $session->id,
                    'product_id' => $product->id,
                    'quantity' => $item['qty'],
                    'unit_price' => $product->price,
                    'total_price' => $product->price * $item['qty'],
                    'status' => 'confirming' // รอพนักงานยืนยัน
                ]);
            }
            DB::commit();
            return response()->json(['message' => 'ส่งรายการอาหารเรียบร้อย กรุณารอพนักงานยืนยัน']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::with(['product.category', 'session'])->findOrFail($id);
        $oldStatus = strtolower($order->status);
        $newStatus = strtolower($request->status);
        
        $request->validate([
            'status' => 'required|string|in:confirming,pending,completed,cancelled'
        ]);

        if ($oldStatus === $newStatus) {
            return response()->json(['message' => 'สถานะไม่มีการเปลี่ยนแปลง']);
        }

        try {
            DB::transaction(function () use ($order, $oldStatus, $newStatus) {
                // กรณีเปลี่ยนเป็น cancelled
                if ($newStatus === 'cancelled') {
                    // อนุญาตให้ยกเลิกเฉพาะรายการที่ยัง pending หรือ confirming เท่านั้น
                    if ($oldStatus !== 'pending' && $oldStatus !== 'confirming') {
                        throw new \Exception('ไม่สามารถยกเลิกรายการที่เสร็จสมบูรณ์แล้วได้');
                    }
                    
                    // ถ้าเคยเป็น pending (ซึ่งหักสต็อก/เงินไปแล้ว) ต้องคืนค่า
                    if ($oldStatus === 'pending') {
                        if ($order->product && $order->product->category && $order->product->category->type !== 'service') {
                            $order->product->increment('stock_qty', $order->quantity);
                        }
                        $order->session->decrement('total_amount', $order->total_price);
                    }
                }
                // กรณีพนักงานยืนยันจาก confirming เป็น pending
                elseif ($newStatus === 'pending' && $oldStatus === 'confirming') {
                    // หักสต็อกและเพิ่มยอดเงินในขั้นตอนนี้
                    if ($order->product && $order->product->category && $order->product->category->type !== 'service') {
                        if ($order->product->stock_qty < $order->quantity) {
                            throw new \Exception('สินค้า ' . $order->product->name . ' มีสต็อกไม่พอ');
                        }
                        $order->product->decrement('stock_qty', $order->quantity);
                    }
                    $order->session->increment('total_amount', $order->total_price);
                }
                // กรณีเปลี่ยนจากปกติ (pending) ไปเป็น completed
                elseif ($newStatus === 'completed' && $oldStatus === 'pending') {
                    // ไม่ต้องทำอะไรกับเงิน/สต็อก เพราะหักไปตั้งแต่ตอนสั่งแล้ว แค่เปลี่ยนสถานะ
                }

                $order->update(['status' => $newStatus]);
            });

            return response()->json([
                'message' => 'อัปเดตสถานะสำเร็จ',
                'order' => $order->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        // 1. รับค่าที่ส่งมาจากหน้าบ้าน
        $request->validate([
            'table_id' => 'required|exists:tables,id',
            'items' => 'required|array', // ต้องเป็น Array ของสินค้า
            'items.*.id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        // 2. หา Session ที่กำลังเปิดอยู่ (ONGOING) ของโต๊ะนี้
        $session = Session::where('table_id', $request->table_id)
                          ->where('status', 'ongoing')
                          ->first();

        if (!$session) {
            return response()->json(['message' => 'ไม่พบรอบการใช้งาน กรุณาเปิดโต๊ะก่อน'], 400);
        }

        try {
            DB::beginTransaction();

            $totalOrderAmount = 0;

            foreach ($request->items as $item) {
                $product = Product::find($item['id']);
                
                $lineTotal = $product->price * $item['qty'];

                Order::create([
                    'session_id' => $session->id,
                    'product_id' => $product->id,
                    'quantity' => $item['qty'],
                    'unit_price' => $product->price,
                    'total_price' => $lineTotal,
                    'status' => 'pending'
                ]);

                $totalOrderAmount += $lineTotal;
                
                if ($product->category->type !== 'service') {
                     $product->decrement('stock_qty', $item['qty']);
                }
            }

            $session->increment('total_amount', $totalOrderAmount);

            DB::commit();

            return response()->json(['message' => 'สั่งอาหารเรียบร้อยแล้ว']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
        }
    }
}
