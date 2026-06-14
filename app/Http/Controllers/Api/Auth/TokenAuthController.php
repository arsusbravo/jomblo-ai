<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleUserResolver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class TokenAuthController extends Controller
{
    /** Issue a Sanctum token and return it with the user. */
    private function respondWithToken(User $user, ?string $device = null): JsonResponse
    {
        $token = $user->createToken($device ?: 'mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user->fresh(),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'       => ['required', 'string', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $key = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! $user->password || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($key);

        return $this->respondWithToken($user, $request->device_name);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
            'gender'        => ['required', 'in:male,female'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'age_confirm'   => ['accepted'],
            'device_name'   => ['nullable', 'string', 'max:255'],
        ], [
            'date_of_birth.before_or_equal' => __('auth.dob_underage'),
            'age_confirm.accepted'          => __('auth.age_confirm_required'),
        ]);

        $user = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->string('password')),
            'gender'          => $request->gender,
            'date_of_birth'   => $request->date_of_birth,
            'is_guest'        => false,
            'message_credits' => config('pricing.guest_credits'),
        ]);

        event(new Registered($user));

        return $this->respondWithToken($user, $request->device_name);
    }

    public function guest(Request $request): JsonResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'gender'        => ['required', 'in:male,female'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'age_confirm'   => ['accepted'],
            'device_name'   => ['nullable', 'string', 'max:255'],
        ], [
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

        return $this->respondWithToken($user, $request->device_name);
    }

    public function google(Request $request, GoogleUserResolver $resolver): JsonResponse
    {
        $request->validate([
            'id_token'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $info = Http::get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $request->id_token]);

        if ($info->failed()) {
            throw ValidationException::withMessages(['id_token' => __('auth.failed')]);
        }

        $payload  = $info->json();
        $clientId = config('services.google.client_id');

        // Verify the token was minted for this app and the email is confirmed.
        $audOk = isset($payload['aud']) && $payload['aud'] === $clientId;
        $emailVerified = ($payload['email_verified'] ?? 'false') === 'true' || ($payload['email_verified'] ?? false) === true;

        if (! $audOk || ! $emailVerified || empty($payload['sub']) || empty($payload['email'])) {
            throw ValidationException::withMessages(['id_token' => __('auth.failed')]);
        }

        $user = $resolver->resolve(
            (string) $payload['sub'],
            $payload['email'],
            $payload['name'] ?? ($payload['given_name'] ?? 'Friend'),
            $request->user(),
        );

        return $this->respondWithToken($user, $request->device_name);
    }

    public function upgrade(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isGuest()) {
            return response()->json(['message' => 'already_registered'], 409);
        }

        $request->validate([
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'email'    => $request->email,
            'password' => Hash::make($request->string('password')),
            'is_guest' => false,
        ]);

        event(new Registered($user));

        // Reissue: drop the current token, hand back a fresh one.
        $request->user()->currentAccessToken()->delete();

        return $this->respondWithToken($user, 'mobile');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'logged_out']);
    }
}
