<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\SetLocale::class,
        ]);

        // /register and /login are web routes (routes/auth.php) — they need SetLocale too
        // so validation messages respect the X-Locale header sent by the frontend.
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'auth.user' => \App\Http\Middleware\UserMiddleware::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return literal JSON null (not {}) for the /api/user guest check — Symfony's JsonResponse
        // converts PHP null to an empty ArrayObject which serializes as {}, and !!{} is truthy in JS.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/user')) {
                return response('null', 200, ['Content-Type' => 'application/json']);
            }
        });
    })->create();
