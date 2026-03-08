<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRating;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $customerId = $request->user('customer')->customer_id;

        $query = Product::where('is_active', true);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $products = $query->orderBy('name')->paginate(20);

        $products->getCollection()->transform(function ($product) use ($customerId) {
            $product->is_favorite     = $product->favorites()->where('customer_id', $customerId)->exists();
            $product->average_rating  = round((float) $product->ratings()->avg('rating'), 1);
            $product->rating_count    = $product->ratings()->count();
            return $product;
        });

        return response()->json([
            'success' => true,
            'data'    => $products,
        ]);
    }

    public function show(Request $request, $id)
    {
        $customerId = $request->user('customer')->customer_id;
        $product    = Product::where('is_active', true)->findOrFail($id);

        $product->is_favorite    = $product->favorites()->where('customer_id', $customerId)->exists();
        $product->average_rating = round((float) $product->ratings()->avg('rating'), 1);
        $product->rating_count   = $product->ratings()->count();

        // Latest 5 comments
        $product->comments = $product->ratings()
            ->with('customer:customer_id,full_name,profile_image')
            ->whereNotNull('comment')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $product,
        ]);
    }

    public function rate(Request $request, $id)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $product    = Product::findOrFail($id);
        $customerId = $request->user('customer')->customer_id;

        $rating = ProductRating::updateOrCreate(
            ['customer_id' => $customerId, 'product_id' => $product->product_id],
            ['rating' => $request->rating, 'comment' => $request->comment]
        );

        return response()->json([
            'success' => true,
            'data'    => $rating,
            'message' => 'Rating submitted successfully',
        ]);
    }

    public function comments(Request $request, $id)
    {
        $product  = Product::findOrFail($id);
        $comments = $product->ratings()
            ->with('customer:customer_id,full_name,profile_image')
            ->whereNotNull('comment')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $comments,
        ]);
    }
}
