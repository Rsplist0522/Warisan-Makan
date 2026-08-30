<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.brand-favicon')
    <title>{{ __('Sign in') }} - Warisan Makan</title>
    @fonts
    <style>
        :root {
            color-scheme: light;
            --ink: #2e211b;
            --muted: #75645a;
            --red: #8f2929;
            --red-dark: #701e1e;
            --cream: #fffaf4;
            --line: rgba(65, 43, 32, .14);
            --gold: #c4933c;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            margin: 0;
            padding: 32px 18px;
            color: var(--ink);
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            background:
                linear-gradient(120deg, rgba(143, 41, 41, .08) 0 18%, transparent 18% 100%),
                linear-gradient(135deg, #f8eee3, #ead8c8);
        }

        .login-shell {
            width: min(900px, 100%);
            display: grid;
            grid-template-columns: .95fr 1.05fr;
            overflow: hidden;
            border: 1px solid rgba(65, 43, 32, .12);
            border-radius: 24px;
            background: var(--cream);
            box-shadow: 0 28px 80px rgba(75, 37, 25, .16);
        }

        .intro {
            min-height: 520px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 46px;
            color: #fff8f0;
            background: linear-gradient(155deg, #9d3030, #651919);
        }

        .brand { font-family: Georgia, serif; font-size: 1.25rem; font-weight: 800; }
        .intro h1 { max-width: 360px; margin: 0 0 14px; font-family: Georgia, serif; font-size: clamp(2.15rem, 5vw, 3.55rem); line-height: 1; }
        .intro p { max-width: 390px; margin: 0; color: rgba(255, 248, 240, .78); line-height: 1.65; }
        .campaign { color: var(--gold); font-size: .8rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }

        .panel { display: grid; align-content: center; padding: 46px; }
        .eyebrow { margin: 0 0 8px; color: var(--red); font-size: .76rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h2 { margin: 0; font-family: Georgia, serif; font-size: 2rem; }
        .lead { margin: 9px 0 28px; color: var(--muted); line-height: 1.55; }
        .button {
            width: 100%;
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 11px 18px;
            border: 1px solid var(--line);
            border-radius: 10px;
            color: var(--ink);
            background: white;
            font: inherit;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }
        .button:hover { color: var(--red-dark); background: #f8f3ed; }
        .button.google { color: #fff; border-color: var(--red); background: var(--red); }
        .button.google:hover { color: #fff; background: var(--red-dark); }
        .button.guest { margin-top: 10px; color: var(--red); }
        .error { margin: 0 0 18px; padding: 11px 13px; border-radius: 10px; color: #812121; background: #fde8e8; font-size: .88rem; }

        @media (max-width: 760px) {
            .login-shell { grid-template-columns: 1fr; }
            .intro { min-height: 260px; padding: 32px; }
            .panel { padding: 34px 28px; }
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <section class="intro">
            <div class="brand">Warisan Makan</div>
            <div>
                <h1>{{ __('Preserving every flavourful story.') }}</h1>
                <p>{{ __('Discover, document, and protect Malaysia\'s culinary heritage through one shared platform.') }}</p>
            </div>
            <div class="campaign">Visit Malaysia 2026</div>
        </section>

        <section class="panel">
            <p class="eyebrow">{{ __('User sign in') }}</p>
            <h2>{{ __('Welcome back') }}</h2>
            <p class="lead">{{ __('Continue with your Google account to submit heritage shop information, manage drafts, and track your contributions.') }}</p>

            @if ($errors->any())
                <div class="error" role="alert">{{ $errors->first() }}</div>
            @endif

            <a class="button google" href="{{ route('auth.google') }}">{{ __('Sign in with Google') }}</a>
            <a class="button guest" href="{{ route('guest.continue') }}">{{ __('Continue as Guest') }}</a>
        </section>
    </main>
</body>
</html>
