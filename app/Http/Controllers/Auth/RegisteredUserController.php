<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Rules\Turnstile;
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
        $request->validate([
            'cf_turnstile_response' => ['nullable', new Turnstile],
            'name'          => ['required', 'string', 'max:255'],
            'gender'        => ['required', 'in:male,female'],
            'email'         => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
            'date_of_birth' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'age_confirm'   => ['accepted'],
        ], [
            'date_of_birth.required'         => __('auth.dob_required'),
            'date_of_birth.date'             => __('auth.dob_invalid'),
            'date_of_birth.before_or_equal'  => __('auth.dob_underage'),
            'age_confirm.accepted'           => __('auth.age_confirm_required'),
        ]);

        $user = User::create([
            'name'          => $request->name,
            'gender'        => $request->gender,
            'email'         => $request->email,
            'password'      => Hash::make($request->string('password')),
            'date_of_birth' => $request->date_of_birth,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return response()->noContent();
    }
}
