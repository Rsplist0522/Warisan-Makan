<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showAdminLogin(Request $request)
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        abort_if($request->user(), 403);

        return view('auth.admin-login');
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        abort_if($request->user(), 403);

        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'username' => 'The username or password is incorrect.',
            ]);
        }

        $request->session()->forget(['guest_mode', 'admin_last_activity']);
        $request->session()->regenerate();

        $user = $request->user();

        if (! $user?->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'username' => 'This account does not have administrator access.',
            ]);
        }

        if ($request->boolean('remember')) {
            $request->session()->put('admin_remember', true);
            $request->session()->forget('admin_last_activity');
        } else {
            $request->session()->forget('admin_remember');
            $request->session()->put('admin_last_activity', now()->timestamp);
        }

        if ($user->isBlocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'username' => 'This account has been deactivated. Please contact the administrator.',
            ]);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $email = $googleUser->getEmail();
        abort_unless($email, 422, __('Google did not provide an email address.'));

        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name = $googleUser->getName()
                ?: $googleUser->getNickname()
                ?: 'Google User';

            $user->google_id = $googleUser->getId();
            $user->password = Str::random(40);
        } else {
            // Existing user: keep the profile name edited inside WarisanMakan.
            // Only update Google ID if necessary.
            $user->google_id = $googleUser->getId();
        }

        $user->save();

        if ($user->isBlocked()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => __('Your account has been blocked. Please contact the administrator to regain access.'),
            ]);
        }

        Auth::login($user);
        request()->session()->forget('guest_mode');
        request()->session()->regenerate();
        if ($user->isAdmin()) {
            request()->session()->forget('admin_last_activity');
            request()->session()->put('admin_remember', false);
            request()->session()->put('admin_last_activity', now()->timestamp);
        } else {
            request()->session()->put('user_last_activity', now()->timestamp);
        }

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->route('user.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $wasAdmin = $request->user()?->isAdmin();

        Auth::logout();
        $request->session()->forget(['guest_mode', 'user_last_activity', 'admin_last_activity', 'admin_remember']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($wasAdmin ? 'admin.login' : 'login');
    }
}
