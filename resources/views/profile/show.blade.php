<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Warisan Makan</title>
    @fonts
    <style>
        :root {
            color-scheme: light;
            --wm-bg: #fbf2e7;
            --wm-panel: #fff8f0;
            --wm-ink: #5b4335;
            --wm-muted: #8c6f5f;
            --wm-border: rgba(177, 140, 106, .16);
            --wm-accent: #b34d35;
            --wm-gold: #d19c3b;
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
        }

        .topbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            padding: 24px 24px;
            margin-bottom: 20px;
            border-radius: 20px;
            background: linear-gradient(180deg, #fff7f0 0%, #fdf0e3 100%);
            border: 1px solid rgba(177, 140, 106, .2);
            box-shadow: 0 18px 36px rgba(104, 71, 42, .08);
        }

        .section-heading {
            margin: 0;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1.85rem;
            line-height: 1.05;
            color: #7d4634;
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
            padding: 0 18px;
            border-radius: 999px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            font-weight: 700;
            white-space: nowrap;
        }

        .button {
            background: var(--wm-accent);
            color: #fff;
        }

        .button-secondary {
            background: transparent;
            color: var(--wm-ink);
            border-color: var(--wm-border);
        }

        .status-alert {
            margin-bottom: 18px;
            padding: 16px 18px;
            border-radius: 14px;
            background: rgba(70, 128, 74, .12);
            color: #14461f;
            border: 1px solid rgba(70, 128, 74, .18);
        }

        /* ---- Single unified profile card ---- */

        .profile-card {
            border-radius: 28px;
            background: var(--wm-panel);
            border: 1px solid var(--wm-border);
            box-shadow: 0 14px 32px rgba(113, 80, 53, .08);
            overflow: hidden;
        }

        .profile-identity {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 28px 28px 24px;
            border-bottom: 1px solid var(--wm-border);
        }

        .avatar {
            flex: 0 0 auto;
            width: 68px;
            height: 68px;
            display: grid;
            place-items: center;
            border-radius: 18px;
            background: linear-gradient(160deg, #f3e3d5, #e6d0bb);
            color: #a14d39;
            font-size: 1.4rem;
            font-weight: 800;
            overflow: hidden;
            box-shadow: 0 6px 14px rgba(113, 80, 53, .1);
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
            font-size: 1.15rem;
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
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(209, 156, 59, .16);
            color: #8a5f19;
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .profile-fields {
            padding: 22px 28px 28px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .profile-field {
            display: grid;
            gap: 6px;
            padding: 14px 16px;
            border-radius: 16px;
            background: #fff7f1;
            border: 1px solid rgba(177, 140, 106, .14);
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

        .profile-footer-actions {
            padding: 0 28px 28px;
            display: flex;
            justify-content: flex-end;
        }

        @media (max-width: 640px) {
            .profile-fields { grid-template-columns: 1fr; }
            .profile-identity { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    <div class="page">
        <header class="topbar">
            <div>
                <h1 class="section-heading">Profile</h1>
                <p class="section-copy">Manage your WarisanMakan identity, update your contact details, and keep your profile photo current for a personalized experience.</p>
            </div>
            <div class="section-actions">
                <a class="button-secondary" href="{{ route('profile.edit') }}">Edit Profile</a>
                <a class="button" href="{{ route('home') }}">Back to dashboard</a>
            </div>
        </header>

        @if (session('success'))
            <div class="status-alert">{{ session('success') }}</div>
        @endif

        <section class="profile-card">
            <div class="profile-identity">
                <div class="avatar">
                    @if ($user->profile_photo)
                        <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }} profile photo">
                    @else
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    @endif
                </div>
                <div class="identity-text">
                    <p class="identity-name">{{ $user->name }}</p>
                    <p class="identity-email">{{ $user->email }}</p>
                    <span class="identity-badge">{{ $user->isAdmin() ? 'Admin' : 'Member' }}</span>
                </div>
            </div>

            <div class="profile-fields">
                <div class="profile-field">
                    <label>Phone</label>
                    <span class="{{ $user->phone ? '' : 'empty' }}">{{ $user->phone ?: 'Not provided' }}</span>
                </div>

                <div class="profile-field">
                    <label>City</label>
                    <span class="{{ $user->city ? '' : 'empty' }}">{{ $user->city ?: 'Not provided' }}</span>
                </div>

                <div class="profile-field">
                    <label>Member since</label>
                    <span>{{ $user->created_at->format('F j, Y') }}</span>
                </div>

                <div class="profile-field">
                    <label>Role</label>
                    <span>{{ $user->isAdmin() ? 'Admin' : 'Member' }}</span>
                </div>

                <div class="profile-field span-2">
                    <label>Bio</label>
                    <span class="{{ $user->bio ? '' : 'empty' }}">{{ $user->bio ?: 'Share a little about your food heritage interests.' }}</span>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
