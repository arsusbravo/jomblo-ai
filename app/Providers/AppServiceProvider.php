<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Required for older MySQL/MariaDB with utf8mb4: limits indexed VARCHAR keys to 191 chars (764 bytes < 767 limit)
        Schema::defaultStringLength(191);

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // Anti-farming: cap brand-new guest accounts per IP per hour.
        RateLimiter::for('guest-register', fn (Request $request) =>
            Limit::perHour(config('pricing.guest_register_per_hour'))->by($request->ip())
        );
    }
}
