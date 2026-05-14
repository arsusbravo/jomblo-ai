<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    protected array $supported = ['en', 'nl', 'fr', 'de'];

    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $request->header('X-Locale', config('app.locale'));

        if (in_array($locale, $this->supported)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
