<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CustomerApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'full_name'             => 'required|string|max:100',
            'username'              => 'required|string|max:50|unique:customerApp,username',
            'phone'                 => 'required|string|unique:customerApp,phone',
            'address'               => 'nullable|string',
            'password'              => 'required|string|min:6|confirmed',
        ]);

        $customer = CustomerApp::create([
            'full_name'       => $request->full_name,
            'username'        => $request->username,
            'phone'           => $request->phone,
            'address'         => $request->address,
            'password'        => $request->password,
            'status'          => 'active',
            'registration_date' => now(),
            'current_balance' => 0,
        ]);

        $token = $customer->createToken('mobile_app')->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => [
                'customer' => $customer,
                'token'    => $token,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $customer = CustomerApp::where('username', $request->username)
            ->orWhere('phone', $request->username)
            ->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($customer->status !== 'active' || $customer->is_blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Account is blocked or inactive. Please contact the store.',
            ], 403);
        }

        $token = $customer->createToken('mobile_app')->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => [
                'customer' => $customer,
                'token'    => $token,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user('customer')->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        $customer = $request->user('customer');

        if (! Hash::check($request->current_password, $customer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        $customer->password = Hash::make($request->new_password);
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }
}
