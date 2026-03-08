<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users',
            'password' => 'required|string|min:6',
            'full_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:15',
            'role' => ['required', Rule::in(['admin', 'cashier', 'app_manager'])],
            'permissions' => 'nullable|array',
            'is_enabled' => 'boolean',
        ]);

        $password = $validated['password'];

        $user = User::create([
            'username' => $validated['username'],
            'password_hash' => Hash::make($password),
            'plain_password' => $password, // As requested by user
            'full_name' => $validated['full_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'permissions' => $validated['permissions'] ?? null,
            'is_enabled' => $validated['is_enabled'] ?? true,
        ]);

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('users')->ignore($user->user_id, 'user_id')],
            'password' => 'nullable|string|min:6',
            'full_name' => 'sometimes|required|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:15',
            'role' => ['sometimes', 'required', Rule::in(['admin', 'cashier', 'app_manager'])],
            'permissions' => 'nullable|array',
            'is_enabled' => 'sometimes|boolean',
        ]);

        if (isset($validated['password'])) {
            $user->password_hash = Hash::make($validated['password']);
            $user->plain_password = $validated['password'];
        }

        $user->update(array_merge(
            $request->only(['username', 'full_name', 'email', 'phone', 'role', 'is_enabled', 'permissions']),
            isset($validated['password']) ? [
                'password_hash' => $user->password_hash,
                'plain_password' => $user->plain_password
            ] : []
        ));

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        // Don't allow deleting the only admin or yourself
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json(['message' => 'Ma masaxi kartid admin-ka kaliya ee jira.'], 403);
        }

        if ($user->user_id === auth()->id()) {
            return response()->json(['message' => 'Ma masaxi kartid akoonka aad ku jirto.'], 403);
        }

        $user->delete();
        return response()->json(null, 204);
    }
}
