<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Welcome to your dashboard, ' . $request->user()->name,
            'user' => $request->user(),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'email', 'unique:users,email,' . $request->user()->id],
            'gender' => ['sometimes', 'in:male,female'],
        ]);

        $request->user()->update($request->only('name', 'email', 'gender'));

        return response()->json(['message' => 'Profile updated.', 'user' => $request->user()->fresh()]);
    }
}
