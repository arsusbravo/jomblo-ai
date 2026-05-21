<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $social = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('Google OAuth callback failed: ' . $e->getMessage());
            return redirect('/login?social=error');
        }

        $providerId = (string) $social->getId();
        $email      = $social->getEmail();
        $name       = $social->getName() ?: ($social->getNickname() ?: 'Friend');

        if (! $email) {
            // Google should always return email when 'email' scope is granted.
            return redirect('/login?social=error');
        }

        // 1) Already linked to a user via provider_id? Log them in.
        $user = User::where('provider', 'google')
            ->where('provider_id', $providerId)
            ->first();

        if (! $user) {
            // 2) Existing local account with this email? Auto-link (only if NOT a guest).
            $existing = User::whereNotNull('email')
                ->where('email', $email)
                ->where(fn ($q) => $q->where('is_guest', false)->orWhereNull('is_guest'))
                ->first();

            if ($existing) {
                $existing->forceFill([
                    'provider'          => 'google',
                    'provider_id'       => $providerId,
                    'email_verified_at' => $existing->email_verified_at ?: now(),
                ])->save();
                $user = $existing;
            }
        }

        if (! $user) {
            $current = Auth::user();

            // 3) Authenticated guest? Upgrade in place (keep credits + chats).
            if ($current && $current->isGuest()) {
                $current->update([
                    'email'             => $email,
                    'name'              => $current->name ?: $name,
                    'provider'          => 'google',
                    'provider_id'       => $providerId,
                    'is_guest'          => false,
                    'email_verified_at' => now(),
                ]);
                $user = $current;
            } else {
                // 4) Brand-new full account.
                $user = User::create([
                    'name'              => $name,
                    'email'             => $email,
                    'provider'          => 'google',
                    'provider_id'       => $providerId,
                    'is_guest'          => false,
                    'email_verified_at' => now(),
                ]);
            }
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect('/dashboard');
    }
}
