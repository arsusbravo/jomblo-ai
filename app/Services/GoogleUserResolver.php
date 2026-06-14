<?php

namespace App\Services;

use App\Models\User;

class GoogleUserResolver
{
    /**
     * Find, link, upgrade, or create a user from verified Google profile data.
     * Mirrors the branching in GoogleAuthController::callback so the web OAuth
     * callback and the native token endpoint resolve users identically.
     *
     * @param  User|null  $current  The currently authenticated user, if any (for guest upgrade).
     */
    public function resolve(string $providerId, string $email, string $name, ?User $current = null): User
    {
        // 1) Already linked via provider_id.
        $user = User::where('provider', 'google')
            ->where('provider_id', $providerId)
            ->first();

        if ($user) {
            return $user;
        }

        // 2) Existing non-guest local account with this email → auto-link.
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

            return $existing;
        }

        // 3) Authenticated guest → upgrade in place (keep credits + chats).
        if ($current && $current->isGuest()) {
            $current->update([
                'email'             => $email,
                'name'              => $current->name ?: $name,
                'provider'          => 'google',
                'provider_id'       => $providerId,
                'is_guest'          => false,
                'email_verified_at' => now(),
            ]);

            return $current;
        }

        // 4) Brand-new full account.
        return User::create([
            'name'              => $name,
            'email'             => $email,
            'provider'          => 'google',
            'provider_id'       => $providerId,
            'is_guest'          => false,
            'email_verified_at' => now(),
        ]);
    }
}
