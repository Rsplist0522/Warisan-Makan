@extends('layouts.user')

@section('title', __('User Dashboard'))
@section('user-topbar-title', __('User Dashboard'))
@section('user-topbar-subtitle', __('WarisanMakan heritage food portal'))

@section('user-topbar-actions')
@auth
    <div class="topbar-account-group">
        <form class="language-form" method="POST" action="{{ route('profile.update') }}">
            @csrf
            <input type="hidden" name="language_only" value="1">
            <input type="hidden" name="name" value="{{ auth()->user()->name }}">
            <input type="hidden" name="email" value="{{ auth()->user()->email }}">
            <input type="hidden" name="phone" value="{{ auth()->user()->phone }}">
            <input type="hidden" name="city" value="{{ auth()->user()->city }}">
            <input type="hidden" name="bio" value="{{ auth()->user()->bio }}">
            <label class="sr-only" for="dashboard-language">{{ __('Language') }}</label>
            <select id="dashboard-language" name="language" onchange="this.form.submit()">
                <option value="en" @selected((auth()->user()->language ?? 'en') === 'en')>{{ __('English') }}</option>
                <option value="ms" @selected((auth()->user()->language ?? 'en') === 'ms')>{{ __('Bahasa Melayu') }}</option>
                <option value="zh" @selected((auth()->user()->language ?? 'en') === 'zh')>{{ __('Chinese') }}</option>
            </select>
        </form>

        <a class="dashboard-profile-link" href="{{ route('profile.show') }}">
            @if (auth()->user()->profile_photo)
                <img class="dashboard-profile-image" src="{{ auth()->user()->profilePhotoUrl() }}" alt="{{ __('Profile photo') }}">
            @else
                <span class="dashboard-profile-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            @endif
            <span class="dashboard-profile-name">{{ auth()->user()->name }}</span>
        </a>
    </div>
@else
    <button class="guest-trigger" type="button" data-login-trigger aria-label="{{ __('Guest Mode') }}">{{ __('Guest Mode') }}</button>
@endauth
@endsection

@push('styles')
<style>
    :root {
        color-scheme: light;
        --wm-sidebar: #3b1b18;
        --wm-sidebar-soft: #51251f;
        --wm-bg: #f7f1ea;
        --wm-panel: #fffdf9;
        --wm-ink: #2e2420;
        --wm-text: #2e2420;
        --wm-muted: #7b6a60;
        --wm-line: rgba(66, 43, 32, .12);
        --wm-border: rgba(66, 43, 32, .12);
        --wm-accent: #a33a2d;
        --wm-accent-strong: #6e2a23;
        --wm-gold: #c89432;
        --wm-gold-soft: #e8c982;
        --wm-green: #3d6f55;
        --wm-cream: #fff7ed;
        --wm-shadow: 0 24px 70px rgba(68, 37, 26, .12);
    }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        min-height: 100vh;
        color: var(--wm-ink);
        background: var(--wm-bg);
        font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    a { color: inherit; }
    button { font: inherit; }
    h1, h2, h3, p { overflow-wrap: anywhere; }

    .shell {
        min-height: 100vh;
        display: grid;
        grid-template-columns: 268px minmax(0, 1fr);
    }

    .sidebar {
        position: sticky;
        top: 0;
        height: 100vh;
        display: flex;
        flex-direction: column;
        padding: 28px 20px;
        color: #fff5ec;
        background:
            radial-gradient(circle at 20% 0%, rgba(200, 148, 50, .13), transparent 24%),
            linear-gradient(180deg, var(--wm-sidebar), #28100e);
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 2px 10px 28px;
        border-bottom: 1px solid rgba(255, 255, 255, .1);
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 1.18rem;
        font-weight: 800;
    }

    .brand-mark {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        color: #3b1b16;
        background: var(--wm-gold);
        font-family: Georgia, 'Times New Roman', serif;
    }

    .nav-label {
        margin: 27px 12px 10px;
        color: rgba(255, 245, 236, .48);
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .nav {
        display: grid;
        gap: 5px;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 11px 12px;
        border-radius: 10px;
        color: rgba(255, 245, 236, .72);
        font-size: .88rem;
        font-weight: 700;
        text-decoration: none;
        transition: color .2s ease, background .2s ease, transform .2s ease;
    }

    .nav-item::before {
        content: '';
        width: 7px;
        height: 7px;
        flex: 0 0 auto;
        border: 1px solid currentColor;
        border-radius: 50%;
    }

    .nav-item.active,
    .nav-item:hover {
        color: #fff;
        background: var(--wm-sidebar-soft);
    }

    .nav-item:hover { transform: translateX(2px); }

    .nav-item.active::before {
        border-color: var(--wm-gold);
        background: var(--wm-gold);
        box-shadow: 0 0 0 4px rgba(200, 148, 50, .1);
    }

    .nav-item.muted { color: rgba(255, 245, 236, .42); }

    .nav-item small {
        margin-left: auto;
        color: rgba(255, 245, 236, .42);
        font-size: .62rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .sidebar-footer {
        margin-top: auto;
        padding-top: 22px;
        border-top: 1px solid rgba(255, 255, 255, .1);
    }

    .user-name {
        margin: 0 0 3px;
        font-size: .88rem;
        font-weight: 800;
    }

    .user-role {
        margin: 0 0 14px;
        color: rgba(255, 245, 236, .52);
        font-size: .76rem;
    }

    .logout {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid rgba(255, 255, 255, .16);
        border-radius: 9px;
        color: #fff5ec;
        background: transparent;
        cursor: pointer;
        text-align: left;
    }

    .logout:hover { background: rgba(255, 255, 255, .08); }

    .main {
        min-width: 0;
        background:
            radial-gradient(circle at 8% 15%, rgba(200, 148, 50, .08), transparent 19rem),
            linear-gradient(135deg, rgba(163, 58, 45, .055), transparent 34%),
            linear-gradient(315deg, rgba(61, 111, 85, .06), transparent 38%),
            var(--wm-bg);
    }

    .topbar {
        min-height: 76px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 16px 34px;
        border-bottom: 1px solid var(--wm-line);
        background: rgba(255, 253, 249, .9);
        backdrop-filter: blur(14px);
    }

    .topbar h2 {
        margin: 0;
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 1.35rem;
    }

    .topbar p {
        margin: 3px 0 0;
        color: var(--wm-muted);
        font-size: .82rem;
    }

    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .language-form { display: inline-flex; align-items: center; }

    .language-form select {
        min-height: 44px;
        padding: 0 34px 0 13px;
        border: 1px solid rgba(46, 36, 32, .14);
        border-radius: 999px;
        color: var(--wm-accent);
        background: #fff;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    .topbar-account-group {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        margin-left: auto;
    }

    .dashboard-profile-link {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-height: 44px;
        padding: 7px 13px;
        border: 1px solid var(--wm-border);
        border-radius: 999px;
        color: var(--wm-text);
        background: #fff;
        font-size: .92rem;
        font-weight: 700;
        text-decoration: none;
        transition: border-color .2s ease, background .2s ease, transform .2s ease;
    }

    .dashboard-profile-link:hover {
        border-color: rgba(163, 58, 45, .35);
        background: #fffaf4;
        transform: translateY(-1px);
    }

    .dashboard-profile-image,
    .dashboard-profile-avatar {
        width: 30px;
        height: 30px;
        flex: 0 0 auto;
        border-radius: 50%;
    }

    .dashboard-profile-image { object-fit: cover; }

    .dashboard-profile-avatar {
        display: inline-grid;
        place-items: center;
        color: var(--wm-accent);
        background: #f2e7dd;
        font-weight: 800;
    }

    .dashboard-profile-name {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .guest-trigger {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 44px;
        padding: 7px 13px;
        border: 1px solid rgba(46, 36, 32, .14);
        border-radius: 999px;
        color: var(--wm-accent);
        background: #fff;
        font-weight: 800;
        cursor: pointer;
    }

    .guest-trigger::before {
        content: '';
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: var(--wm-gold);
        box-shadow: 0 0 0 5px rgba(200, 148, 50, .1);
    }

    .dashboard-scroll-progress {
        position: sticky;
        top: 0;
        z-index: 30;
        height: 3px;
        pointer-events: none;
        background: transparent;
    }

    .dashboard-scroll-progress span {
        display: block;
        width: 100%;
        height: 100%;
        transform: scaleX(0);
        transform-origin: left center;
        background: linear-gradient(90deg, var(--wm-gold), var(--wm-accent));
        box-shadow: 0 0 16px rgba(200, 148, 50, .35);
        will-change: transform;
    }

    .dashboard-page {
        width: min(1180px, 100%);
        margin: 0 auto;
        padding: 24px 34px 64px;
    }

    .page-header {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        display: grid;
        grid-template-columns: minmax(0, 1.14fr) minmax(290px, .86fr);
        align-items: stretch;
        min-height: 500px;
        margin-bottom: 0;
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 30px;
        color: #fffaf4;
        background: #421815;
        box-shadow: 0 30px 80px rgba(71, 29, 21, .2);
    }

    .page-header::before {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 1;
        pointer-events: none;
        background:
            linear-gradient(90deg, rgba(33, 11, 9, .96) 0%, rgba(67, 24, 19, .88) 37%, rgba(67, 24, 19, .45) 58%, rgba(33, 11, 9, .04) 100%),
            radial-gradient(circle at 15% 22%, rgba(232, 201, 130, .17), transparent 29%);
    }

    .page-header::after {
        content: '';
        position: absolute;
        z-index: 2;
        right: -150px;
        bottom: -185px;
        width: 430px;
        height: 430px;
        border: 1px solid rgba(255,255,255,.18);
        border-radius: 50%;
        box-shadow:
            0 0 0 30px rgba(255,255,255,.035),
            0 0 0 64px rgba(255,255,255,.025),
            0 0 0 98px rgba(200,148,50,.025);
        pointer-events: none;
    }

    .hero-copy {
        position: relative;
        z-index: 4;
        align-self: end;
        min-width: 0;
        max-width: 710px;
        padding: 58px 52px 56px;
    }

    .eyebrow,
    .section-kicker {
        margin: 0 0 9px;
        color: var(--wm-gold);
        font-size: .7rem;
        font-weight: 850;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .page-header .eyebrow { color: #e7bf74; }

    .page-header h1 {
        max-width: 690px;
        margin: 0;
        font-family: Georgia, 'Times New Roman', serif;
        font-size: clamp(3rem, 6.5vw, 6.1rem);
        line-height: .91;
        letter-spacing: -.045em;
        text-wrap: balance;
    }

    .page-header .hero-description {
        max-width: 590px;
        margin: 20px 0 0;
        color: rgba(255, 250, 244, .76);
        font-size: 1rem;
        line-height: 1.72;
    }

    .hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 28px;
    }

    .hero-action {
        position: relative;
        overflow: hidden;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 48px;
        padding: 0 19px;
        border: 1px solid rgba(255,255,255,.3);
        border-radius: 999px;
        color: #fffaf4;
        background: rgba(255,255,255,.09);
        font-size: .86rem;
        font-weight: 800;
        text-decoration: none;
        backdrop-filter: blur(10px);
        transition: transform .22s ease, background .22s ease, border-color .22s ease, box-shadow .22s ease;
    }

    .hero-action::before {
        content: '';
        position: absolute;
        inset: 0;
        transform: translateX(-115%);
        background: linear-gradient(100deg, transparent 20%, rgba(255,255,255,.32), transparent 70%);
        transition: transform .55s ease;
    }

    .hero-action:hover::before { transform: translateX(115%); }

    .hero-action.primary {
        border-color: #d6a64d;
        color: #3b1b16;
        background: #d6a64d;
        box-shadow: 0 10px 28px rgba(200, 148, 50, .22);
    }

    .hero-action:hover {
        transform: translateY(-2px);
        border-color: rgba(255,255,255,.48);
        background: rgba(255,255,255,.17);
    }

    .hero-action.primary:hover {
        border-color: #e2b65f;
        background: #e2b65f;
        box-shadow: 0 14px 34px rgba(200, 148, 50, .3);
    }

    .hero-image {
        position: relative;
        z-index: 0;
        min-height: 340px;
        overflow: hidden;
    }

    .hero-image-media {
        position: absolute;
        inset: -4%;
        background: url('{{ asset('images/shop1.jpg') }}') center / cover no-repeat;
        transform: scale(1.045);
        filter: saturate(.97) contrast(1.03);
        will-change: transform;
        transition: transform .5s cubic-bezier(.2,.8,.2,1);
    }

    .hero-image::before {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 1;
        pointer-events: none;
        background:
            linear-gradient(90deg, rgba(65, 24, 18, .6), transparent 38%),
            linear-gradient(180deg, transparent 48%, rgba(38, 14, 10, .58));
    }

    .hero-image::after {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 2;
        pointer-events: none;
        opacity: .17;
        background-image:
            radial-gradient(circle at 20% 20%, #fff 0 1px, transparent 1.2px),
            radial-gradient(circle at 80% 60%, #fff 0 1px, transparent 1.2px);
        background-size: 34px 34px, 48px 48px;
        mix-blend-mode: soft-light;
    }

    .hero-stamp {
        position: absolute;
        right: 27px;
        bottom: 27px;
        z-index: 5;
        display: grid;
        place-items: center;
        width: 116px;
        height: 116px;
        padding: 17px;
        border: 1px solid rgba(255,255,255,.5);
        border-radius: 50%;
        color: #fffaf4;
        background: rgba(55, 23, 18, .58);
        box-shadow: inset 0 0 0 8px rgba(255,255,255,.025), 0 18px 40px rgba(0,0,0,.16);
        font-size: .68rem;
        font-weight: 800;
        line-height: 1.35;
        letter-spacing: .08em;
        text-align: center;
        text-transform: uppercase;
        backdrop-filter: blur(8px);
    }

    .hero-orbit {
        position: absolute;
        z-index: 4;
        top: 30px;
        right: 28px;
        width: 88px;
        height: 88px;
        border: 1px solid rgba(255,255,255,.26);
        border-radius: 50%;
        pointer-events: none;
    }

    .hero-orbit::before,
    .hero-orbit::after {
        content: '';
        position: absolute;
        border-radius: 50%;
    }

    .hero-orbit::before {
        inset: 12px;
        border: 1px dashed rgba(232,201,130,.55);
    }

    .hero-orbit::after {
        width: 7px;
        height: 7px;
        top: 7px;
        left: 40px;
        background: var(--wm-gold-soft);
        box-shadow: 0 0 16px rgba(232,201,130,.65);
        transform-origin: 3px 37px;
        animation: wmOrbit 11s linear infinite;
    }


    .hero-sparkles {
        position: absolute;
        z-index: 4;
        inset: 0;
        overflow: hidden;
        pointer-events: none;
    }

    .hero-sparkle {
        position: absolute;
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #fff4c7;
        box-shadow: 0 0 12px rgba(255,235,178,.78);
        opacity: 0;
        animation: wmSparkle 6s ease-in-out infinite;
    }

    .hero-sparkle:nth-child(1) { left: 15%; top: 72%; animation-delay: .2s; }
    .hero-sparkle:nth-child(2) { left: 72%; top: 64%; width: 7px; height: 7px; animation-delay: 1.4s; }
    .hero-sparkle:nth-child(3) { left: 54%; top: 25%; animation-delay: 2.6s; }
    .hero-sparkle:nth-child(4) { left: 87%; top: 39%; width: 4px; height: 4px; animation-delay: 3.8s; }
    .hero-sparkle:nth-child(5) { left: 39%; top: 54%; animation-delay: 4.7s; }

    .dashboard-intro {
        display: grid;
        grid-template-columns: minmax(0, .72fr) minmax(0, 1.28fr);
        gap: 48px;
        align-items: start;
        padding: 76px 18px 54px;
    }

    .dashboard-intro h2,
    .section-heading-row h2,
    .heritage-feature-copy h2,
    .closing-band h2 {
        font-family: Georgia, 'Times New Roman', serif;
        letter-spacing: -.025em;
    }

    .dashboard-intro h2 {
        margin: 0;
        color: var(--wm-accent);
        font-size: clamp(1.95rem, 3vw, 3rem);
        line-height: 1;
    }

    .dashboard-intro p {
        margin: 0;
        color: var(--wm-muted);
        font-size: 1.02rem;
        line-height: 1.82;
    }

    .section-heading-row {
        display: flex;
        justify-content: space-between;
        align-items: end;
        gap: 20px;
        margin: 0 0 22px;
    }

    .section-heading-row h2 {
        max-width: 760px;
        margin: 0;
        color: var(--wm-ink);
        font-size: clamp(1.85rem, 3vw, 2.75rem);
        line-height: 1.02;
    }

    .section-heading-row a {
        position: relative;
        flex: 0 0 auto;
        padding-bottom: 3px;
        color: var(--wm-accent);
        font-size: .84rem;
        font-weight: 850;
        text-decoration: none;
    }

    .section-heading-row a::after {
        content: '';
        position: absolute;
        right: 0;
        bottom: 0;
        left: 0;
        height: 1px;
        background: currentColor;
        transform: scaleX(.25);
        transform-origin: left;
        transition: transform .25s ease;
    }

    .section-heading-row a:hover::after { transform: scaleX(1); }

    
    .journey-strip {
        position: relative;
        isolation: isolate;
        margin: 18px 0 0;
        padding: 34px 0 20px;
    }

    .journey-strip::before {
        content: '';
        position: absolute;
        top: 0;
        right: 7%;
        left: 7%;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(163,58,45,.2), transparent);
    }

    .journey-heading {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 24px;
        padding: 0 10px 28px;
    }

    .journey-heading h2 {
        max-width: 760px;
        margin: 0;
        color: var(--wm-ink);
        font-family: Georgia, 'Times New Roman', serif;
        font-size: clamp(2rem, 3.6vw, 3.25rem);
        line-height: 1;
        letter-spacing: -.03em;
        text-wrap: balance;
    }

    .journey-scroll {
        overflow-x: auto;
        padding: 8px 4px 26px;
        scrollbar-width: thin;
        scrollbar-color: rgba(163,58,45,.25) transparent;
    }

    .journey-canvas {
        position: relative;
        min-width: 940px;
        min-height: 410px;
        border-radius: 34px;
        background:
            radial-gradient(circle at 48% 48%, rgba(200,148,50,.1), transparent 25rem),
            radial-gradient(circle at 12% 78%, rgba(61,111,85,.07), transparent 17rem),
            radial-gradient(circle at 88% 17%, rgba(163,58,45,.055), transparent 18rem);
    }

    .journey-canvas::before,
    .journey-canvas::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }

    .journey-canvas::before {
        width: 230px;
        height: 230px;
        top: 70px;
        left: 38%;
        border: 1px solid rgba(200,148,50,.08);
        box-shadow: 0 0 0 22px rgba(200,148,50,.018), 0 0 0 44px rgba(163,58,45,.014);
    }

    .journey-canvas::after {
        width: 150px;
        height: 150px;
        right: 5%;
        bottom: 15px;
        border: 1px dashed rgba(163,58,45,.09);
        animation: wmSpin 22s linear infinite;
    }

    .journey-route {
        position: absolute;
        inset: 44px 2% 20px;
        width: 96%;
        height: 300px;
        overflow: visible;
        pointer-events: none;
    }

    .journey-route-base {
        fill: none;
        stroke: rgba(66,43,32,.075);
        stroke-width: 15;
        stroke-linecap: round;
    }

    .journey-path {
        fill: none;
        stroke: url(#journeyGradient);
        stroke-width: 2.5;
        stroke-linecap: round;
        stroke-dasharray: 1;
        stroke-dashoffset: 1;
        opacity: .95;
        transition: stroke-dashoffset 1.55s cubic-bezier(.2,.75,.25,1) .12s;
    }

    .journey-strip.is-visible .journey-path { stroke-dashoffset: 0; }

    .journey-traveller {
        fill: #fff8d5;
        stroke: var(--wm-gold);
        stroke-width: 3;
        opacity: 0;
        filter: drop-shadow(0 0 8px rgba(200,148,50,.7));
        transition: opacity .35s ease;
    }

    .journey-strip.is-visible .journey-traveller { opacity: 1; }

    .journey-stops {
        position: absolute;
        inset: 0;
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px;
        padding: 36px 22px 28px;
    }

    .journey-stop {
        --stop-shift: 0px;
        position: relative;
        z-index: 3;
        align-self: start;
        display: grid;
        justify-items: center;
        align-content: start;
        gap: 10px;
        min-width: 0;
        padding: 0 7px;
        color: var(--wm-ink);
        text-align: center;
        text-decoration: none;
        transform: translateY(var(--stop-shift));
        transition: transform .28s cubic-bezier(.2,.8,.2,1), color .25s ease;
    }

    .journey-stop:nth-child(1) { --stop-shift: 190px; }
    .journey-stop:nth-child(2) { --stop-shift: 62px; }
    .journey-stop:nth-child(3) { --stop-shift: 162px; }
    .journey-stop:nth-child(4) { --stop-shift: 48px; }
    .journey-stop:nth-child(5) { --stop-shift: 182px; }

    .journey-node {
        position: relative;
        display: grid;
        place-items: center;
        width: 88px;
        height: 88px;
        border: 1px solid rgba(163,58,45,.18);
        border-radius: 50%;
        color: var(--wm-accent);
        background: #fff;
        box-shadow:
            0 18px 36px rgba(73, 41, 28, .12),
            inset 0 0 0 7px rgba(255,255,255,.48);
        transition:
            transform .28s cubic-bezier(.2,.8,.2,1),
            box-shadow .28s ease,
            border-color .28s ease,
            color .28s ease,
            background .28s ease;
    }

    .journey-node::before,
    .journey-node::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }

    .journey-node::before {
        inset: -10px;
        border: 1px dashed rgba(200,148,50,.29);
        transition: transform .45s ease, opacity .3s ease;
    }

    .journey-node::after {
        inset: -22px;
        border: 1px solid rgba(163,58,45,.08);
        opacity: .72;
        transform: scale(.9);
        transition: transform .45s ease, opacity .3s ease;
    }

    .journey-node svg {
        width: 29px;
        height: 29px;
        stroke: currentColor;
        stroke-width: 1.9;
        fill: none;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .journey-stop:hover { color: var(--wm-accent); }

    .journey-stop:hover .journey-node,
    .journey-stop:focus-visible .journey-node {
        color: #3c260b;
        border-color: rgba(200,148,50,.48);
        background:
            radial-gradient(circle at 35% 30%, #fff8d8, #edbd56 62%, #c98b20);
        transform: translateY(-7px) scale(1.065);
        box-shadow:
            0 27px 50px rgba(78,38,25,.17),
            0 0 0 11px rgba(200,148,50,.09),
            inset 0 0 0 7px rgba(255,255,255,.2);
    }

    .journey-stop:hover .journey-node::before,
    .journey-stop:focus-visible .journey-node::before {
        transform: rotate(16deg) scale(1.07);
    }

    .journey-stop:hover .journey-node::after,
    .journey-stop:focus-visible .journey-node::after {
        transform: scale(1.06);
        opacity: 1;
    }

    .journey-name {
        max-width: 185px;
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 1.05rem;
        font-weight: 800;
        line-height: 1.16;
    }

    .journey-description {
        max-width: 190px;
        color: var(--wm-muted);
        font-size: .76rem;
        line-height: 1.48;
    }

    .journey-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--wm-accent);
        font-size: .72rem;
        font-weight: 900;
    }

    .journey-action::after {
        content: '›';
        font-size: 1.15rem;
        line-height: 1;
        transition: transform .22s ease;
    }

    .journey-stop:hover .journey-action::after { transform: translateX(4px); }

    .journey-stop[data-login-required="true"] .journey-node::after {
        border-style: dashed;
    }

.heritage-feature {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        display: grid;
        grid-template-columns: minmax(0, 1.08fr) minmax(0, .92fr);
        min-height: 430px;
        margin-top: 66px;
        border: 1px solid rgba(163,58,45,.08);
        border-radius: 26px;
        background: #eadbc9;
        box-shadow: var(--wm-shadow);
    }

    .heritage-feature::after {
        content: '';
        position: absolute;
        z-index: 0;
        right: -110px;
        top: -110px;
        width: 260px;
        height: 260px;
        border: 1px solid rgba(163,58,45,.1);
        border-radius: 50%;
        box-shadow: 0 0 0 24px rgba(163,58,45,.025), 0 0 0 48px rgba(200,148,50,.025);
        pointer-events: none;
    }

    .heritage-feature-image {
        position: relative;
        z-index: 1;
        min-height: 370px;
        overflow: hidden;
    }

    .heritage-image-media {
        position: absolute;
        inset: 0;
        background: url('{{ asset('images/shop2.jpg') }}') center / cover no-repeat;
        transform: scale(1.035);
        transition: transform .8s cubic-bezier(.2,.8,.2,1);
    }

    .heritage-feature:hover .heritage-image-media { transform: scale(1.075); }

    .heritage-feature-image::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent 62%, rgba(234,219,201,.52));
        pointer-events: none;
    }

    .heritage-image-wipe {
        position: absolute;
        z-index: 3;
        inset: 0;
        background: linear-gradient(110deg, #4a1d19, #7a3027 62%, #c89432);
        transform: translateX(0);
        transition: transform 1s cubic-bezier(.65,0,.2,1) .08s;
        pointer-events: none;
    }

    .heritage-feature-image.is-visible .heritage-image-wipe { transform: translateX(104%); }

    .heritage-feature-copy {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 48px;
    }

    .heritage-feature-copy h2 {
        margin: 0 0 15px;
        color: var(--wm-accent);
        font-size: clamp(2rem, 4vw, 3.55rem);
        line-height: .97;
        text-wrap: balance;
    }

    .heritage-feature-copy p {
        margin: 0;
        color: var(--wm-muted);
        line-height: 1.72;
    }

    .text-link {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        width: fit-content;
        margin-top: 26px;
        color: var(--wm-accent);
        font-weight: 850;
        text-decoration: none;
    }

    .text-link::after {
        content: '›';
        font-size: 1.3rem;
        transition: transform .22s ease;
    }

    .text-link:hover::after { transform: translateX(5px); }

    .closing-band {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto 150px;
        gap: 28px;
        align-items: center;
        min-height: 230px;
        margin-top: 68px;
        padding: 42px 44px;
        border-radius: 24px;
        color: #fffaf4;
        background:
            radial-gradient(circle at 90% 15%, rgba(232,201,130,.16), transparent 22%),
            linear-gradient(120deg, #351411, #783028 72%, #8f392d);
        box-shadow: 0 28px 70px rgba(72, 25, 20, .18);
    }

    .closing-band::before {
        content: '';
        position: absolute;
        inset: 0;
        z-index: -1;
        opacity: .2;
        background-image: radial-gradient(circle, rgba(255,255,255,.45) 0 1px, transparent 1.4px);
        background-size: 28px 28px;
        mask-image: linear-gradient(90deg, transparent 20%, #000 100%);
    }

    .closing-band h2 {
        margin: 0 0 10px;
        color: #fffaf4;
        font-size: clamp(1.9rem, 4vw, 3.15rem);
        line-height: 1;
    }

    .closing-band p {
        max-width: 660px;
        margin: 0;
        color: rgba(255,250,244,.72);
        line-height: 1.65;
    }

    .blind-visual {
        position: relative;
        width: 126px;
        height: 126px;
        justify-self: end;
        pointer-events: none;
    }

    .blind-ring,
    .blind-ring::before,
    .blind-ring::after {
        position: absolute;
        border-radius: 50%;
    }

    .blind-ring {
        inset: 10px;
        border: 1px solid rgba(255,255,255,.35);
        animation: wmSpin 16s linear infinite;
    }

    .blind-ring::before,
    .blind-ring::after { content: ''; }

    .blind-ring::before {
        inset: 16px;
        border: 1px dashed rgba(232,201,130,.6);
    }

    .blind-ring::after {
        width: 10px;
        height: 10px;
        top: -5px;
        left: 50%;
        background: var(--wm-gold-soft);
        box-shadow: 0 0 18px rgba(232,201,130,.7);
    }

    .blind-cube {
        position: absolute;
        inset: 38px;
        border: 1px solid rgba(255,255,255,.45);
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(255,255,255,.16), rgba(255,255,255,.04));
        box-shadow: inset 0 0 22px rgba(255,255,255,.06), 0 14px 30px rgba(0,0,0,.16);
        transform: rotate(8deg);
        animation: wmFloat 4.5s ease-in-out infinite;
    }

    .blind-cube::before,
    .blind-cube::after {
        content: '';
        position: absolute;
        background: rgba(232,201,130,.6);
    }

    .blind-cube::before {
        width: 1px;
        top: 8px;
        bottom: 8px;
        left: 50%;
    }

    .blind-cube::after {
        height: 1px;
        right: 8px;
        left: 8px;
        top: 50%;
    }

    .dashboard-motion .reveal-ready {
        opacity: 0;
        transform: translateY(24px);
        transition: opacity .72s ease, transform .72s cubic-bezier(.2,.75,.25,1);
    }

    .dashboard-motion .reveal-ready.is-visible {
        opacity: 1;
        transform: translateY(0);
    }
@keyframes wmSparkle {
        0%, 100% { transform: translateY(16px) scale(.4); opacity: 0; }
        30% { opacity: .76; }
        65% { opacity: .5; }
        100% { transform: translateY(-42px) scale(1.1); opacity: 0; }
    }

    @keyframes wmOrbit {
        to { transform: rotate(360deg); }
    }

    @keyframes wmSpin {
        to { transform: rotate(360deg); }
    }

    @keyframes wmFloat {
        0%, 100% { transform: translateY(0) rotate(8deg); }
        50% { transform: translateY(-7px) rotate(5deg); }
    }

    @media (max-width: 980px) {
        .shell { grid-template-columns: 1fr; }

        .sidebar {
            position: static;
            height: auto;
        }

        .nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sidebar-footer { margin-top: 24px; }
        .topbar { padding-inline: 22px; }
        .dashboard-page { padding-inline: 22px; }
        .dashboard-profile-name { display: none; }

        .page-header {
            grid-template-columns: minmax(0, 1fr) minmax(260px, .78fr);
            min-height: 460px;
        }

        .hero-copy { padding: 44px 34px; }
.closing-band {
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .blind-visual { display: none; }
    }

    @media (max-width: 800px) {
        .page-header,
        .dashboard-intro,
        .heritage-feature {
            grid-template-columns: 1fr;
        }

        .page-header { min-height: 0; }
        .hero-copy { padding: 38px 28px; }
        .hero-image { min-height: 280px; }
        .dashboard-intro { gap: 20px; padding: 52px 8px 38px; }

        .heritage-feature-image::after {
            background: linear-gradient(180deg, transparent 68%, rgba(234,219,201,.6));
        }

        .closing-band {
            grid-template-columns: 1fr;
            gap: 22px;
        }

        .closing-band .hero-action { justify-self: start; }
    }


    @media (max-width: 800px) {
        .journey-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .journey-scroll {
            overflow: visible;
        }

        .journey-canvas {
            min-width: 0;
            min-height: 650px;
            border-radius: 24px;
        }

        .journey-route {
            display: none;
        }

        .journey-stops {
            position: relative;
            display: grid;
            grid-template-columns: 1fr;
            gap: 0;
            padding: 12px 12px 24px 58px;
        }

        .journey-stops::before {
            content: '';
            position: absolute;
            top: 55px;
            bottom: 58px;
            left: 44px;
            width: 2px;
            background: linear-gradient(180deg, var(--wm-gold), rgba(163,58,45,.26));
        }

        .journey-stop,
        .journey-stop:nth-child(n) {
            --stop-shift: 0px;
            grid-template-columns: 80px minmax(0, 1fr);
            grid-template-areas:
                "node name"
                "node desc"
                "node action";
            justify-items: start;
            align-items: center;
            gap: 3px 18px;
            min-height: 120px;
            padding: 15px 0;
            text-align: left;
            transform: none;
        }

        .journey-node {
            grid-area: node;
            width: 68px;
            height: 68px;
        }

        .journey-name {
            grid-area: name;
            max-width: none;
        }

        .journey-description {
            grid-area: desc;
            max-width: 520px;
        }

        .journey-action {
            grid-area: action;
        }
    }

    @media (max-width: 620px) {
        .journey-stops { padding-left: 43px; }
        .journey-stops::before { left: 32px; }

        .journey-stop,
        .journey-stop:nth-child(n) {
            grid-template-columns: 58px minmax(0, 1fr);
            gap: 3px 14px;
        }

        .journey-node {
            width: 54px;
            height: 54px;
        }

        .journey-node svg {
            width: 22px;
            height: 22px;
        }

        .journey-node::before { inset: -6px; }
        .journey-node::after { display: none; }


        .topbar { display: grid; }
        .dashboard-page { padding: 18px 16px 44px; }
        .nav { grid-template-columns: 1fr; }

        .page-header {
            border-radius: 22px;
        }

        .page-header h1 {
            font-size: clamp(2.75rem, 13vw, 4.45rem);
        }

        .hero-copy { padding: 32px 22px; }
        .hero-image { min-height: 250px; }
        .hero-stamp { width: 96px; height: 96px; right: 18px; bottom: 18px; }
        .hero-orbit { width: 72px; height: 72px; top: 18px; right: 18px; }

        .section-heading-row {
            align-items: flex-start;
            flex-direction: column;
        }
.heritage-feature-copy,
        .closing-band { padding: 31px 24px; }
        .closing-band { border-radius: 20px; }
    }

    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            scroll-behavior: auto !important;
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: .01ms !important;
        }

        .dashboard-motion .reveal-ready {
            opacity: 1 !important;
            transform: none !important;
        }

        .journey-path { stroke-dashoffset: 0 !important; }
        .journey-traveller { display: none !important; }
        .heritage-image-wipe { display: none; }
    }
</style>
@endpush

@section('content')
@php
    $isGuest = ! auth()->check();

    $modules = [
        [
            'name' => 'Community Contribution',
            'description' => 'Submit heritage shop stories, food details, media, and location notes.',
            'status' => 'Open module',
            'icon' => 'community',
            'route' => 'community-contribution.create',
            'guestRestricted' => true,
        ],
        [
            'name' => 'Heritage Shop Tracking',
            'description' => 'Track verified heritage eateries, ownership notes, and updates.',
            'status' => 'Open module',
            'icon' => 'shop',
            'route' => 'heritage-shops.index',
        ],
        [
            'name' => 'Food Passport & Achievement',
            'description' => 'Collect stamps, badges, and milestones from heritage food visits.',
            'status' => 'Open module',
            'icon' => 'award',
            'route' => 'passport.index',
            'guestRestricted' => true,
        ],
        [
            'name' => 'Food Trail & Navigation',
            'description' => 'Discover curated routes to heritage food spots across Malaysia.',
            'status' => 'Open module',
            'icon' => 'map',
            'route' => 'foodtrails.index',
            'guestRestricted' => true,
        ],
        [
            'name' => 'Blind Box Recommendation',
            'description' => 'Reveal surprise heritage food suggestions matched to your taste.',
            'status' => 'Open module',
            'icon' => 'box',
            'route' => 'blind-box.index',
            'guestRestricted' => true,
        ],
    ];
@endphp

<div class="dashboard-scroll-progress" aria-hidden="true">
    <span data-scroll-progress></span>
</div>

<div class="dashboard-page" data-dashboard-root>
    <header class="page-header" data-hero>
        <div class="hero-copy">
            <p class="eyebrow">{{ __('Heritage food explorer') }}</p>
            <h1>{{ __('Preserve Malaysia’s culinary heritage through every bite.') }}</h1>
            <p class="hero-description">{{ __('Discover forgotten food stories, celebrate traditional vendors, and let every visit feel like a small cultural expedition.') }}</p>

            <div class="hero-actions">
                <a class="hero-action primary" href="{{ route('heritage-shops.index') }}">{{ __('Browse Heritage Shops') }}</a>
                <a class="hero-action" href="{{ route('foodtrails.index') }}" @guest data-login-required="true" @endguest>{{ __('Explore Food Trails') }}</a>
            </div>
        </div>

        <div class="hero-image" role="img" aria-label="{{ __('Heritage Shop Discovery') }}">
            <div class="hero-image-media" data-hero-media aria-hidden="true"></div>
            <span class="hero-orbit" aria-hidden="true"></span>

            <span class="hero-sparkles" aria-hidden="true">
                <i class="hero-sparkle"></i>
                <i class="hero-sparkle"></i>
                <i class="hero-sparkle"></i>
                <i class="hero-sparkle"></i>
                <i class="hero-sparkle"></i>
            </span>

            <span class="hero-stamp">{{ __('Heritage Shop Discovery') }}</span>
        </div>
    </header>

    <section class="dashboard-intro" aria-labelledby="dashboard-intro-title" data-reveal>
        <div>
            <p class="section-kicker">{{ __('WarisanMakan • Heritage Discovery') }}</p>
            <h2 id="dashboard-intro-title">{{ __('Heritage Shop Discovery') }}</h2>
        </div>
        <p>{{ __('Browse featured vendors with founder stories and generations of flavour.') }} {{ __('Explore Malaysian heritage food culture, preserve local food stories, and follow new WarisanMakan modules as they open.') }}</p>
    </section>

    <section class="journey-strip" aria-labelledby="module-exploration-title" data-reveal>
        <div class="journey-heading">
            <div>
                <p class="section-kicker">{{ __('Follow the trail') }}</p>
                <h2 id="module-exploration-title">{{ __('Choose your heritage journey') }}</h2>
            </div>
        </div>

        <div class="journey-scroll">
            <div class="journey-canvas" data-journey-canvas>
                <svg class="journey-route" viewBox="0 0 1000 330" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="journeyGradient" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%" stop-color="#a33a2d"></stop>
                            <stop offset="50%" stop-color="#c89432"></stop>
                            <stop offset="100%" stop-color="#3d6f55"></stop>
                        </linearGradient>
                    </defs>

                    <path
                        class="journey-route-base"
                        d="M35 238 C125 212, 128 80, 250 78 S380 228, 500 202 S616 55, 748 66 S838 242, 965 215"
                        pathLength="1"
                    ></path>

                    <path
                        class="journey-path"
                        data-journey-path
                        d="M35 238 C125 212, 128 80, 250 78 S380 228, 500 202 S616 55, 748 66 S838 242, 965 215"
                        pathLength="1"
                    ></path>

                    <circle class="journey-traveller" data-journey-traveller r="6"></circle>
                </svg>

                <div class="journey-stops">
                    @foreach ($modules as $module)
                        @php
                            $restrictedForGuest = $isGuest && ($module['guestRestricted'] ?? false);
                            $moduleHref = $restrictedForGuest ? '#' : route($module['route']);
                        @endphp

                        <a
                            class="journey-stop"
                            href="{{ $moduleHref }}"
                            @if($restrictedForGuest) data-login-required="true" @endif
                        >
                            <span class="journey-node" aria-hidden="true">
                                @include('partials.module-icon', ['icon' => $module['icon']])
                            </span>

                            <span class="journey-name">{{ __($module['name']) }}</span>
                            <span class="journey-description">{{ __($module['description']) }}</span>
                            <span class="journey-action">{{ __($module['status']) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="heritage-feature" aria-labelledby="heritage-feature-title" data-reveal>
        <div class="heritage-feature-image" role="img" aria-label="{{ __('Heritage Shop Discovery') }}" data-image-reveal>
            <div class="heritage-image-media" aria-hidden="true"></div>
            <span class="heritage-image-wipe" aria-hidden="true"></span>
        </div>

        <div class="heritage-feature-copy">
            <p class="section-kicker">{{ __('Heritage Shop Listing') }}</p>
            <h2 id="heritage-feature-title">{{ __('Browse traditional vendors, search by keyword, and explore the stories behind Malaysia\'s heritage food culture.') }}</h2>
            <p>{{ __('Track verified heritage eateries, ownership notes, and updates.') }}</p>
            <a class="text-link" href="{{ route('heritage-shops.index') }}">{{ __('Browse Heritage Shops') }}</a>
        </div>
    </section>

    <section class="closing-band" aria-labelledby="closing-title" data-reveal>
        <div>
            <p class="section-kicker">{{ __('WarisanMakan • Heritage Discovery') }}</p>
            <h2 id="closing-title">{{ __('Surprise discovery unlocked') }}</h2>
            <p>{{ __('Tap the box for a surprise heritage shop recommendation inspired by the spirit of discovery.') }}</p>
        </div>

        <a class="hero-action primary" href="{{ route('blind-box.index') }}" @guest data-login-required="true" @endguest>{{ __('Explore Blind Box') }}</a>

        <div class="blind-visual" aria-hidden="true">
            <span class="blind-ring"></span>
            <span class="blind-cube"></span>
        </div>
    </section>
</div>

@guest
    @include('partials.login-required-modal')
@endguest

<script>
(() => {
    const root = document.querySelector('[data-dashboard-root]');
    if (!root || root.dataset.enhanced === 'true') return;

    root.dataset.enhanced = 'true';

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const progressBar = document.querySelector('[data-scroll-progress]');

    const updateScrollProgress = () => {
        if (!progressBar) return;

        const documentElement = document.documentElement;
        const scrollable = Math.max(1, documentElement.scrollHeight - window.innerHeight);
        const progress = Math.min(1, Math.max(0, window.scrollY / scrollable));
        progressBar.style.transform = `scaleX(${progress})`;
    };

    updateScrollProgress();
    window.addEventListener('scroll', updateScrollProgress, { passive: true });
    window.addEventListener('resize', updateScrollProgress, { passive: true });

    const revealItems = [...root.querySelectorAll('[data-reveal]')];
    const imageReveal = root.querySelector('[data-image-reveal]');

    if (reducedMotion || !('IntersectionObserver' in window)) {
        revealItems.forEach((item) => item.classList.add('is-visible'));
        imageReveal?.classList.add('is-visible');
    } else {
        root.classList.add('dashboard-motion');
        revealItems.forEach((item) => item.classList.add('reveal-ready'));

        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);

                window.setTimeout(() => {
                    entry.target.classList.remove('reveal-ready');
                    entry.target.style.transitionDelay = '';
                }, 950);
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -7% 0px',
        });

        revealItems.forEach((item) => revealObserver.observe(item));

        if (imageReveal) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            }, { threshold: 0.22 });

            imageObserver.observe(imageReveal);
        }
    }

    if (!reducedMotion && finePointer) {
        const hero = root.querySelector('[data-hero]');
        const heroMedia = root.querySelector('[data-hero-media]');

        if (hero && heroMedia) {
            hero.addEventListener('pointermove', (event) => {
                const rect = hero.getBoundingClientRect();
                const x = (event.clientX - rect.left) / rect.width - 0.5;
                const y = (event.clientY - rect.top) / rect.height - 0.5;

                heroMedia.style.transform = `scale(1.065) translate3d(${x * -10}px, ${y * -8}px, 0)`;
            });

            hero.addEventListener('pointerleave', () => {
                heroMedia.style.transform = 'scale(1.045) translate3d(0, 0, 0)';
            });
        }

        root.querySelectorAll('.journey-stop').forEach((stop) => {
            const node = stop.querySelector('.journey-node');
            if (!node) return;

            stop.addEventListener('pointermove', (event) => {
                const rect = node.getBoundingClientRect();
                const x = Math.max(-1, Math.min(1, (event.clientX - (rect.left + rect.width / 2)) / rect.width));
                const y = Math.max(-1, Math.min(1, (event.clientY - (rect.top + rect.height / 2)) / rect.height));

                node.style.transform = `translate(${x * 4}px, ${y * 4 - 7}px) scale(1.065)`;
            });

            stop.addEventListener('pointerleave', () => {
                node.style.transform = '';
            });
        });
    }

    const journeyPath = root.querySelector('[data-journey-path]');
    const journeyTraveller = root.querySelector('[data-journey-traveller]');
    const journeyCanvas = root.querySelector('[data-journey-canvas]');

    let journeyFrame = null;
    let journeyStart = null;
    let journeyActive = false;

    const animateJourneyTraveller = (timestamp) => {
        if (!journeyActive || reducedMotion || !journeyPath || !journeyTraveller) {
            journeyFrame = null;
            return;
        }

        if (journeyStart === null) journeyStart = timestamp;

        const duration = 10000;
        const progress = ((timestamp - journeyStart) % duration) / duration;
        const totalLength = journeyPath.getTotalLength();
        const point = journeyPath.getPointAtLength(totalLength * progress);

        journeyTraveller.setAttribute('cx', point.x);
        journeyTraveller.setAttribute('cy', point.y);

        journeyFrame = window.requestAnimationFrame(animateJourneyTraveller);
    };

    if (
        !reducedMotion &&
        journeyCanvas &&
        journeyPath &&
        journeyTraveller &&
        'IntersectionObserver' in window
    ) {
        const travellerObserver = new IntersectionObserver((entries) => {
            journeyActive = entries.some((entry) => entry.isIntersecting);

            if (journeyActive && journeyFrame === null) {
                journeyStart = null;
                journeyFrame = window.requestAnimationFrame(animateJourneyTraveller);
            }
        }, { threshold: 0.12 });

        travellerObserver.observe(journeyCanvas);
    }
})();
</script>
@endsection