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

// All web routes serve the Vue SPA
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');

require __DIR__.'/auth.php';
