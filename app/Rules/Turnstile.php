<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Disabled on local / when no secret configured
        if (! config('services.turnstile.enabled')) {
            return;
        }

        $secret = config('services.turnstile.secret');

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret'   => $secret,
            'response' => $value,
        ]);

        if (! $response->successful() || ! $response->json('success')) {
            $fail(__('auth.turnstile_failed'));
        }
    }
}
