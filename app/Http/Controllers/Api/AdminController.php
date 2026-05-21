<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $since = now()->subDays(7);

        $stats = [
            // Robust against null roles (some accounts predate the role column default).
            'total_users'   => User::where(fn ($q) => $q->whereNull('role')->orWhere('role', '!=', 'admin'))->count(),
            'total_admins'  => User::where('role', 'admin')->count(),
            'guest_users'   => User::where('is_guest', true)->count(),
            'new_users_7d'  => User::where('created_at', '>=', $since)->count(),
            'characters'    => [
                'total'     => Character::count(),
                'active'    => Character::where('is_active', true)->count(),
                'inactive'  => Character::where('is_active', false)->count(),
                'female'    => Character::where('gender', 'female')->count(),
                'male'      => Character::where('gender', 'male')->count(),
                'anime'     => Character::where('category', 'anime')->count(),
                'realistic' => Character::where('category', 'realistic')->count(),
            ],
        ];

        $newUsers = User::where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'name', 'email', 'is_guest', 'role', 'created_at']);

        return response()->json([
            'message'   => 'Welcome to the Admin Dashboard',
            'stats'     => $stats,
            'new_users' => $newUsers,
        ]);
    }

    public function users(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'role', 'message_credits', 'created_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['users' => $users]);
    }

    public function storeUser(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', 'in:admin,user'],
            'password' => ['required', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'User created.', 'user' => $user->only('id', 'name', 'email', 'role', 'created_at')], 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'role' => ['required', 'in:admin,user'],
            'password' => ['nullable', Password::min(8)],
        ]);

        $data = $request->only('name', 'email', 'role');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json(['message' => 'User updated.', 'user' => $user->only('id', 'name', 'email', 'role', 'created_at')]);
    }

    public function deleteUser(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function grantCredits(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'credits' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        $user->increment('message_credits', $request->credits);

        return response()->json([
            'message' => 'Credits granted.',
            'user' => $user->only('id', 'name', 'email', 'role', 'message_credits', 'created_at'),
        ]);
    }
}
