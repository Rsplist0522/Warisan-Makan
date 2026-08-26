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
        $language = $request->user()?->language ?: config('app.fallback_locale', 'en');
        $supportedLanguages = ['en', 'ms', 'zh'];

        App::setLocale(in_array($language, $supportedLanguages, true) ? $language : 'en');

        return $next($request);
    }
}