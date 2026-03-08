<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'username' => ['Macluumaadka soo gelitaanka waa khalad.'],
            ]);
        }

        if (!$user->is_enabled) {
            throw ValidationException::withMessages([
                'username' => ['Akoonkan waa la xiray.'],
            ]);
        }

        // Update user status
        $user->update([
            'is_online' => true,
            'last_login' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Log the login
        \App\Models\AuditLog::create([
            'user_id' => $user->user_id,
            'action' => 'login',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        
        // Calculate durations
        $now = now();
        $lastLogin = $user->last_login;
        $lastLogout = $user->last_logout_at;

        $activeHours = 0;
        if ($lastLogin) {
            $activeHours = $now->diffInMinutes($lastLogin) / 60;
        }

        $inactiveHours = 0;
        if ($lastLogout && $lastLogin) {
            $inactiveHours = $lastLogin->diffInMinutes($lastLogout) / 60;
        }

        // Log the logout with durations
        \App\Models\AuditLog::create([
            'user_id' => $user->user_id,
            'action' => 'logout',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'active_hours' => round($activeHours, 2),
            'inactive_hours' => round($inactiveHours, 2),
        ]);

        // Update user status
        $user->update([
            'is_online' => false,
            'last_logout_at' => $now,
        ]);

        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Si guul leh ayaad uga baxday.'
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:4',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password-ka hadda waa khalad.'],
            ]);
        }

        $user->update([
            'password_hash' => Hash::make($request->new_password),
            'plain_password' => $request->new_password // Keeping it sync if requested earlier
        ]);

        return response()->json([
            'message' => 'Password-ka si guul leh waa loo bedelay.'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
