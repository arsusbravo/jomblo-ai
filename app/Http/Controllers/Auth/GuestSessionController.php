<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Turnstile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class GuestSessionController extends Controller
{
    /**
     * Create a low-friction guest account (no email/password) and log it in
     * via the session cookie. Persists on this browser until cookies clear.
     *
     * @throws ValidationException
     */
    public function store(Request $request): Response
    {
        $turnstile = config('services.turnstile.enabled')
            ? ['required', new Turnstile]
            : ['nullable'];

        $request->validate([
            'cf_turnstile_response' => $turnstile,
            'name'          => ['required', 'string', 'max:255'],
            'gender'        => ['required', 'in:male,female'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'age_confirm'   => ['accepted'],
        ], [
            'date_of_birth.required'        => __('auth.dob_required'),
            'date_of_birth.date'            => __('auth.dob_invalid'),
            'date_of_birth.before_or_equal' => __('auth.dob_underage'),
            'age_confirm.accepted'          => __('auth.age_confirm_required'),
        ]);

        $user = User::create([
            'name'            => $request->name,
            'gender'          => $request->gender,
            'date_of_birth'   => $request->date_of_birth,
            'is_guest'        => true,
            'message_credits' => config('pricing.guest_credits'),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->noContent();
    }
}
