<div class="wm-foodtrail-nav">
    <a class="wm-foodtrail-brand" href="{{ auth()->check() ? route('home') : url('/') }}">
        <span class="wm-foodtrail-brand-mark">W</span>
        <span>WarisanMakan</span>
    </a>
    <nav class="wm-foodtrail-links" aria-label="{{ __('Food trail navigation') }}">
        <a class="{{ request()->is('foodtrails') ? 'active' : '' }}" href="{{ url('/foodtrails') }}">{{ __('Food Trails') }}</a>
        <a href="{{ route('passport.index') }}">{{ __('Food Passport') }}</a>
        @auth
            <a href="{{ route('profile.show') }}">{{ __('Profile') }}</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">{{ __('Log out') }}</button>
            </form>
        @else
            <a href="{{ route('login') }}">{{ __('Login') }}</a>
        @endauth
    </nav>
</div>
