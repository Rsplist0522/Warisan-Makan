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
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <label class="remember" for="remember">
                <input id="remember" name="remember" type="checkbox" value="1">
                Keep me signed in
            </label>
            <button class="button" type="submit">Open admin dashboard</button>
        </form>
    </main>
</body>
</html>
