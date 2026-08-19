<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - Warisan Makan</title>
    @fonts
    <style>
        :root {
            color-scheme: light;
            --sidebar: #371919;
            --sidebar-soft: #4a2222;
            --accent: #a33636;
            --gold: #c4933c;
            --ink: #2e2420;
            --muted: #77665c;
            --canvas: #f7f1ea;
            --panel: #fffdf9;
            --line: rgba(66, 43, 32, .12);
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; color: var(--ink); background: var(--canvas); font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        a { color: inherit; }
        button { font: inherit; }
        .shell { min-height: 100vh; display: grid; grid-template-columns: 270px 1fr; }
        .sidebar { position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; padding: 28px 20px; color: #fff5ec; background: linear-gradient(180deg, var(--sidebar), #281010); }
        .brand { display: flex; align-items: center; gap: 12px; padding: 2px 10px 28px; border-bottom: 1px solid rgba(255,255,255,.1); font-family: Georgia, serif; font-size: 1.2rem; font-weight: 800; }
        .brand-mark { width: 38px; height: 38px; display: grid; place-items: center; border: 1px solid rgba(255,255,255,.2); border-radius: 12px; color: #3b1b16; background: var(--gold); font-family: Georgia, serif; }
        .nav-label { margin: 27px 12px 10px; color: rgba(255,245,236,.48); font-size: .68rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
        .nav { display: grid; gap: 5px; }
        .nav-item { display: flex; align-items: center; gap: 11px; padding: 11px 12px; border-radius: 10px; color: rgba(255,245,236,.7); font-size: .88rem; text-decoration: none; }
        .nav-item::before { content: ''; width: 7px; height: 7px; flex: 0 0 auto; border: 1px solid currentColor; border-radius: 50%; }
        .nav-item.active, .nav-item:hover { color: #fff; background: var(--sidebar-soft); }
        .nav-item.active::before { border-color: var(--gold); background: var(--gold); }
        .nav-item.placeholder { color: rgba(255,245,236,.42); }
        .nav-item.placeholder:hover { color: rgba(255,245,236,.78); background: rgba(255,255,255,.06); }
        .nav-item span { min-width: 0; }
        .nav-item small { margin-left: auto; color: rgba(255,245,236,.42); font-size: .62rem; font-weight: 800; text-transform: uppercase; }
        .subnav { margin: 6px 0 8px 18px; padding-left: 13px; border-left: 1px solid rgba(255,255,255,.13); }
        .subnav .nav-item { padding: 9px 10px; font-size: .8rem; }
        .subnav .nav-item::before { width: 5px; height: 5px; }
        .sidebar-footer { margin-top: auto; padding-top: 22px; border-top: 1px solid rgba(255,255,255,.1); }
        .admin-name { margin: 0 0 3px; font-size: .88rem; font-weight: 800; }
        .admin-role { margin: 0 0 14px; color: rgba(255,245,236,.52); font-size: .76rem; }
        .logout { width: 100%; padding: 9px 12px; border: 1px solid rgba(255,255,255,.16); border-radius: 9px; color: #fff5ec; background: transparent; cursor: pointer; text-align: left; }
        .logout:hover { background: rgba(255,255,255,.08); }
        .main { min-width: 0; }
        .topbar { min-height: 76px; display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 16px 34px; border-bottom: 1px solid var(--line); background: rgba(255,253,249,.9); }
        .topbar h1 { margin: 0; font-family: Georgia, serif; font-size: 1.35rem; }
        .topbar p { margin: 3px 0 0; color: var(--muted); font-size: .82rem; }
        .content { padding: 34px; }
        .page-header { display: flex; align-items: end; justify-content: space-between; gap: 18px; margin-bottom: 22px; }
        .eyebrow { margin: 0 0 7px; color: var(--accent); font-size: .78rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        .page-header h1 { margin: 0; font-family: Georgia, serif; font-size: clamp(2rem, 5vw, 3.2rem); line-height: 1; }
        .page-header p:last-child { margin: 9px 0 0; color: var(--muted); }
        h1, h2, h3, p { overflow-wrap: anywhere; }
        h2 { margin: 0; font-size: 1.25rem; }
        h3 { margin: 0; font-size: 1rem; }
        .muted { color: var(--muted); }
        .status-banner { margin-bottom: 18px; padding: 14px 16px; border: 1px solid var(--line); border-radius: 12px; background: rgba(255, 255, 255, .78); }
        .status-banner.success { border-color: rgba(41, 100, 71, .22); background: rgba(41, 100, 71, .09); color: #296447; }
        .status-banner.error { border-color: rgba(180, 35, 24, .2); background: rgba(180, 35, 24, .08); color: #b42318; }
        .status-banner ul { margin: 8px 0 0; padding-left: 20px; }
        .panel, .record-card, .stat-card { border: 1px solid var(--line); border-radius: 14px; background: var(--panel); box-shadow: 0 12px 36px rgba(69, 42, 28, .05); }
        .panel { padding: 22px; }
        .filters { display: grid; grid-template-columns: minmax(220px, 2fr) minmax(160px, 1fr) auto; gap: 10px; align-items: end; margin-bottom: 18px; }
        .filters.four { grid-template-columns: 1.5fr 1fr 1fr 1fr auto; }
        .filters.six { grid-template-columns: 1.5fr 1fr 1.2fr 1fr 1fr auto; }
        .field { display: grid; gap: 7px; }
        label { font-size: .88rem; font-weight: 750; }
        input:not([type='hidden']):not([type='checkbox']):not([type='radio']), textarea, select {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: rgba(255, 255, 255, .96);
            color: var(--ink);
            padding: 12px 13px;
            outline: none;
        }
        textarea { min-height: 112px; resize: vertical; }
        input:focus, textarea:focus, select:focus { border-color: rgba(163, 54, 54, .45); box-shadow: 0 0 0 4px rgba(163, 54, 54, .1); }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 4px; }
        .button { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid transparent; border-radius: 10px; padding: 0 15px; font-weight: 800; text-decoration: none; cursor: pointer; }
        .button.primary { background: var(--gold); color: #3f2a0d; }
        .button.secondary { border-color: var(--line); background: white; color: var(--ink); }
        .button.danger { border-color: rgba(180, 35, 24, .22); background: rgba(180, 35, 24, .08); color: #b42318; }
        .button.info { border-color: rgba(49, 93, 131, .2); background: rgba(49, 93, 131, .09); color: #315d83; }
        .button.small { min-height: 36px; padding: 0 13px; font-size: .82rem; }
        .record-list { display: grid; gap: 12px; }
        .record-card { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 20px; align-items: center; padding: 18px; }
        .record-card p { margin: 6px 0 0; color: var(--muted); }
        .record-meta { display: flex; flex-wrap: wrap; gap: 8px 18px; margin-top: 10px; color: var(--muted); font-size: .83rem; }
        .record-actions { display: flex; flex-wrap: wrap; justify-content: end; gap: 8px; }
        .badge { display: inline-flex; align-items: center; width: fit-content; border-radius: 999px; padding: 5px 9px; font-size: .72rem; font-weight: 850; text-transform: uppercase; letter-spacing: .035em; }
        .badge-draft { background: rgba(109, 91, 79, .12); color: var(--muted); }
        .badge-pending_review { background: rgba(199, 154, 40, .16); color: #72520d; }
        .badge-under_review { background: rgba(49, 93, 131, .13); color: #315d83; }
        .badge-revision_required { background: rgba(180, 35, 24, .1); color: #b42318; }
        .badge-approved { background: rgba(41, 100, 71, .13); color: #296447; }
        .badge-rejected { background: rgba(180, 35, 24, .13); color: #b42318; }
        .badge-withdrawn { background: rgba(109, 91, 79, .12); color: var(--muted); }
        .badge-deleted { background: rgba(45, 35, 32, .13); color: var(--ink); }
        .badge-pending { background: rgba(199, 154, 40, .16); color: #72520d; }
        .badge-needs_information { background: rgba(180, 35, 24, .1); color: #b42318; }
        .empty-state { padding: 46px 24px; text-align: center; }
        .empty-state h2 { margin-bottom: 7px; }
        .empty-state p { margin: 0 0 18px; color: var(--muted); }
        .module-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .module-card { min-height: 190px; display: flex; flex-direction: column; padding: 21px; border: 1px solid var(--line); border-radius: 14px; background: var(--panel); box-shadow: 0 10px 28px rgba(77, 48, 34, .06); text-decoration: none; }
        .module-card:hover { border-color: rgba(163,54,54,.3); transform: translateY(-1px); }
        .module-card.inactive { color: var(--muted); background: rgba(255,253,249,.62); }
        .module-card h3 { margin: 0 0 8px; font-size: 1rem; }
        .module-card p { margin: 0; color: var(--muted); font-size: .84rem; line-height: 1.5; }
        .module-card strong { margin-top: auto; padding-top: 18px; color: var(--accent); font-size: .78rem; }
        .module-card.inactive strong { color: #8a7a70; }
        .detail-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr); gap: 16px; align-items: start; }
        .detail-stack { display: grid; gap: 16px; }
        .definition-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 22px; margin: 0; }
        .definition-grid > div { border-bottom: 1px solid var(--line); padding-bottom: 11px; }
        .definition-grid .full { grid-column: 1 / -1; }
        dt { color: var(--muted); font-size: .76rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
        dd { margin: 5px 0 0; white-space: pre-line; }
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(145px, 1fr)); gap: 10px; }
        .media-card { position: relative; overflow: hidden; min-height: 120px; border: 1px solid var(--line); border-radius: 12px; background: #fff; }
        .media-card img, .media-card video { display: block; width: 100%; height: 150px; object-fit: cover; }
        .timeline { display: grid; gap: 0; margin-top: 12px; }
        .timeline-item { position: relative; padding: 0 0 18px 22px; border-left: 2px solid rgba(163, 54, 54, .18); }
        .timeline-item::before { content: ''; position: absolute; left: -6px; top: 2px; width: 10px; height: 10px; border-radius: 50%; background: var(--accent); }
        .timeline-item:last-child { padding-bottom: 0; }
        .timeline-item p { margin: 4px 0 0; color: var(--muted); font-size: .84rem; }
        .pagination { margin-top: 18px; }
        nav[role='navigation'] svg { width: 18px; height: 18px; }
        @media (max-width: 850px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .subnav { grid-column: 1 / -1; margin-left: 0; }
            .sidebar-footer { margin-top: 24px; }
            .topbar, .content { padding-inline: 20px; }
            .filters, .filters.four, .filters.six { grid-template-columns: 1fr 1fr; }
            .filters .filter-action { grid-column: 1 / -1; }
            .module-grid { grid-template-columns: 1fr; }
            .detail-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 520px) {
            .nav, .filters, .filters.four, .filters.six, .definition-grid { grid-template-columns: 1fr; }
            .page-header, .record-card { display: grid; }
            .record-actions { justify-content: start; }
        }
    </style>
    @stack('styles')
</head>
<body>
    @php
        $communityContributionActive = request()->routeIs('admin.community-contributions.*');
        $blindBoxActive = request()->routeIs('admin.blind-box-items.*');
        $placeholderModules = [
            'heritage-registry' => 'Heritage Registry',
            'food-map' => 'Food Map',
            'stories-editorial' => 'Stories & Editorial',
            'events-trails' => 'Events & Trails',
            'users-roles' => 'Users & Roles',
            'reports-analytics' => 'Reports & Analytics',
        ];
    @endphp
    <div class="shell">
        <aside class="sidebar">
            <div class="brand"><span class="brand-mark">W</span> Warisan Makan</div>
            <p class="nav-label">Admin home</p>
            <nav class="nav" aria-label="Administrator modules">
                <a class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
            </nav>

            <p class="nav-label">Modules</p>
            <nav class="nav" aria-label="Administrator modules">
                <a class="nav-item {{ $communityContributionActive ? 'active' : '' }}" href="{{ route('admin.community-contributions.submissions') }}">
                    <span>Community Contribution</span>
                </a>
                @if ($communityContributionActive)
                    <div class="subnav" aria-label="Community Contribution functions">
                        <a class="nav-item {{ request()->routeIs('admin.community-contributions.submissions') ? 'active' : '' }}" href="{{ route('admin.community-contributions.submissions') }}">Review Queue</a>
                        <a class="nav-item {{ request()->routeIs('admin.community-contributions.show') ? 'active' : '' }}" href="{{ route('admin.community-contributions.submissions') }}">Review Submission</a>
                        <a class="nav-item {{ request()->routeIs('admin.community-contributions.correction-requests*') ? 'active' : '' }}" href="{{ route('admin.community-contributions.correction-requests') }}">Correction Requests</a>
                        <a class="nav-item {{ request()->routeIs('admin.community-contributions.history') ? 'active' : '' }}" href="{{ route('admin.community-contributions.history') }}">Admin History</a>
                    </div>
                @endif

                <a class="nav-item {{ $blindBoxActive ? 'active' : '' }}" href="{{ route('admin.blind-box-items.index') }}">
                    <span>Blind Box</span>
                </a>

                @foreach ($placeholderModules as $slug => $name)
                    <a class="nav-item placeholder {{ request()->routeIs('admin.modules.show') && request()->route('moduleSlug') === $slug ? 'active' : '' }}" href="{{ route('admin.modules.show', $slug) }}">
                        <span>{{ $name }}</span>
                        <small>soon</small>
                    </a>
                @endforeach
            </nav>
            <div class="sidebar-footer">
                <p class="admin-name">{{ auth()->user()->name }}</p>
                <p class="admin-role">System administrator</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout" type="submit">Sign out</button>
                </form>
            </div>
        </aside>

        <section class="main">
            <header class="topbar">
                <div>
                    <h1>@yield('page-title', 'Admin Dashboard')</h1>
                    <p>Warisan Makan management portal</p>
                </div>
            </header>
            <main class="content">@yield('content')</main>
        </section>
    </div>
</body>
</html>
