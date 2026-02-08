<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
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
            $product->image_url = $product->image_path 
                ? asset('storage/' . $product->image_path) 
                : null;
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
            $path = $request->file('image')->store('products', 'public');
            $data['image_path'] = $path;
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
        ]);

        $data = $request->except('image');
        $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);

        if ($request->hasFile('image')) {
            // ลบรูปเก่า
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $path = $request->file('image')->store('products', 'public');
            $data['image_path'] = $path;
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
        
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        
        $product->delete();

        return response()->json([
            'message' => 'ลบสินค้าสำเร็จ'
        ]);
    }
}
