<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['supplier', 'creator'])->paginate(50);
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_code' => 'required|unique:products|max:50',
            'barcode' => 'nullable|max:100',
            'name' => 'required|max:200',
            'description' => 'nullable',
            'category' => 'required',
            'supplier_id' => 'nullable|exists:suppliers,supplier_id',
            'cost_price' => 'required|numeric',
            'selling_price' => 'required|numeric',
            'wholesale_price' => 'nullable|numeric',
            'current_stock' => 'required|integer',
            'minimum_stock' => 'nullable|integer',
            'maximum_stock' => 'nullable|integer',
            'unit' => 'required|max:50',
            'location' => 'nullable|max:100',
            'expiry_date' => 'nullable|date',
            'is_active' => 'sometimes',
        ]);

        if (isset($validated['is_active'])) {
            $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_path'] = $path;
        }

        $product = Product::create($validated);
        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return response()->json($product->load(['supplier', 'creator']));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|max:200',
            'category' => 'sometimes|required',
            'cost_price' => 'sometimes|required|numeric',
            'selling_price' => 'sometimes|required|numeric',
            'current_stock' => 'sometimes|required|integer',
            'is_active' => 'sometimes',
        ]);

        if (isset($validated['is_active'])) {
            $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image_path) {
                \Storage::disk('public')->delete($product->image_path);
            }
            $path = $request->file('image')->store('products', 'public');
            $validated['image_path'] = $path;
        }

        $product->update($validated);
        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        // Check if product has sales or purchases
        $hasSales = \App\Models\SaleItem::where('product_id', $product->product_id)->exists();
        $hasPurchases = \App\Models\PurchaseItem::where('product_id', $product->product_id)->exists();

        if ($hasSales || $hasPurchases) {
            return response()->json([
                'message' => 'Badeecadan lama masaxi karo sababtoo ah waxay leedahay taariikh iib ama iibsi. Fadlan kaliya ka dhig mid aan shaqeynayn (Inactive).'
            ], 403);
        }

        if ($product->image_path) {
            \Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();
        return response()->json(null, 204);
    }
}
