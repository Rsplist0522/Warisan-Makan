<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Home - Warisan Makan</title>
    @fonts
    <style>
        :root {
            color-scheme: light;
            --wm-sidebar: #3b1b18;
            --wm-sidebar-soft: #51251f;
            --wm-bg: #f7f1ea;
            --wm-panel: #fffdf9;
            --wm-ink: #2e2420;
            --wm-muted: #7b6a60;
            --wm-line: rgba(66, 43, 32, .12);
            --wm-accent: #a33a2d;
            --wm-gold: #c89432;
            --wm-green: #3d6f55;
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
            background: linear-gradient(180deg, var(--wm-sidebar), #28100e);
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

        .nav-item.active::before {
            border-color: var(--wm-gold);
            background: var(--wm-gold);
        }

        .nav-item.muted {
            color: rgba(255, 245, 236, .42);
        }

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
                linear-gradient(135deg, rgba(163, 58, 45, .06), transparent 34%),
                linear-gradient(315deg, rgba(61, 111, 85, .07), transparent 38%),
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

        .content {
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 34px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 20px;
            margin-bottom: 24px;
            padding: 30px;
            border-radius: 14px;
            color: #fffaf4;
            background: linear-gradient(125deg, #96352c, #54201b);
            box-shadow: 0 20px 50px rgba(91, 29, 29, .18);
        }

        .eyebrow {
            margin: 0 0 8px;
            color: #e7bf74;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(2rem, 5vw, 3.2rem);
            line-height: 1;
        }

        .page-header p:last-child {
            max-width: 680px;
            margin: 11px 0 0;
            color: rgba(255, 250, 244, .74);
            line-height: 1.6;
        }

        .header-pill {
            display: inline-flex;
            align-items: center;
            min-height: 38px;
            padding: 0 14px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 999px;
            color: #fff5ec;
            background: rgba(255, 255, 255, .08);
            font-size: .8rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .module-card {
            min-height: 202px;
            display: flex;
            flex-direction: column;
            padding: 22px;
            border: 1px solid var(--wm-line);
            border-radius: 14px;
            background: var(--wm-panel);
            box-shadow: 0 12px 32px rgba(77, 48, 34, .07);
            text-decoration: none;
        }

        .module-card h3 {
            margin: 0 0 8px;
            font-size: 1.02rem;
        }

        .module-card p {
            margin: 0;
            color: var(--wm-muted);
            font-size: .86rem;
            line-height: 1.5;
        }

        .module-icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            margin-bottom: 20px;
            border-radius: 12px;
            color: var(--wm-accent);
            background: rgba(163, 58, 45, .1);
        }

        .module-icon svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .module-status {
            margin-top: auto;
            padding-top: 18px;
            color: var(--wm-accent);
            font-size: .78rem;
            font-weight: 850;
        }

        .module-card.is-active {
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease;
        }

        .module-card.is-active:hover {
            border-color: rgba(163, 58, 45, .36);
            background: #fffaf2;
            box-shadow: 0 18px 42px rgba(77, 48, 34, .12);
            transform: translateY(-3px);
        }

        .module-card.is-active .module-icon {
            color: #3f2a0d;
            background: rgba(200, 148, 50, .24);
        }

        .module-card.is-static {
            color: #88766b;
            background: rgba(255, 253, 249, .64);
            box-shadow: 0 8px 22px rgba(77, 48, 34, .04);
        }

        .module-card.is-static .module-icon {
            color: #8f8179;
            background: rgba(66, 43, 32, .08);
        }

        .module-card.is-static .module-status {
            color: #8a7a70;
        }

        @media (max-width: 980px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar {
                position: static;
                height: auto;
            }
            .nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .sidebar-footer { margin-top: 24px; }
            .module-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .topbar,
            .content { padding-inline: 22px; }
        }

        @media (max-width: 620px) {
            .topbar,
            .page-header {
                display: grid;
            }
            .content { padding: 22px 16px 34px; }
            .nav,
            .module-grid { grid-template-columns: 1fr; }
            .page-header { padding: 24px; }
            .header-pill { justify-self: start; }
        }
    </style>
</head>
<body>
    @php
        $userName = auth()->user()->name ?? 'Food Explorer';
        $comingSoonModules = [
            [
                'name' => 'User Management',
                'description' => 'View profile access and account preferences for WarisanMakan.',
                'status' => 'Coming soon',
                'icon' => 'users',
            ],
            [
                'name' => 'Heritage Shop Tracking',
                'description' => 'Track verified heritage eateries, ownership notes, and updates.',
                'status' => 'Coming soon',
                'icon' => 'shop',
            ],
            [
                'name' => 'Food Passport & Achievement',
                'description' => 'Collect stamps, badges, and milestones from heritage food visits.',
                'status' => 'Open module',
                'icon' => 'award',
                'route' => 'passport.index',
            ],
            [
                'name' => 'Food Trail & Navigation',
                'description' => 'Discover curated routes to heritage food spots across Malaysia.',
                'status' => 'Coming soon',
                'icon' => 'map',
            ],
            [
                'name' => 'Blind Box Recommendation',
                'description' => 'Reveal surprise heritage food suggestions matched to your taste.',
                'status' => 'Open module',
                'icon' => 'box',
                'route' => 'blind-box.index',
            ],
        ];
        $leadingModules = array_slice($comingSoonModules, 0, 4);
        $blindBoxModule = $comingSoonModules[4];
    @endphp

    <div class="shell">
        <aside class="sidebar">
            <div class="brand"><span class="brand-mark">W</span> WarisanMakan</div>

            <p class="nav-label">Home</p>
            <nav class="nav" aria-label="User home navigation">
                <a class="nav-item active" href="{{ route('home') }}">Dashboard</a>
                <a class="nav-item" href="{{ route('community-contribution.create') }}">Community Contribution</a>
            </nav>

            <p class="nav-label">Modules</p>
            <nav class="nav" aria-label="Upcoming modules">
                @foreach ($comingSoonModules as $module)
                    @if (isset($module['route']))
                        <a class="nav-item" href="{{ route($module['route']) }}">
                            <span>{{ $module['name'] }}</span>
                        </a>
                    @else
                        <span class="nav-item muted">
                            <span>{{ $module['name'] }}</span>
                            <small>soon</small>
                        </span>
                    @endif
                @endforeach
            </nav>

            <div class="sidebar-footer">
                <p class="user-name">{{ $userName }}</p>
                <p class="user-role">WarisanMakan member</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout" type="submit">Log out</button>
                </form>
            </div>
        </aside>

        <section class="main">
            <header class="topbar">
                <div>
                    <h2>User Dashboard</h2>
                    <p>WarisanMakan heritage food portal</p>
                </div>
            </header>

            <main class="content">
                <header class="page-header">
                    <div>
                        <p class="eyebrow">User home</p>
                        <h1>Welcome back, {{ $userName }}</h1>
                        <p>Explore Malaysian heritage food culture, preserve local food stories, and follow new WarisanMakan modules as they open.</p>
                    </div>
                    <span class="header-pill">Heritage food explorer</span>
                </header>

                <section class="module-grid" aria-label="WarisanMakan modules">
                    @foreach ($leadingModules as $module)
                        @if (isset($module['route']))
                            <a class="module-card is-active" href="{{ route($module['route']) }}">
                                <span class="module-icon" aria-hidden="true">
                                    @include('partials.module-icon', ['icon' => $module['icon']])
                                </span>
                                <h3>{{ $module['name'] }}</h3>
                                <p>{{ $module['description'] }}</p>
                                <strong class="module-status">{{ $module['status'] }}</strong>
                            </a>
                        @else
                            <article class="module-card is-static" aria-disabled="true">
                                <span class="module-icon" aria-hidden="true">
                                    @include('partials.module-icon', ['icon' => $module['icon']])
                                </span>
                                <h3>{{ $module['name'] }}</h3>
                                <p>{{ $module['description'] }}</p>
                                <strong class="module-status">{{ $module['status'] }}</strong>
                            </article>
                        @endif
                    @endforeach

                    <a class="module-card is-active" href="{{ route('community-contribution.create') }}">
                        <span class="module-icon" aria-hidden="true">
                            @include('partials.module-icon', ['icon' => 'community'])
                        </span>
                        <h3>Community Contribution</h3>
                        <p>Submit heritage shop stories, food details, media, and location notes.</p>
                        <strong class="module-status">Open module</strong>
                    </a>

                    <a class="module-card is-active" href="{{ route('blind-box.index') }}">
                        <span class="module-icon" aria-hidden="true">
                            @include('partials.module-icon', ['icon' => $blindBoxModule['icon']])
                        </span>
                        <h3>{{ $blindBoxModule['name'] }}</h3>
                        <p>{{ $blindBoxModule['description'] }}</p>
                        <strong class="module-status">Open module</strong>
                    </a>
                </section>
            </main>
        </section>
    </div>
</body>
</html>
