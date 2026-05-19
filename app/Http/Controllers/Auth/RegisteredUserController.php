<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): Response
    {
        /** @var \App\Models\User|null $current */
        $current = Auth::user();

        // Registration is upgrade-only: you must have a guest session first.
        if (! $current || ! $current->isGuest()) {
            return response()->json(['message' => 'guest_required'], 403);
        }

        // The guest already gave name / gender / date_of_birth at /try and
        // confirmed 18+ — only email + password are needed to unlock paying.
        $request->validate([
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $current->update([
            'email'    => $request->email,
            'password' => Hash::make($request->string('password')),
            'is_guest' => false,
        ]);

        event(new Registered($current));

        return response()->noContent();
    }
}
