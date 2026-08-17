<div class="wm-foodtrail-nav">
    <a class="wm-foodtrail-brand" href="{{ auth()->check() ? route('home') : url('/') }}">
        <span class="wm-foodtrail-brand-mark">W</span>
        <span>WarisanMakan</span>
    </a>
    <nav class="wm-foodtrail-links" aria-label="Food trail navigation">
        <a class="{{ request()->is('foodtrails') ? 'active' : '' }}" href="{{ url('/foodtrails') }}">Food Trails</a>
        <a href="{{ route('passport.index') }}">Food Passport</a>
        @auth
            <a href="{{ route('profile.show') }}">Profile</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Log out</button>
            </form>
        @else
            <a href="{{ route('login') }}">Login</a>
        @endauth
    </nav>
</div>
