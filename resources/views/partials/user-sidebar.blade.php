@php
    $userName = auth()->user()->name ?? __('Food Explorer');
    $homeActive = request()->routeIs('home', 'user.dashboard');
    $heritageActive = request()->routeIs('heritage-shops.*') && ! request()->routeIs('heritage-shops.correction-requests.*');
    $passportActive = request()->routeIs('passport.*');
    $foodTrailActive = request()->routeIs('foodtrails.*') || request()->is('start_trail');
    $communityContributionActive = request()->routeIs('community-contribution.*') || request()->routeIs('heritage-shops.correction-requests.*');
    $profileActive = request()->routeIs('profile.*');
    $blindBoxActive = request()->routeIs('blind-box.*');
@endphp

<aside class="user-sidebar" id="user-sidebar">
    <a class="user-brand" href="{{ route('home') }}">
        <span class="user-brand-mark">@include('partials.brand-logo', ['imageClass' => 'user-brand-image', 'placeholderClass' => 'user-brand-placeholder'])</span>
        <span class="user-brand-word">WarisanMakan</span>
    </a>

    <p class="user-nav-label">{{ __('Home') }}</p>
    <nav class="user-nav" aria-label="{{ __('User navigation') }}">
        <a class="user-nav-item {{ $homeActive ? 'active' : '' }}" data-label="{{ __('Dashboard') }}" title="{{ __('Dashboard') }}" href="{{ route('home') }}">
            <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'dashboard'])</span>
            <span class="user-nav-text">{{ __('Dashboard') }}</span>
        </a>
    </nav>

    <p class="user-nav-label">{{ __('Modules') }}</p>
    <nav class="user-nav" aria-label="{{ __('WarisanMakan modules') }}">
        <details class="user-nav-group" {{ $heritageActive ? 'open' : '' }}>
            <summary class="user-nav-item user-nav-parent {{ $heritageActive ? 'is-active' : '' }}" data-label="{{ __('Heritage Discovery') }}" title="{{ __('Heritage Discovery') }}">
                <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'shop'])</span>
                <span class="user-nav-text">{{ __('Heritage Discovery') }}</span>
                <span class="user-nav-chevron" aria-hidden="true">&rsaquo;</span>
            </summary>
            <div class="user-nav-submenu" aria-label="{{ __('Heritage discovery navigation') }}">
                <a class="user-nav-item user-nav-child {{ $heritageActive ? 'active' : '' }}" data-label="{{ __('Heritage Shops') }}" title="{{ __('Heritage Shops') }}" href="{{ route('heritage-shops.index') }}">
                    <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'shop'])</span>
                    <span class="user-nav-text">{{ __('Heritage Shops') }}</span>
                </a>
            </div>
        </details>

        <details class="user-nav-group" {{ $passportActive ? 'open' : '' }}>
            <summary class="user-nav-item user-nav-parent {{ $passportActive ? 'is-active' : '' }}" data-label="{{ __('Food Passport') }}" title="{{ __('Food Passport') }}">
                <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'passport'])</span>
                <span class="user-nav-text">{{ __('Food Passport') }}</span>
                <span class="user-nav-chevron" aria-hidden="true">&rsaquo;</span>
            </summary>
            <div class="user-nav-submenu" aria-label="{{ __('Food passport navigation') }}">
                <a class="user-nav-item user-nav-child {{ request()->routeIs('passport.index') ? 'active' : '' }}" data-label="{{ __('Check In') }}" title="{{ __('Check In') }}" href="{{ route('passport.index') }}#check-in" @guest data-login-required="true" @endguest>
                    <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'passport'])</span>
                    <span class="user-nav-text">{{ __('Check In') }}</span>
                </a>
                <a class="user-nav-item user-nav-child {{ request()->routeIs('passport.history') ? 'active' : '' }}" data-label="{{ __('View Visit History') }}" title="{{ __('View Visit History') }}" href="{{ route('passport.history') }}" @guest data-login-required="true" @endguest>
                    <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'history-list'])</span>
                    <span class="user-nav-text">{{ __('View Visit History') }}</span>
                </a>
                <a class="user-nav-item user-nav-child {{ request()->routeIs('passport.statistics') ? 'active' : '' }}" data-label="{{ __('Passport Statistics') }}" title="{{ __('Passport Statistics') }}" href="{{ route('passport.statistics') }}" @guest data-login-required="true" @endguest>
                    <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'award'])</span>
                    <span class="user-nav-text">{{ __('Passport Statistics') }}</span>
                </a>
                <a class="user-nav-item user-nav-child {{ request()->routeIs('passport.leaderboard') ? 'active' : '' }}" data-label="{{ __('Leaderboard') }}" title="{{ __('Leaderboard') }}" href="{{ route('passport.leaderboard') }}" @guest data-login-required="true" @endguest>
                    <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'ranking'])</span>
                    <span class="user-nav-text">{{ __('Leaderboard') }}</span>
                </a>
            </div>
        </details>

        <details class="user-nav-group" {{ $foodTrailActive ? 'open' : '' }}>
            <summary class="user-nav-item user-nav-parent {{ $foodTrailActive ? 'is-active' : '' }}" data-label="{{ __('Food Trail') }}" title="{{ __('Food Trail') }}">
                <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'map'])</span>
                <span class="user-nav-text">{{ __('Food Trail') }}</span>
                <span class="user-nav-chevron" aria-hidden="true">&rsaquo;</span>
            </summary>
            <div class="user-nav-submenu" aria-label="{{ __('Food trail navigation') }}">
                <a class="user-nav-item user-nav-child {{ request()->routeIs('foodtrails.index') ? 'active' : '' }}" data-label="{{ __('Generate Trail') }}" title="{{ __('Generate Trail') }}" href="{{ route('foodtrails.index') }}" @guest data-login-required="true" @endguest>
                    <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'map'])</span>
                    <span class="user-nav-text">{{ __('Generate Trail') }}</span>
                </a>
                <a class="user-nav-item user-nav-child {{ request()->is('start_trail') ? 'active' : '' }}" data-label="{{ __('Current Trail') }}" title="{{ __('Current Trail') }}" href="{{ url('/start_trail') }}" @guest data-login-required="true" @endguest>
                    <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'route'])</span>
                    <span class="user-nav-text">{{ __('Current Trail') }}</span>
                </a>
                <a class="user-nav-item user-nav-child" data-label="{{ __('Saved Trails') }}" title="{{ __('Saved Trails') }}" href="{{ route('foodtrails.index') }}#initialPanel" @guest data-login-required="true" @endguest>
                    <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'bookmark'])</span>
                    <span class="user-nav-text">{{ __('Saved Trails') }}</span>
                </a>
            </div>
        </details>

        <details class="user-nav-group" {{ $communityContributionActive ? 'open' : '' }}>
            <summary class="user-nav-item user-nav-parent {{ $communityContributionActive ? 'is-active' : '' }}" data-label="{{ __('Community Contribution') }}" title="{{ __('Community Contribution') }}">
                <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'users'])</span>
                <span class="user-nav-text">{{ __('Community Contribution') }}</span>
                <span class="user-nav-chevron" aria-hidden="true">&rsaquo;</span>
            </summary>
            <div class="user-nav-submenu" aria-label="{{ __('Community contribution navigation') }}">
                <a class="user-nav-item user-nav-child {{ request()->routeIs('community-contribution.create', 'community-contribution.edit') || request()->routeIs('heritage-shops.correction-requests.*') ? 'active' : '' }}" data-label="{{ __('Submit shop') }}" title="{{ __('Submit shop') }}" href="{{ route('community-contribution.create') }}" @guest data-login-required="true" @endguest>
                    <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'shop-plus'])</span>
                    <span class="user-nav-text">{{ __('Submit shop') }}</span>
                </a>
                <a class="user-nav-item user-nav-child {{ request()->routeIs('community-contribution.drafts*') ? 'active' : '' }}" data-label="{{ __('Drafts') }}" title="{{ __('Drafts') }}" href="{{ route('community-contribution.drafts') }}" @guest data-login-required="true" @endguest>
                    <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'file-edit'])</span>
                    <span class="user-nav-text">{{ __('Drafts') }}</span>
                </a>
                <a class="user-nav-item user-nav-child {{ request()->routeIs('community-contribution.contributions*') ? 'active' : '' }}" data-label="{{ __('My contributions') }}" title="{{ __('My contributions') }}" href="{{ route('community-contribution.contributions') }}" @guest data-login-required="true" @endguest>
                    <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'history-list'])</span>
                    <span class="user-nav-text">{{ __('My contributions') }}</span>
                </a>
                <a class="user-nav-item user-nav-child {{ request()->routeIs('community-contribution.correction-requests*') ? 'active' : '' }}" data-label="{{ __('Correction Requests') }}" title="{{ __('Correction Requests') }}" href="{{ route('community-contribution.correction-requests') }}" @guest data-login-required="true" @endguest>
                    <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'alert'])</span>
                    <span class="user-nav-text">{{ __('Correction Requests') }}</span>
                </a>
            </div>
        </details>

        <a class="user-nav-item {{ $blindBoxActive ? 'active' : '' }}" data-label="{{ __('Blind Box') }}" title="{{ __('Blind Box') }}" href="{{ route('blind-box.index') }}" @guest data-login-required="true" @endguest>
            <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'box'])</span>
            <span class="user-nav-text">{{ __('Blind Box') }}</span>
        </a>
        <a class="user-nav-item {{ request()->routeIs('blind-box.favourites') ? 'active' : '' }}" data-label="{{ __('Blind Box Favourites') }}" title="{{ __('Blind Box Favourites') }}" href="{{ route('blind-box.favourites') }}">
            <span class="user-nav-icon" aria-hidden="true">&#9825;</span>
            <span class="user-nav-text">{{ __('My Favourites') }}</span>
        </a>
    </nav>

    @auth
        <p class="user-nav-label">{{ __('Account') }}</p>
        <nav class="user-nav" aria-label="{{ __('Account navigation') }}">
            <a class="user-nav-item {{ $profileActive ? 'active' : '' }}" data-label="{{ __('Profile') }}" title="{{ __('Profile') }}" href="{{ route('profile.show') }}">
                <span class="user-nav-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'user'])</span>
                <span class="user-nav-text">{{ __('Profile') }}</span>
            </a>
        </nav>

        <div class="user-sidebar-footer">
            <p class="user-sidebar-name">{{ $userName }}</p>
            <p class="user-sidebar-role">{{ __('WarisanMakan member') }}</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="user-logout" type="submit">{{ __('Sign out') }}</button>
            </form>
        </div>
    @endauth
</aside>
