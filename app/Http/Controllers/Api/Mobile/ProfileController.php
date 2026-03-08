<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user('customer');
        
        // Calculate total successful purchases
        $totalPurchases = \App\Models\Sale::where('customer_app_id', $customer->customer_id)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $data = $customer->toArray();
        $data['total_purchases'] = (float)$totalPurchases;

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function update(Request $request)
    {
        $customer = $request->user('customer');

        $request->validate([
            'full_name' => 'sometimes|string|max:100',
            'phone'     => 'sometimes|string|unique:customerApp,phone,' . $customer->customer_id . ',customer_id',
            'address'   => 'nullable|string',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['full_name', 'phone', 'address']);

        if ($request->hasFile('profile_image')) {
            // Delete old image
            if ($customer->profile_image) {
                Storage::disk('public')->delete($customer->profile_image);
            }
            $path = $request->file('profile_image')->store('customer_profiles', 'public');
            $data['profile_image'] = $path;
        }

        $customer->update($data);

        return response()->json([
            'success' => true,
            'data'    => $customer->fresh(),
            'message' => 'Profile updated successfully',
        ]);
    }
}
