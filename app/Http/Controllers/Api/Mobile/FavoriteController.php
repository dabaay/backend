<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CustomerFavorite;
use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $customerId = $request->user('customer')->customer_id;

        $favorites = CustomerFavorite::where('customer_id', $customerId)
            ->with('product')
            ->get()
            ->filter(fn ($f) => $f->product && $f->product->is_active)
            ->map(function ($f) use ($customerId) {
                $product = $f->product;
                $product->is_favorite    = true;
                $product->average_rating = round((float) $product->ratings()->avg('rating'), 1);
                return $product;
            })
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $favorites,
        ]);
    }

    public function store(Request $request, $productId)
    {
        $customerId = $request->user('customer')->customer_id;
        $product    = Product::where('is_active', true)->findOrFail($productId);

        CustomerFavorite::firstOrCreate([
            'customer_id' => $customerId,
            'product_id'  => $product->product_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Added to favorites',
        ]);
    }

    public function destroy(Request $request, $productId)
    {
        $customerId = $request->user('customer')->customer_id;

        CustomerFavorite::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Removed from favorites',
        ]);
    }
}
