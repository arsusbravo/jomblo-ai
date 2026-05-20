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

    /**
     * Self-service account deletion (GDPR right to erasure).
     * Deletes the user; FK cascades remove conversations, messages, and
     * pending payments. The Stripe customer record is intentionally left
     * in Stripe for accounting/legal records.
     */
    public function destroyAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        // Safety: admins must not be deletable from the regular profile button.
        if ($user->isAdmin()) {
            return response()->json(['message' => 'admin_cannot_self_delete'], 403);
        }

        // Typed confirmation prevents accidental clicks.
        $request->validate([
            'confirm' => ['required', 'string', 'in:DELETE'],
        ]);

        $user->delete();

        // Invalidate the session so the now-orphaned cookie can't be reused.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'account_deleted']);
    }
}
