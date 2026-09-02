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
            --paper: #f6ecd9;
            --paper-2: #efe0c2;
            --card: #fdf7ea;
            --ink: #3b2b1f;
            --ink-soft: #7a6349;
            --line: rgba(59, 43, 31, .16);
            --stamp-red: #a3402c;
            --stamp-red-dark: #7c2f20;
            --stamp-teal: #2f6b5e;
            --thread: #c8963f;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--paper);
            background-image:
                repeating-linear-gradient(0deg, rgba(59,43,31,.025) 0px, rgba(59,43,31,.025) 1px, transparent 1px, transparent 3px);
            color: var(--ink);
        }

        a { color: inherit; text-decoration: none; }
        button, input, textarea { font: inherit; }

        .page {
            max-width: 780px;
            margin: 0 auto;
            padding: 28px 22px 48px;
        }

        /* ---- Boarding-pass header ---- */

        .pass-header {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: stretch;
            border-radius: 18px;
            background: var(--card);
            border: 1px solid var(--line);
            box-shadow: 0 14px 30px rgba(59, 43, 31, .08);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .pass-header-main {
            padding: 26px 28px;
        }

        .pass-eyebrow {
            font-size: .78rem;
            color: var(--ink-soft);
            font-variant: small-caps;
            letter-spacing: .02em;
        }

        .pass-title {
            margin: 4px 0 8px;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1.9rem;
            color: var(--stamp-red-dark);
        }

        .pass-copy {
            margin: 0;
            color: var(--ink-soft);
            max-width: 480px;
            line-height: 1.7;
        }

        .pass-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        .btn,
        .btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 18px;
            border-radius: 10px;
            border: 1px solid transparent;
            cursor: pointer;
            font-weight: 700;
            font-size: .92rem;
            white-space: nowrap;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
        }

        .btn {
            background: var(--stamp-red);
            color: #fff8ef;
            box-shadow: 0 8px 16px rgba(163, 64, 44, .28);
        }
        .btn:hover { transform: translateY(-1px); background: var(--stamp-red-dark); }

        .btn-outline {
            background: transparent;
            color: var(--ink);
            border-color: var(--line);
        }
        .btn-outline:hover { border-color: var(--stamp-red); color: var(--stamp-red-dark); }

        /* perforated stub */
        .pass-stub {
            position: relative;
            width: 172px;
            padding: 26px 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 18px;
            background: var(--stamp-red);
            color: #fbe9dd;
            border-left: 2px dashed rgba(255, 246, 234, .45);
        }
        .pass-stub::before,
        .pass-stub::after {
            content: "";
            position: absolute;
            left: -11px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--paper);
        }
        .pass-stub::before { top: -11px; }
        .pass-stub::after { bottom: -11px; }

        .stub-stat {
            text-align: left;
        }
        .stub-stat .num {
            display: block;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1.6rem;
            line-height: 1;
        }
        .stub-stat .label {
            display: block;
            margin-top: 4px;
            font-size: .74rem;
            color: rgba(251, 233, 221, .8);
            font-variant: small-caps;
        }

        /* ---- Passport card ---- */

        .passport {
            border-radius: 20px;
            background: var(--card);
            border: 1px solid var(--line);
            box-shadow: 0 16px 34px rgba(59, 43, 31, .1);
            overflow: hidden;
        }

        .passport-top {
            display: grid;
            grid-template-columns: 190px 1fr;
        }

        @media (max-width: 620px) {
            .pass-header { grid-template-columns: 1fr; }
            .pass-stub {
                flex-direction: row;
                width: auto;
                border-left: none;
                border-top: 2px dashed rgba(255, 246, 234, .45);
            }
            .pass-stub::before, .pass-stub::after {
                left: auto; top: -11px;
            }
            .pass-stub::before { left: -11px; }
            .pass-stub::after { right: -11px; left: auto; }
            .passport-top { grid-template-columns: 1fr; }
        }

        .id-panel {
            padding: 30px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
            border-right: 1px dashed var(--line);
            background: var(--paper-2);
        }

        .id-seal {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            border: 3px solid var(--stamp-red);
            padding: 4px;
        }
        .id-seal-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #f3e3d0;
            color: var(--stamp-red-dark);
            font-size: 1.9rem;
            font-weight: 800;
            font-family: Georgia, serif;
            overflow: hidden;
        }
        .id-seal-inner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .id-name {
            margin: 6px 0 0;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1.15rem;
            color: var(--ink);
            overflow-wrap: anywhere;
        }
        .id-email {
            margin: 0;
            font-size: .84rem;
            color: var(--ink-soft);
            overflow-wrap: anywhere;
        }
        .id-role {
            margin-top: 6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 999px;
            border: 1px solid var(--stamp-teal);
            color: var(--stamp-teal);
            font-size: .74rem;
            font-weight: 700;
        }

        /* manifest list */
        .manifest {
            padding: 24px 26px;
            display: flex;
            flex-direction: column;
        }

        .manifest-row {
            display: flex;
            align-items: baseline;
            gap: 10px;
            padding: 12px 0;
            border-bottom: 1px dotted var(--line);
        }
        .manifest-row:last-child { border-bottom: none; }

        .manifest-label {
            flex: 0 0 auto;
            font-variant: small-caps;
            color: var(--ink-soft);
            font-size: .92rem;
        }
        .manifest-fill {
            flex: 1;
            border-bottom: 1px dotted var(--line);
            transform: translateY(-4px);
            min-width: 12px;
        }
        .manifest-value {
            flex: 0 0 auto;
            max-width: 60%;
            text-align: right;
            color: var(--ink);
        }
        .manifest-value.empty {
            color: var(--ink-soft);
            font-style: italic;
        }

        .bio-block {
            padding: 4px 26px 26px;
        }
        .bio-block .manifest-label {
            display: block;
            margin-bottom: 6px;
        }
        .bio-block p {
            margin: 0;
            color: var(--ink);
            line-height: 1.75;
        }
        .bio-block p.empty {
            color: var(--ink-soft);
            font-style: italic;
        }

        /* ---- Stamps (badges) ---- */

        .stamps-section {
            padding: 20px 26px 30px;
            border-top: 1px dashed var(--line);
        }

        .stamps-heading {
            margin: 4px 0 20px;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1.25rem;
            color: var(--stamp-red-dark);
        }

        .stamps-row {
            display: flex;
            flex-wrap: wrap;
            gap: 18px 22px;
        }

        .stamp {
            width: 128px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 8px;
            opacity: 0;
            transform: scale(.6) rotate(var(--tilt, 0deg));
            animation: stamp-in .5s cubic-bezier(.2, 1.6, .4, 1) forwards;
            animation-delay: var(--delay, 0s);
        }
        .stamp:nth-child(3n)   { --tilt: -6deg; --ring: var(--stamp-teal); }
        .stamp:nth-child(3n+1) { --tilt: 4deg;  --ring: var(--stamp-red); }
        .stamp:nth-child(3n+2) { --tilt: -2deg; --ring: var(--thread); }

        @keyframes stamp-in {
            0%   { opacity: 0; transform: scale(.6) rotate(var(--tilt)); }
            70%  { opacity: 1; transform: scale(1.06) rotate(var(--tilt)); }
            100% { opacity: 1; transform: scale(1) rotate(var(--tilt)); }
        }

        .stamp-mark {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            border: 2px dashed var(--ring, var(--stamp-red));
            display: grid;
            place-items: center;
            font-size: 1.7rem;
            color: var(--ring, var(--stamp-red));
            background: rgba(255, 255, 255, .5);
            transition: transform .2s ease;
        }
        .stamp:hover .stamp-mark { transform: rotate(0deg) scale(1.06); }

        .stamp-name {
            margin: 0;
            font-size: .86rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.3;
        }
        .stamp-desc {
            margin: 0;
            font-size: .74rem;
            color: var(--ink-soft);
            line-height: 1.4;
        }
        .stamp-date {
            margin: 0;
            font-size: .68rem;
            color: var(--ink-soft);
            font-variant: small-caps;
        }

        .status-alert {
            margin-bottom: 18px;
            padding: 14px 18px;
            border-radius: 12px;
            background: rgba(47, 107, 94, .12);
            color: #1f4a40;
            border: 1px solid rgba(47, 107, 94, .2);
        }
</style>
@endpush

@section('content')
<div class="page">

        <header class="pass-header">
            <div class="pass-header-main">
                <span class="pass-eyebrow">{{ __('WarisanMakan · Member Passport') }}</span>
                <h1 class="pass-title">{{ __('Profile') }}</h1>
                <p class="pass-copy">{{ __('Manage your WarisanMakan identity, update your contact details, and keep your profile photo current for a personalized experience.') }}</p>

                <div class="pass-actions">
                    <a class="btn-outline" href="{{ route('profile.edit') }}">{{ __('Edit Profile') }}</a>
                    <a class="btn" href="{{ route('home') }}">{{ __('Back to dashboard') }}</a>
                </div>
            </div>

            <div class="pass-stub">
                <div class="stub-stat">
                    <span class="num">{{ $badges->count() }}</span>
                    <span class="label">{{ __('Badges earned') }}</span>
                </div>
                <div class="stub-stat">
                    <span class="num">{{ $user->created_at->format('Y') }}</span>
                    <span class="label">{{ __('Member since') }}</span>
                </div>
            </div>
        </header>

        <section class="passport">
            <div class="passport-top">
                <div class="id-panel">
                    <div class="id-seal">
                        <div class="id-seal-inner">
                            @if ($user->profile_photo)
                                <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }} profile photo">
                            @else
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            @endif
                        </div>
                    </div>
                    <p class="id-name">{{ $user->name }}</p>
                    <p class="id-email">{{ $user->email }}</p>
                    <span class="id-role">✦ {{ __($user->isAdmin() ? 'Admin' : 'Member') }}</span>
                </div>

                <div class="manifest">
                    <div class="manifest-row">
                        <span class="manifest-label">{{ __('Phone') }}</span>
                        <span class="manifest-fill"></span>
                        <span class="manifest-value {{ $user->phone ? '' : 'empty' }}">{{ $user->phone ?: __('Not provided') }}</span>
                    </div>
                    <div class="manifest-row">
                        <span class="manifest-label">{{ __('City') }}</span>
                        <span class="manifest-fill"></span>
                        <span class="manifest-value {{ $user->city ? '' : 'empty' }}">{{ $user->city ?: __('Not provided') }}</span>
                    </div>
                    <div class="manifest-row">
                        <span class="manifest-label">{{ __('Member since') }}</span>
                        <span class="manifest-fill"></span>
                        <span class="manifest-value">{{ $user->created_at->format('F j, Y') }}</span>
                    </div>
                    <div class="manifest-row">
                        <span class="manifest-label">{{ __('Role') }}</span>
                        <span class="manifest-fill"></span>
                        <span class="manifest-value">{{ __($user->isAdmin() ? 'Admin' : 'Member') }}</span>
                    </div>
                </div>
            </div>

            <div class="bio-block">
                <span class="manifest-label">{{ __('Bio') }}</span>
                <p class="{{ $user->bio ? '' : 'empty' }}">{{ $user->bio ?: __('Share a little about your food heritage interests.') }}</p>
            </div>

            @if ($badges->isNotEmpty())
                <div class="stamps-section" aria-labelledby="earned-badges-title">
                    <h2 id="earned-badges-title" class="stamps-heading">{{ __('Earned badges') }}</h2>

                    <div class="stamps-row">
                        @php $stampIndex = 0; @endphp
                        @foreach ($badges as $userBadge)
                            @if ($userBadge->badge)
                                <article class="stamp" style="--delay: {{ $stampIndex * 0.08 }}s">
                                    <div class="stamp-mark" aria-hidden="true">
                                        {{ $userBadge->badge->icon ?: '★' }}
                                    </div>
                                    <p class="stamp-name">{{ $userBadge->badge->badge_name }}</p>

                                    @if ($userBadge->badge->description)
                                        <p class="stamp-desc">{{ $userBadge->badge->description }}</p>
                                    @endif

                                    @if ($userBadge->earned_at)
                                        <p class="stamp-date">{{ __('Earned :date', ['date' => $userBadge->earned_at->format('M j, Y')]) }}</p>
                                    @endif
                                </article>
                                @php $stampIndex++; @endphp
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection

