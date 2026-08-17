<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Community Contribution') - Warisan Makan</title>
    @fonts
    <style>
        :root {
            color-scheme: light;
            --wm-bg: #fbf2e7;
            --wm-panel: #fff8f0;
            --wm-text: #5b4335;
            --wm-muted: #8c6f5f;
            --wm-accent: #b34d35;
            --wm-accent-strong: #7d4634;
            --wm-accent-soft: rgba(179, 77, 53, .1);
            --wm-border: rgba(177, 140, 106, .16);
            --wm-shadow: 0 18px 36px rgba(104, 71, 42, .08);
            --wm-highlight: #d19c3b;
            --wm-success: #296447;
            --wm-danger: #b42318;
            --wm-info: #315d83;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--wm-text);
            background: linear-gradient(180deg, #fbf2e7 0%, #f5e4d5 100%);
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                repeating-linear-gradient(90deg, transparent 0 18px, rgba(140, 111, 95, .035) 18px 20px),
                repeating-linear-gradient(0deg, transparent 0 18px, rgba(140, 111, 95, .026) 18px 20px);
            opacity: .42;
            z-index: -1;
        }

        a { color: inherit; }
        button, input, textarea, select { font: inherit; }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 1px solid var(--wm-border);
            background: rgba(255, 248, 240, .92);
            backdrop-filter: blur(16px);
        }

        .topbar-inner {
            width: min(1180px, calc(100% - 32px));
            min-height: 68px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .brand {
            margin-right: auto;
            color: var(--wm-accent-strong);
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1.2rem;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .nav-links { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

        .nav-link, .logout-button {
            border: 0;
            border-radius: 999px;
            background: transparent;
            padding: 9px 12px;
            color: var(--wm-muted);
            font-size: .88rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .nav-link:hover, .nav-link.active, .logout-button:hover {
            color: var(--wm-accent);
            background: var(--wm-accent-soft);
        }

        .inline-form { display: inline; margin: 0; }

        .page-shell {
            width: min(1120px, calc(100% - 32px));
            margin: 34px auto 64px;
            padding: 28px;
            border: 1px solid rgba(177, 140, 106, .22);
            border-radius: 24px;
            background: linear-gradient(135deg, #fff8ef 0%, #f4e1c5 100%);
            box-shadow: 0 18px 38px rgba(104, 71, 42, .08);
        }

        .page-header {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
        }

        .eyebrow {
            margin: 0 0 7px;
            color: var(--wm-accent);
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        h1, h2, h3, p { overflow-wrap: anywhere; }
        h1 { margin: 0; font-size: clamp(2rem, 5vw, 3.5rem); line-height: 1; letter-spacing: 0; }
        h2 { margin: 0; font-size: 1.25rem; }
        h3 { margin: 0; font-size: 1rem; }
        .muted { color: var(--wm-muted); }
        .page-header p:last-child { margin: 9px 0 0; color: var(--wm-muted); }

        .status-banner {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid var(--wm-border);
            border-radius: 16px;
            background: rgba(255, 255, 255, .78);
        }

        .status-banner.success { border-color: rgba(41, 100, 71, .22); background: rgba(41, 100, 71, .09); color: var(--wm-success); }
        .status-banner.error { border-color: rgba(180, 35, 24, .2); background: rgba(180, 35, 24, .08); color: var(--wm-danger); }
        .status-banner ul { margin: 8px 0 0; padding-left: 20px; }

        .panel, .form-section, .record-card, .stat-card {
            border: 1px solid var(--wm-border);
            border-radius: 18px;
            background: rgba(255, 253, 249, .72);
            box-shadow: none;
        }

        .panel { padding: 22px; }
        .form-grid { display: grid; gap: 16px; }
        .form-section { display: grid; gap: 16px; padding: 19px; }
        .section-title { color: var(--wm-accent); font-family: Georgia, 'Times New Roman', serif; font-size: 1.08rem; }

        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .field-grid.three { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .field { display: grid; gap: 7px; }
        .field.full { grid-column: 1 / -1; }
        label { font-size: .88rem; font-weight: 750; }
        .required::after { content: ' *'; color: var(--wm-danger); }

        input:not([type='hidden']):not([type='checkbox']):not([type='radio']), textarea, select {
            width: 100%;
            border: 1px solid var(--wm-border);
            border-radius: 14px;
            background: rgba(255, 255, 255, .96);
            color: var(--wm-text);
            padding: 13px 14px;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        textarea { min-height: 112px; resize: vertical; }
        input:focus, textarea:focus, select:focus { border-color: rgba(154, 43, 43, .45); box-shadow: 0 0 0 4px rgba(154, 43, 43, .1); }
        .help-text, .field-error { margin: 0; font-size: .82rem; }
        .help-text { color: var(--wm-muted); }
        .field-error { color: var(--wm-danger); }

        .soft-card { border: 1px solid var(--wm-border); border-radius: 14px; background: #fdfaf6; overflow: hidden; }
        .soft-card-head, .soft-card-row { display: grid; grid-template-columns: 110px 1fr 1fr; gap: 10px; padding: 9px 14px; align-items: center; }
        .soft-card-head { background: linear-gradient(135deg, rgba(154, 43, 43, .08), rgba(199, 154, 40, .12)); color: var(--wm-muted); font-size: .72rem; font-weight: 800; text-transform: uppercase; }
        .soft-card-row { border-top: 1px solid var(--wm-border); font-size: .86rem; }
        .day-label { font-weight: 700; }
        .close-cell { display: flex; align-items: center; gap: 10px; }
        .checkbox-input { width: 17px; height: 17px; accent-color: var(--wm-accent); }

        .repeat-shell { display: grid; gap: 10px; }
        .repeat-row { display: grid; grid-template-columns: 1fr 2fr auto; gap: 10px; align-items: start; }
        .repeat-actions { padding-top: 23px; }
        .repeat-row:not(:first-child) .repeat-actions { padding-top: 0; }
        .mini-button, .link-button { border-radius: 10px; cursor: pointer; font-weight: 750; }
        .mini-button { border: 1px solid var(--wm-border); background: #fff; padding: 10px 12px; color: var(--wm-danger); }
        .link-button { justify-self: start; border: 1px dashed var(--wm-accent); background: transparent; padding: 8px 14px; color: var(--wm-accent); }

        .actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 4px; }
        .button {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: 999px;
            padding: 0 18px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }
        .button.primary { background: var(--wm-accent); color: #fff; box-shadow: 0 10px 24px rgba(179, 77, 53, .18); }
        .button.secondary { border-color: var(--wm-border); background: transparent; color: var(--wm-text); }
        .button.danger { border-color: rgba(180, 35, 24, .22); background: rgba(180, 35, 24, .08); color: var(--wm-danger); }
        .button.info { border-color: rgba(49, 93, 131, .2); background: rgba(49, 93, 131, .09); color: var(--wm-info); }
        .button.small { min-height: 36px; padding: 0 13px; font-size: .82rem; }
        .button:hover { transform: translateY(-1px); }

        .filters { display: grid; grid-template-columns: minmax(220px, 2fr) minmax(160px, 1fr) auto; gap: 10px; align-items: end; margin-bottom: 18px; }
        .filters.four { grid-template-columns: 1.5fr 1fr 1fr 1fr auto; }
        .record-list { display: grid; gap: 12px; }
        .record-card { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 20px; align-items: center; padding: 18px; }
        .record-card p { margin: 6px 0 0; color: var(--wm-muted); }
        .record-meta { display: flex; flex-wrap: wrap; gap: 8px 18px; margin-top: 10px; color: var(--wm-muted); font-size: .83rem; }
        .record-actions { display: flex; flex-wrap: wrap; justify-content: end; gap: 8px; }

        .badge { display: inline-flex; align-items: center; width: fit-content; border-radius: 999px; padding: 5px 9px; font-size: .72rem; font-weight: 850; text-transform: uppercase; letter-spacing: .035em; }
        .badge-draft { background: rgba(109, 91, 79, .12); color: var(--wm-muted); }
        .badge-pending_review { background: rgba(199, 154, 40, .16); color: #72520d; }
        .badge-under_review { background: rgba(49, 93, 131, .13); color: var(--wm-info); }
        .badge-revision_required { background: rgba(180, 35, 24, .1); color: var(--wm-danger); }
        .badge-approved { background: rgba(41, 100, 71, .13); color: var(--wm-success); }
        .badge-rejected { background: rgba(180, 35, 24, .13); color: var(--wm-danger); }
        .badge-withdrawn { background: rgba(109, 91, 79, .12); color: var(--wm-muted); }
        .badge-deleted { background: rgba(45, 35, 32, .13); color: var(--wm-text); }
        .badge-pending { background: rgba(199, 154, 40, .16); color: #72520d; }
        .badge-needs_information { background: rgba(180, 35, 24, .1); color: var(--wm-danger); }

        .empty-state { padding: 46px 24px; text-align: center; }
        .empty-state h2 { margin-bottom: 7px; }
        .empty-state p { margin: 0 0 18px; color: var(--wm-muted); }

        .detail-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr); gap: 16px; align-items: start; }
        .detail-stack { display: grid; gap: 16px; }
        .definition-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 22px; margin: 0; }
        .definition-grid > div { border-bottom: 1px solid var(--wm-border); padding-bottom: 11px; }
        .definition-grid .full { grid-column: 1 / -1; }
        dt { color: var(--wm-muted); font-size: .76rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
        dd { margin: 5px 0 0; white-space: pre-line; }

        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(145px, 1fr)); gap: 10px; }
        .media-card { position: relative; overflow: hidden; min-height: 120px; border: 1px solid var(--wm-border); border-radius: 14px; background: #fff; }
        .media-card img, .media-card video { display: block; width: 100%; height: 150px; object-fit: cover; }
        .media-card .remove-media { display: flex; gap: 7px; padding: 9px; align-items: center; color: var(--wm-danger); font-size: .78rem; font-weight: 700; }
        .media-placeholder { height: 150px; display: grid; place-items: center; padding: 10px; color: var(--wm-muted); text-align: center; }
        .preview-note { grid-column: 1 / -1; color: var(--wm-muted); font-size: .8rem; }

        .timeline { display: grid; gap: 0; margin-top: 12px; }
        .timeline-item { position: relative; padding: 0 0 18px 22px; border-left: 2px solid rgba(154, 43, 43, .18); }
        .timeline-item::before { content: ''; position: absolute; left: -6px; top: 2px; width: 10px; height: 10px; border-radius: 50%; background: var(--wm-accent); }
        .timeline-item:last-child { padding-bottom: 0; }
        .timeline-item p { margin: 4px 0 0; color: var(--wm-muted); font-size: .84rem; }

        .notification { border-left: 3px solid var(--wm-highlight); padding: 11px 12px; background: rgba(255,255,255,.62); }
        .notification + .notification { margin-top: 8px; }
        .notification.unread { background: rgba(199, 154, 40, .1); }
        .notification p { margin: 4px 0 0; color: var(--wm-muted); font-size: .83rem; }

        .pagination { margin-top: 18px; }
        nav[role='navigation'] svg { width: 18px; height: 18px; }

        @media (max-width: 900px) {
            .topbar-inner { align-items: flex-start; padding: 14px 0; }
            .nav-links { justify-content: end; }
            .detail-grid { grid-template-columns: 1fr; }
            .filters, .filters.four { grid-template-columns: 1fr 1fr; }
            .filters .filter-action { grid-column: 1 / -1; }
        }

        @media (max-width: 650px) {
            .topbar { position: static; }
            .topbar-inner { display: grid; }
            .brand { margin: 0; }
            .nav-links { justify-content: start; }
            .page-shell { width: min(100% - 24px, 1120px); padding: 18px; margin-top: 18px; }
            .page-header, .record-card { display: grid; }
            .record-actions { justify-content: start; }
            .field-grid, .field-grid.three, .definition-grid, .filters, .filters.four { grid-template-columns: 1fr; }
            .soft-card-head, .soft-card-row { grid-template-columns: 78px 1fr 1fr; padding-inline: 9px; }
            .repeat-row { grid-template-columns: 1fr; }
            .repeat-actions { padding-top: 0; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="{{ route('home') }}">Warisan Makan</a>
            <nav class="nav-links" aria-label="Community contribution navigation">
                <a class="nav-link {{ request()->routeIs('community-contribution.create', 'community-contribution.edit') ? 'active' : '' }}" href="{{ route('community-contribution.create') }}">Submit shop</a>
                <a class="nav-link {{ request()->routeIs('community-contribution.drafts*') ? 'active' : '' }}" href="{{ route('community-contribution.drafts') }}">Drafts</a>
                <a class="nav-link {{ request()->routeIs('community-contribution.contributions*') ? 'active' : '' }}" href="{{ route('community-contribution.contributions') }}">My contributions</a>
                <a class="nav-link {{ request()->routeIs('community-contribution.correction-requests*') ? 'active' : '' }}" href="{{ route('community-contribution.correction-requests') }}">My correction requests</a>
                <form class="inline-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-button" type="submit">Log out</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="page-shell">
        @if (session('status'))
            <div class="status-banner success" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="status-banner error" role="alert">
                <strong>Please fix the following:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
