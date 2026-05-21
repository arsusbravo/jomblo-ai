<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Emergency logout — visit this URL directly in the browser to clear a broken session
Route::get('/force-logout', function () {
    Auth::guard('web')->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/login');
});

// Register explicit auth routes (login, register, OAuth callbacks, verify email)
// BEFORE the SPA catch-all — otherwise the catch-all swallows every GET and
// the auth routes never match. Order matters in Laravel routing.
require __DIR__.'/auth.php';

// All other web routes serve the Vue SPA.
// The HTML must never be cached: it references hashed asset filenames that
// change every deploy. A stale HTML => browser requests deleted chunks =>
// "Failed to fetch dynamically imported module". The hashed assets themselves
// are still safely cacheable (their names change per build).
Route::get('/{any?}', function () {
    return response()
        ->view('app')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache');
})->where('any', '.*');
