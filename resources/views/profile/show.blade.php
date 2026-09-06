@extends('layouts.user')

@section('title', __('Profile'))
@section('user-topbar-title', __('Profile'))
@section('user-topbar-subtitle', __('Manage your WarisanMakan account and identity.'))

@section('user-topbar-actions')
<a class="user-topbar-link" href="{{ route('profile.edit') }}">{{ __('Edit Profile') }}</a>
<a class="user-topbar-link" href="{{ route('home') }}">{{ __('Back to Home') }}</a>
@endsection

@push('styles')
<style>
        :root {
            color-scheme: light;
            --wm-bg: #fbf2e7;
            --wm-panel: #fff8f0;
            --wm-ink: #5b4335;
            --wm-muted: #8c6f5f;
            --wm-border: rgba(177, 140, 106, .16);
            --wm-accent: #b34d35;
            --wm-accent-dark: #7d3020;
            --wm-gold: #d19c3b;
            --wm-gold-light: #f7d488;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(180deg, #fbf2e7 0%, #f5e4d5 100%);
            color: var(--wm-ink);
        }

        a { color: inherit; text-decoration: none; }
        button, input, textarea { font: inherit; }

        .page {
            max-width: 820px;
            margin: 0 auto;
            padding: 24px 22px 42px;
            position: relative;
        }

        /* ---- Ambient floating sparkles across the page ---- */
        .page::before,
        .page::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            filter: blur(70px);
            opacity: .35;
            z-index: 0;
            pointer-events: none;
        }
        .page::before {
            top: -60px;
            left: -80px;
            background: var(--wm-gold-light);
            animation: drift-a 12s ease-in-out infinite;
        }
        .page::after {
            bottom: 40px;
            right: -100px;
            background: var(--wm-accent);
            opacity: .15;
            animation: drift-b 14s ease-in-out infinite;
        }
        @keyframes drift-a {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, 20px) scale(1.15); }
        }
        @keyframes drift-b {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-25px, -15px) scale(1.1); }
        }

        .sparkle-particle {
            position: absolute;
            pointer-events: none;
            color: var(--wm-gold);
            font-size: .8rem;
            opacity: 0;
            animation: float-sparkle 7s infinite ease-in-out;
            z-index: 1;
        }
        .sparkle-particle:nth-child(1) { top: 10%; left: 6%; animation-delay: 0s; }
        .sparkle-particle:nth-child(2) { top: 30%; right: 8%; font-size: 1.1rem; animation-delay: 2s; }
        .sparkle-particle:nth-child(3) { bottom: 15%; left: 10%; font-size: .6rem; animation-delay: 4s; }
        .sparkle-particle:nth-child(4) { top: 55%; right: 4%; font-size: .9rem; animation-delay: 1.5s; }

        @keyframes float-sparkle {
            0% { transform: translateY(0) scale(.5); opacity: 0; }
            30% { opacity: .55; }
            70% { opacity: .55; }
            100% { transform: translateY(-70px) scale(0); opacity: 0; }
        }

        .topbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            padding: 26px 28px;
            margin-bottom: 22px;
            border-radius: 22px;
            position: relative;
            background: linear-gradient(180deg, #fff7f0 0%, #fdf0e3 100%);
            border: 1px solid rgba(177, 140, 106, .2);
            box-shadow: 0 18px 36px rgba(104, 71, 42, .1);
            overflow: hidden;
            z-index: 1;
        }

        /* subtle shifting gold underline glow */
        .topbar::after {
            content: "";
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--wm-gold), var(--wm-accent), var(--wm-gold), transparent);
            background-size: 200% auto;
            animation: shimmer-line 4s linear infinite;
        }
        @keyframes shimmer-line { to { background-position: -200% center; } }

        .section-heading {
            margin: 0;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1.95rem;
            line-height: 1.05;
            color: #7d4634;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-heading .heading-emoji {
            display: inline-block;
            animation: gentle-sway 3s ease-in-out infinite;
        }
        @keyframes gentle-sway {
            0%, 100% { transform: rotate(-6deg); }
            50% { transform: rotate(6deg); }
        }

        .section-copy {
            margin: 10px 0 0;
            color: var(--wm-muted);
            max-width: 620px;
            line-height: 1.75;
        }

        .section-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .button,
        .button-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 20px;
            border-radius: 999px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            font-weight: 700;
            white-space: nowrap;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .button {
            background: linear-gradient(135deg, var(--wm-accent), var(--wm-accent-dark));
            color: #fff;
            box-shadow: 0 8px 18px rgba(179, 77, 53, .3);
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(179, 77, 53, .4);
        }

        .button-secondary {
            background: transparent;
            color: var(--wm-ink);
            border-color: var(--wm-border);
        }
        .button-secondary:hover {
            transform: translateY(-2px);
            border-color: var(--wm-gold);
            background: #fff7ec;
        }

        .status-alert {
            margin-bottom: 18px;
            padding: 16px 18px;
            border-radius: 14px;
            background: rgba(70, 128, 74, .12);
            color: #14461f;
            border: 1px solid rgba(70, 128, 74, .18);
        }

        /* ---- Single unified profile card, now with a shimmering gold border ---- */

        .profile-card {
            position: relative;
            border-radius: 30px;
            background: var(--wm-panel);
            box-shadow: 0 20px 46px rgba(113, 80, 53, .14);
            overflow: hidden;
            z-index: 1;
            padding: 2px;
            background-image: linear-gradient(var(--wm-panel), var(--wm-panel)),
                conic-gradient(from 0deg, var(--wm-gold-light), var(--wm-accent), var(--wm-gold), var(--wm-gold-light));
            background-origin: border-box;
            background-clip: padding-box, border-box;
            border: 2px solid transparent;
            animation: rotate-border 8s linear infinite;
        }
        @keyframes rotate-border {
            to { background-image: linear-gradient(var(--wm-panel), var(--wm-panel)),
                conic-gradient(from 360deg, var(--wm-gold-light), var(--wm-accent), var(--wm-gold), var(--wm-gold-light)); }
        }

        .profile-card-inner {
            background: var(--wm-panel);
            border-radius: 28px;
            overflow: hidden;
        }

        .profile-identity {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 30px 28px 26px;
            border-bottom: 1px solid var(--wm-border);
            position: relative;
        }

        .avatar-ring {
            position: relative;
            flex: 0 0 auto;
            width: 76px;
            height: 76px;
            border-radius: 20px;
            padding: 4px;
            background: conic-gradient(from 0deg, var(--wm-gold), var(--wm-accent), var(--wm-gold-light), var(--wm-gold));
            animation: rotate-border 6s linear infinite;
        }

        .avatar {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: linear-gradient(160deg, #f3e3d5, #e6d0bb);
            color: #a14d39;
            font-size: 1.5rem;
            font-weight: 800;
            overflow: hidden;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .identity-text {
            min-width: 0;
        }

        .identity-name {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: #6f432a;
            overflow-wrap: anywhere;
        }

        .identity-email {
            margin: 4px 0 0;
            color: #8a6f5f;
            font-size: .92rem;
            overflow-wrap: anywhere;
        }

        .identity-badge {
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(209, 156, 59, .22), rgba(179, 77, 53, .16));
            color: #8a5f19;
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            box-shadow: 0 3px 8px rgba(209, 156, 59, .18);
        }

        .profile-fields {
            padding: 24px 28px 28px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .profile-field {
            display: grid;
            gap: 6px;
            padding: 16px 18px;
            border-radius: 16px;
            background: #fff7f1;
            border: 1px solid rgba(177, 140, 106, .14);
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        }

        .profile-field:hover {
            transform: translateY(-3px);
            border-color: rgba(209, 156, 59, .4);
            box-shadow: 0 10px 22px rgba(179, 77, 53, .1);
        }

        .profile-field.span-2 {
            grid-column: 1 / -1;
        }

        .profile-field label {
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #8e6e5e;
        }

        .profile-field span {
            color: #5b4335;
            line-height: 1.65;
        }

        .profile-field span.empty {
            color: #9b8171;
            font-style: italic;
        }

        @media (max-width: 640px) {
            .profile-fields { grid-template-columns: 1fr; }
            .profile-identity { flex-wrap: wrap; }
        }

        /* ---- Earned badges, now with a shine sweep on hover ---- */

        .badges-section {
            padding: 4px 28px 30px;
            border-top: 1px solid var(--wm-border);
        }

        .badges-section h2 {
            margin: 24px 0 14px;
            color: #7d4634;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
        }

        .earned-badge {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            padding: 15px;
            border: 1px solid rgba(209, 156, 59, .3);
            border-radius: 16px;
            background: linear-gradient(135deg, #fff9ed, #fff2d3);
            overflow: hidden;
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .earned-badge:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 26px rgba(209, 156, 59, .25);
        }

        /* diagonal shine sweep */
        .earned-badge::before {
            content: "";
            position: absolute;
            top: 0;
            left: -150%;
            width: 60%;
            height: 100%;
            background: linear-gradient(115deg, transparent, rgba(255,255,255,.75), transparent);
            transform: skewX(-20deg);
            transition: left .7s ease;
        }
        .earned-badge:hover::before {
            left: 150%;
        }

        .earned-badge-icon {
            flex: 0 0 auto;
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            color: #8a5f19;
            background: rgba(209, 156, 59, .25);
            font-size: 1.5rem;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(209, 156, 59, .25);
        }

        .earned-badge-content {
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .earned-badge-name {
            margin: 0;
            color: #6f432a;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .earned-badge-description {
            margin: 4px 0 0;
            color: var(--wm-muted);
            font-size: .82rem;
            line-height: 1.4;
        }

        .earned-badge-date {
            margin: 6px 0 0;
            color: #9b8171;
            font-size: .72rem;
        }
    </style>
@endpush

@section('content')
<div class="page">
        <span class="sparkle-particle">✦</span>
        <span class="sparkle-particle">✦</span>
        <span class="sparkle-particle">✦</span>
        <span class="sparkle-particle">✦</span>

        <header class="topbar">
            <div>
                <h1 class="section-heading"><span class="heading-emoji">🏮</span> {{ __('Profile') }}</h1>
                <p class="section-copy">{{ __('Manage your WarisanMakan identity, update your contact details, and keep your profile photo current for a personalized experience.') }}</p>
            </div>
            <div class="section-actions">
                <a class="button-secondary" href="{{ route('profile.edit') }}">{{ __('Edit Profile') }}</a>
                <a class="button" href="{{ route('home') }}">{{ __('Back to dashboard') }}</a>
            </div>
        </header>

        <section class="profile-card">
            <div class="profile-card-inner">
                <div class="profile-identity">
                    <div class="avatar-ring">
                        <div class="avatar">
                            @if ($user->profile_photo)
                                <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }} profile photo">
                            @else
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            @endif
                        </div>
                    </div>
                    <div class="identity-text">
                        <p class="identity-name">{{ $user->name }}</p>
                        <p class="identity-email">{{ $user->email }}</p>
                        <span class="identity-badge">✨ {{ __($user->isAdmin() ? 'Admin' : 'Member') }}</span>
                    </div>
                </div>

                <div class="profile-fields">
                    <div class="profile-field">
                        <label>{{ __('Phone') }}</label>
                        <span class="{{ $user->phone ? '' : 'empty' }}">{{ $user->phone ?: __('Not provided') }}</span>
                    </div>

                    <div class="profile-field">
                        <label>{{ __('City') }}</label>
                        <span class="{{ $user->city ? '' : 'empty' }}">{{ $user->city ?: __('Not provided') }}</span>
                    </div>

                    <div class="profile-field">
                        <label>{{ __('Member since') }}</label>
                        <span>{{ $user->created_at->format('F j, Y') }}</span>
                    </div>

                    <div class="profile-field">
                        <label>{{ __('Role') }}</label>
                        <span>{{ __($user->isAdmin() ? 'Admin' : 'Member') }}</span>
                    </div>

                    <div class="profile-field span-2">
                        <label>{{ __('Bio') }}</label>
                        <span class="{{ $user->bio ? '' : 'empty' }}">{{ $user->bio ?: __('Share a little about your food heritage interests.') }}</span>
                    </div>
                </div>

                @if ($badges->isNotEmpty())
                    <div class="badges-section" aria-labelledby="earned-badges-title">
                        <h2 id="earned-badges-title">🏆 {{ __('Earned badges') }}</h2>

                        <div class="badges-grid">
                            @foreach ($badges as $userBadge)
                                @if ($userBadge->badge)
                                    <article class="earned-badge">
                                        <div class="earned-badge-icon" aria-hidden="true">
                                            {{ $userBadge->badge->icon ?: '★' }}
                                        </div>

                                        <div class="earned-badge-content">
                                            <h3 class="earned-badge-name">
                                                {{ $userBadge->badge->badge_name }}
                                            </h3>

                                            @if ($userBadge->badge->description)
                                                <p class="earned-badge-description">
                                                    {{ $userBadge->badge->description }}
                                                </p>
                                            @endif

                                            @if ($userBadge->earned_at)
                                                <p class="earned-badge-date">
                                                    {{ __('Earned :date', ['date' => $userBadge->earned_at->format('F j, Y')]) }}
                                                </p>
                                            @endif
                                        </div>
                                    </article>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection