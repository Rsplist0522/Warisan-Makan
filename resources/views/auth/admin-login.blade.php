<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.brand-favicon')
    <title>Admin sign in - Warisan Makan</title>
    @fonts
    <style>
        :root {
            color-scheme: light;
            --ink: #241f1d;
            --muted: #74645c;
            --accent: #8f2929;
            --accent-dark: #701e1e;
            --panel: #fffdf9;
            --line: rgba(42, 31, 27, .14);
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            margin: 0;
            padding: 28px 18px;
            color: var(--ink);
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            background:
                linear-gradient(120deg, rgba(36, 31, 29, .05) 0 22%, transparent 22% 100%),
                #f3ede6;
        }

        .card {
            width: min(440px, 100%);
            padding: 34px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--panel);
            box-shadow: 0 22px 60px rgba(64, 39, 28, .13);
        }

        .brand { margin: 0 0 24px; color: var(--accent); font-family: Georgia, serif; font-size: 1.15rem; font-weight: 800; }
        .eyebrow { margin: 0 0 8px; color: var(--muted); font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 0; font-family: Georgia, serif; font-size: 2rem; line-height: 1.05; }
        .lead { margin: 10px 0 25px; color: var(--muted); line-height: 1.55; }
        .field { margin-bottom: 17px; }
        label { display: block; margin-bottom: 7px; font-size: .88rem; font-weight: 750; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            color: var(--ink);
            background: white;
            outline: none;
        }
        .password-control { position: relative; }
        .password-control input { padding-right: 48px; }
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 10px;
            display: inline-grid;
            width: 32px;
            height: 32px;
            place-items: center;
            padding: 0;
            border: 0;
            border-radius: 6px;
            color: var(--muted);
            background: transparent;
            cursor: pointer;
            transform: translateY(-50%);
        }
        .password-toggle:hover,
        .password-toggle:focus-visible { color: var(--accent); background: rgba(143, 41, 41, .08); outline: none; }
        .password-toggle svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; }
        .password-toggle .password-icon[hidden] { display: none !important; }
        input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(143, 41, 41, .11); }
        .remember { display: flex; align-items: center; gap: 8px; margin: -2px 0 18px; color: var(--muted); font-size: .88rem; }
        .remember input { accent-color: var(--accent); }
        .button {
            width: 100%;
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 18px;
            border: 0;
            border-radius: 10px;
            color: white;
            background: var(--accent);
            font: inherit;
            font-weight: 800;
            cursor: pointer;
        }
        .button:hover { background: var(--accent-dark); }
        .error { margin: 0 0 18px; padding: 11px 13px; border-radius: 10px; color: #812121; background: #fde8e8; font-size: .88rem; }
    </style>
</head>
<body>
    <main class="card">
        <p class="brand">Warisan Makan</p>
        <p class="eyebrow">Administrator access</p>
        <h1>Admin sign in</h1>
        <p class="lead">Use an administrator account to open the management portal.</p>

        @if ($errors->any())
            <div class="error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="password-control">
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <button class="password-toggle" id="password-toggle" type="button" aria-label="Show password" aria-pressed="false">
                        <svg class="password-icon" id="password-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 3l18 18"></path>
                            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                            <path d="M9.9 4.3A10.8 10.8 0 0 1 12 4c5.2 0 9.2 4.3 10 8-.3 1.4-1 2.7-2 3.8"></path>
                            <path d="M6.2 6.2C4.4 7.5 3.2 9.4 2 12c.8 3.7 4.8 8 10 8 1.5 0 2.8-.3 4-.9"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <label class="remember" for="remember">
                <input id="remember" name="remember" type="checkbox" value="1">
                Keep me signed in
            </label>
            <button class="button" type="submit">Open admin dashboard</button>
        </form>
    </main>
    <script>
        (() => {
            const password = document.getElementById('password');
            const toggle = document.getElementById('password-toggle');
            const icon = document.getElementById('password-icon');
            const closedIconMarkup = `
                <path d="M3 3l18 18"></path>
                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                <path d="M9.9 4.3A10.8 10.8 0 0 1 12 4c5.2 0 9.2 4.3 10 8-.3 1.4-1 2.7-2 3.8"></path>
                <path d="M6.2 6.2C4.4 7.5 3.2 9.4 2 12c.8 3.7 4.8 8 10 8 1.5 0 2.8-.3 4-.9"></path>`;
            const openIconMarkup = `
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path>
                <circle cx="12" cy="12" r="2.5"></circle>`;

            if (!password || !toggle || !icon) return;

            toggle.addEventListener('click', () => {
                const isVisible = password.type === 'text';
                password.type = isVisible ? 'password' : 'text';
                toggle.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
                toggle.setAttribute('aria-pressed', String(!isVisible));
                icon.innerHTML = isVisible ? closedIconMarkup : openIconMarkup;
            });
        })();
    </script>
</body>
</html>
