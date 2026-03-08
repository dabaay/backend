<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        
        // Calculate total active hours
        $totalHours = AuditLog::where('user_id', $user->user_id)
            ->where('action', 'logout')
            ->sum('active_hours');

        // Recent login history
        $recentLogins = AuditLog::where('user_id', $user->user_id)
            ->whereIn('action', ['login', 'logout'])
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'user' => $user,
            'stats' => [
                'total_hours_worked' => round($totalHours, 2),
                'joined_at' => $user->created_at->format('Y-m-d'),
                'recent_activity' => $recentLogins
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:15',
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => 'nullable|string|min:6|confirmed',
        ]);

        if (isset($validated['new_password'])) {
            if (!Hash::check($validated['current_password'], $user->password_hash)) {
                return response()->json(['message' => 'Password-ka hadda waa khalad (Current password is incorrect).'], 422);
            }

            $user->password_hash = Hash::make($validated['new_password']);
            $user->plain_password = $validated['new_password']; // Keeping in sync
        }

        // Only admins can update name, email, and phone
        $canUpdateDetails = $user->role === 'admin';

        if ($canUpdateDetails) {
            $user->update([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ]);
        } else {
            // For others, just save the password if it was changed
            $user->save();
        }

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user
        ]);
    }

    public function history(Request $request)
    {
        $logs = AuditLog::where('user_id', $request->user()->user_id)
            ->whereIn('action', ['login', 'logout'])
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }
}
