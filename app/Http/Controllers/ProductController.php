<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // --- Sync Service Prices from Settings before returning ---
        $hourlyRate = \App\Models\Setting::where('key', 'hourly_rate')->first();
        if ($hourlyRate) {
            Product::where('name', '1 Hour Play')->update(['price' => $hourlyRate->value]);
        }

        $dayPassRate = \App\Models\Setting::where('key', 'day_pass_rate')->first();
        if ($dayPassRate) {
            Product::where('name', 'Day Pass (Buffet)')->update(['price' => $dayPassRate->value]);
        }
        // ---------------------------------------------------------

        $query = Product::with('category');

        if (!$request->has('all')) {
            $query->where('is_active', true);
        }

        $products = $query->get();

        // แปลง path ให้เป็น URL เต็มสำหรับ Frontend
        $products->transform(function ($product) {
            if ($product->image_path) {
                // ถ้าเป็น URL อยู่แล้ว (จาก Cloudinary) ให้ใช้ได้เลย
                if (filter_var($product->image_path, FILTER_VALIDATE_URL)) {
                    $product->image_url = $product->image_path;
                } else {
                    // ถ้าเป็น path ในเครื่อง (Legacy)
                    $product->image_url = asset('storage/' . $product->image_path);
                }
            } else {
                $product->image_url = null;
            }
            return $product;
        });

        return response()->json($products);
    }

    public function categories()
    {
        return response()->json(Category::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'stock_qty' => 'required|integer',
            'is_active' => 'required', // รับเป็น string 'true'/'false' จาก FormData
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except('image');
        $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);

        if ($request->hasFile('image')) {
            // อัปโหลดขึ้น Cloudinary
            $uploadedFileUrl = Cloudinary::upload($request->file('image')->getRealPath(), [
                'folder' => 'products'
            ])->getSecurePath();
            $data['image_path'] = $uploadedFileUrl;
        }

        $product = Product::create($data);

        return response()->json([
            'message' => 'เพิ่มสินค้าสำเร็จ',
            'product' => $product
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'stock_qty' => 'required|integer',
            'is_active' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image_path' => 'nullable', // อนุญาตให้ส่ง null มาเพื่อลบรูป
        ]);

        $data = $request->except('image');
        $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);

        // ถ้ามีการส่ง image_path มาเป็น string 'null' หรือ 'undefined' หรือค่าว่าง ให้เซตเป็น null จริงๆ
        if ($request->has('image_path')) {
            $val = $request->image_path;
            if ($val === 'null' || $val === 'undefined' || empty($val)) {
                $data['image_path'] = null;
            }
        }

        if ($request->hasFile('image')) {
            // ลบรูปเก่า (ถ้าเป็น local storage)
            if ($product->image_path && !filter_var($product->image_path, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($product->image_path);
            }
            
            // อัปโหลดขึ้น Cloudinary
            $uploadedFileUrl = Cloudinary::upload($request->file('image')->getRealPath(), [
                'folder' => 'products'
            ])->getSecurePath();
            $data['image_path'] = $uploadedFileUrl;
        }

        $product->update($data);

        return response()->json([
            'message' => 'แก้ไขสินค้าสำเร็จ',
            'product' => $product
        ]);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        // ลบรูปเก่า (ถ้าเป็น local storage)
        if ($product->image_path && !filter_var($product->image_path, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($product->image_path);
        }
        
        $product->delete();

        return response()->json([
            'message' => 'ลบสินค้าสำเร็จ'
        ]);
    }
}
