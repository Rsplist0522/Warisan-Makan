<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        if (
            $request->session()->get('guest_mode') === true &&
            $request->routeIs(
                'home',
                'user.dashboard',
                'heritage-shops.index',
                'heritage-shops.images.show'
            )
        ) {
            return $next($request);
        }

        return redirect()->route('login');
    }
}