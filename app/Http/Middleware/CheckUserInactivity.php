<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserInactivity
{
    private const USER_LAST_ACTIVITY_KEY = 'user_last_activity';
    private const ADMIN_LAST_ACTIVITY_KEY = 'admin_last_activity';
    private const ADMIN_REMEMBER_KEY = 'admin_remember';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $isAdmin = $user->isAdmin();

        if ($isAdmin && ($request->session()->get(self::ADMIN_REMEMBER_KEY) === true || Auth::viaRemember())) {
            $request->session()->forget(self::ADMIN_LAST_ACTIVITY_KEY);

            return $next($request);
        }

        $activityKey = $isAdmin ? self::ADMIN_LAST_ACTIVITY_KEY : self::USER_LAST_ACTIVITY_KEY;
        $timeout = $isAdmin
            ? config('session.admin_inactivity_timeout', 30)
            : config('session.user_inactivity_timeout', 30);
        $lastActivity = $request->session()->get($activityKey);

        if ($lastActivity !== null && now()->timestamp - (int) $lastActivity >= $timeout) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $loginUrl = route($isAdmin ? 'admin.login' : 'login');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Your session expired due to inactivity. Please sign in again.'),
                    'session_expired' => true,
                    'login_url' => $loginUrl,
                ], 401);
            }

            return response()->view('auth.session-expired', [
                'loginUrl' => $loginUrl,
            ], 401);
        }

        $request->session()->put($activityKey, now()->timestamp);

        return $next($request);
    }
}