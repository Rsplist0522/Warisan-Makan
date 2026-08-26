<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLanguages = ['en', 'ms', 'zh'];

        $language = $request->session()->get('locale')
            ?? $request->user()?->language
            ?? config('app.fallback_locale', 'en');

        if (! in_array($language, $supportedLanguages, true)) {
            $language = 'en';
        }

        App::setLocale($language);

        return $next($request);
    }
}
