<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @stack('head')
    <title>@yield('title', __('WarisanMakan')) - Warisan Makan</title>
    @fonts
    @stack('head-scripts')
    <style>
        html { scrollbar-gutter: stable; }

        :root {
            color-scheme: light;
            --wm-bg: #f7f1ea;
            --wm-panel: #fffdf9;
            --wm-text: #2e2420;
            --wm-ink: #2e2420;
            --wm-muted: #7b6a60;
            --wm-accent: #a33a2d;
            --wm-accent-strong: #3b1b18;
            --wm-accent-soft: rgba(163, 58, 45, .1);
            --wm-border: rgba(66, 43, 32, .12);
            --wm-line: rgba(66, 43, 32, .12);
            --wm-shadow: 0 12px 32px rgba(77, 48, 34, .07);
            --wm-highlight: #c89432;
            --wm-gold: #c89432;
            --wm-success: #296447;
            --wm-danger: #b42318;
            --wm-info: #315d83;
            --wm-sidebar: #3b1b18;
            --wm-sidebar-soft: #51251f;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-width: 0;
            min-height: 100vh;
            overflow-x: hidden;
            color: var(--wm-text);
            background:
                linear-gradient(135deg, rgba(163, 58, 45, .06), transparent 34%),
                linear-gradient(315deg, rgba(61, 111, 85, .07), transparent 38%),
                var(--wm-bg);
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        body::-webkit-scrollbar { display: none; }

        a { color: inherit; }
        button, input, textarea, select { font: inherit; }
        h1, h2, h3, p { overflow-wrap: anywhere; }
        h1, h2, h3 { font-family: Georgia, 'Times New Roman', serif; }

        .user-shell { min-height: 100vh; display: grid; grid-template-columns: 268px minmax(0, 1fr); }
        .user-shell.nav-collapsed { grid-template-columns: 82px minmax(0, 1fr); }
        .user-main { min-width: 0; overflow-x: hidden; }
        .user-sidebar, .user-topbar {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .user-sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 28px 20px;
            color: #fff5ec;
            background: linear-gradient(180deg, var(--wm-sidebar), #28100e);
            overflow-y: auto;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .user-sidebar::-webkit-scrollbar { display: none; }

        .user-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            padding: 2px 10px 28px;
            border-bottom: 1px solid rgba(255, 255, 255, .1);
            color: #fff5ec;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1.18rem;
            font-weight: 800;
            text-decoration: none;
        }

        .user-brand-mark {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 12px;
            color: #3b1b16;
            background: var(--wm-highlight);
            font-family: Georgia, 'Times New Roman', serif;
        }

        .user-nav-label {
            margin: 27px 12px 10px;
            color: rgba(255, 245, 236, .48);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        .user-nav { display: grid; gap: 5px; }
        .user-nav-item {
            position: relative;
            display: flex;
            width: 100%;
            min-width: 0;
            align-items: center;
            gap: 11px;
            padding: 11px 12px;
            border-radius: 10px;
            color: rgba(255, 245, 236, .72);
            font-size: .88rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .user-nav-text {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-nav-icon {
            width: 25px;
            height: 25px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 8px;
        }

        .user-nav-icon svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .user-nav-item.active, .user-nav-item:hover { color: #fff; background: var(--wm-sidebar-soft); }
        .user-nav-item.active .user-nav-icon { border-color: rgba(200, 148, 50, .42); background: var(--wm-highlight); color: #3b1b16; }
        .user-nav-group { display: grid; gap: 4px; }
        .user-nav-group summary { list-style: none; cursor: pointer; }
        .user-nav-group summary::-webkit-details-marker { display: none; }
        .user-nav-parent { border: 0; background: transparent; }
        .user-nav-parent.is-active { color: #fffaf4; background: rgba(255, 255, 255, .06); }
        .user-nav-parent.is-active .user-nav-icon { border-color: rgba(200, 148, 50, .32); background: rgba(200, 148, 50, .16); color: #e7bf74; }
        .user-nav-chevron {
            display: grid;
            width: 1rem;
            flex: 0 0 1rem;
            place-items: center;
            margin-left: auto;
            color: rgba(255, 245, 236, .46);
            font-size: .9rem;
            transition: transform .16s ease;
        }
        .user-nav-group[open] .user-nav-chevron { transform: rotate(90deg); }
        .user-nav-submenu {
            display: grid;
            gap: 4px;
            margin: 3px 0 4px 18px;
            padding-left: 13px;
            border-left: 1px solid rgba(255, 255, 255, .12);
        }
        .user-nav-child {
            min-height: 36px;
            padding: 8px 10px;
            color: rgba(255, 245, 236, .66);
            font-size: .8rem;
        }
        .user-nav-child .user-nav-icon {
            width: 21px;
            height: 21px;
            border-radius: 7px;
            background: rgba(255, 255, 255, .035);
        }
        .user-nav-child .user-nav-icon svg { width: 13px; height: 13px; }
        .user-nav-child.active {
            color: #3b1b16;
            background: var(--wm-highlight);
        }
        .user-nav-child.active .user-nav-icon { border-color: rgba(59, 27, 22, .18); background: rgba(59, 27, 22, .12); color: #3b1b16; }

        .user-sidebar-footer {
            margin-top: auto;
            padding-top: 22px;
            border-top: 1px solid rgba(255, 255, 255, .1);
        }

        .user-sidebar-name { margin: 0 0 3px; font-size: .88rem; font-weight: 800; }
        .user-sidebar-role { margin: 0 0 14px; color: rgba(255, 245, 236, .52); font-size: .76rem; }
        .user-logout {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 9px;
            color: #fff5ec;
            background: transparent;
            cursor: pointer;
            text-align: left;
        }
        .user-logout:hover { background: rgba(255, 255, 255, .08); }

        .user-shell.nav-collapsed .user-brand { justify-content: center; padding-inline: 0; }
        .user-shell.nav-collapsed .user-brand-word,
        .user-shell.nav-collapsed .user-nav-label,
        .user-shell.nav-collapsed .user-nav-text,
        .user-shell.nav-collapsed .user-nav-chevron,
        .user-shell.nav-collapsed .user-sidebar-name,
        .user-shell.nav-collapsed .user-sidebar-role { display: none; }
        .user-shell.nav-collapsed .user-nav-item { width: 44px; min-height: 44px; justify-content: center; margin-inline: auto; padding-inline: 8px; overflow: hidden; }
        .user-shell.nav-collapsed .user-nav-icon { width: 28px; height: 28px; border-color: transparent; background: rgba(255,255,255,.04); }
        .user-shell.nav-collapsed .user-nav-group { justify-items: center; }
        .user-shell.nav-collapsed .user-nav-submenu { display: none; }
        .user-shell.nav-collapsed .user-nav-item:hover::after,
        .user-shell.nav-collapsed .user-nav-item:focus-visible::after {
            content: attr(data-label);
            position: absolute;
            z-index: 60;
            left: calc(100% + 10px);
            top: 50%;
            display: block;
            min-width: max-content;
            transform: translateY(-50%);
            padding: 8px 10px;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 8px;
            color: #fffaf4;
            background: #3b1b18;
            box-shadow: 0 10px 22px rgba(0,0,0,.18);
            font-size: .75rem;
            font-weight: 800;
        }
        .user-shell.nav-collapsed .user-nav-item:hover { overflow: visible; }
        .user-nav-backdrop { display: none; }

        .user-topbar {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 16px 34px;
            border-bottom: 1px solid var(--wm-border);
            background: rgba(255, 253, 249, .9);
        }

        .user-topbar-inner {
            min-height: 0;
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .user-topbar h2 { margin: 0; font-family: Georgia, 'Times New Roman', serif; font-size: 1.35rem; }
        .user-topbar p { margin: 3px 0 0; color: var(--wm-muted); font-size: .82rem; }
        .user-topbar-actions { display: flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
        .user-nav-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 12px;
            border: 1px solid var(--wm-border);
            border-radius: 10px;
            color: var(--wm-text);
            background: #fff;
            cursor: pointer;
            font-size: .8rem;
            font-weight: 800;
        }
        .user-nav-toggle:hover { border-color: rgba(163, 58, 45, .35); background: #fffaf4; }
        .user-topbar-link {
            display: inline-flex;
            align-items: center;
            min-height: 38px;
            padding: 0 14px;
            border: 1px solid var(--wm-border);
            border-radius: 999px;
            color: var(--wm-accent);
            background: #fff;
            font-size: .82rem;
            font-weight: 800;
            text-decoration: none;
        }

        .page-shell {
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 34px;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .page-header {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
            padding: 30px;
            border-radius: 18px;
            color: #fffaf4;
            background: linear-gradient(125deg, #96352c, #54201b);
            box-shadow: 0 20px 50px rgba(91, 29, 29, .18);
        }
        .page-header::after {
            content: '';
            position: absolute;
            width: 240px;
            height: 240px;
            right: -68px;
            top: -100px;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 50%;
            box-shadow: 0 0 0 22px rgba(255,255,255,.04), 0 0 0 46px rgba(255,255,255,.025);
            pointer-events: none;
        }
        .page-header > * { position: relative; z-index: 1; min-width: 0; }
        .eyebrow { margin: 0 0 7px; color: #e7bf74; font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 0; font-size: 3.2rem; line-height: 1; letter-spacing: 0; }
        h2 { margin: 0; font-size: 1.25rem; }
        h3 { margin: 0; font-size: 1rem; }
        .muted { color: var(--wm-muted); }
        .page-header p:last-child { max-width: 720px; margin: 9px 0 0; color: rgba(255, 250, 244, .74); line-height: 1.6; }
        .submission-secondary { display: block; color: rgba(255, 250, 244, .68); font-size: .9em; }
        .page-header .button.secondary { border-color: rgba(255, 255, 255, .18); color: #fff5ec; background: rgba(255, 255, 255, .08); }
        .page-header .button.primary { box-shadow: none; }

        .status-banner {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid var(--wm-border);
            border-radius: 16px;
            background: rgba(255, 255, 255, .78);
        }
        .status-banner.success { border-color: rgba(41, 100, 71, .22); background: rgba(41, 100, 71, .09); color: var(--wm-success); }
        .status-banner.error { border-color: rgba(180, 35, 24, .2); background: rgba(180, 35, 24, .08); color: var(--wm-danger); }
        .status-banner.neutral { border-color: rgba(200, 148, 50, .28); background: rgba(200, 148, 50, .1); color: var(--wm-text); }
        .status-note { margin: 8px 0 0; color: var(--wm-muted); font-size: .88rem; }
        .status-banner ul { margin: 8px 0 0; padding-left: 20px; }

        .panel, .form-section, .record-card, .stat-card {
            border: 1px solid var(--wm-border);
            border-radius: 14px;
            background: var(--wm-panel);
            box-shadow: var(--wm-shadow);
        }
        .panel { padding: 22px; }
        .form-grid { display: grid; gap: 16px; }
        .form-section { display: grid; gap: 16px; padding: 20px; }
        .section-title { color: var(--wm-accent); font-family: Georgia, 'Times New Roman', serif; font-size: 1.12rem; }
        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .field-grid.three { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .field { display: grid; gap: 7px; }
        .field.full { grid-column: 1 / -1; }
        label { color: var(--wm-muted); font-size: .76rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .required::after { content: ' *'; color: var(--wm-danger); }
        input:not([type='hidden']):not([type='checkbox']):not([type='radio']), textarea, select {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            min-height: 42px;
            border: 1px solid var(--wm-border);
            border-radius: 10px;
            background: #fff;
            color: var(--wm-text);
            padding: 11px 12px;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        textarea { min-height: 112px; resize: vertical; }
        input:focus, textarea:focus, select:focus { border-color: rgba(163, 58, 45, .45); box-shadow: 0 0 0 4px rgba(163, 58, 45, .1); }
        .help-text, .field-error { margin: 0; font-size: .82rem; }
        .help-text { color: var(--wm-muted); }
        .field-error { color: var(--wm-danger); }
        .soft-card { border: 1px solid var(--wm-border); border-radius: 14px; background: var(--wm-panel); overflow: hidden; }
        .soft-card-head, .soft-card-row { display: grid; grid-template-columns: 110px minmax(0, 1fr) minmax(0, 1fr); gap: 10px; padding: 9px 14px; align-items: center; }
        .soft-card-head { background: linear-gradient(135deg, rgba(154, 43, 43, .08), rgba(199, 154, 40, .12)); color: var(--wm-muted); font-size: .72rem; font-weight: 800; text-transform: uppercase; }
        .soft-card-row { border-top: 1px solid var(--wm-border); font-size: .86rem; }
        .day-label { font-weight: 700; }
        .close-cell { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .close-cell label { display: inline-flex; align-items: center; gap: 6px; text-transform: none; letter-spacing: 0; }
        .checkbox-input { width: 17px; height: 17px; accent-color: var(--wm-accent); }
        .repeat-shell { display: grid; gap: 10px; }
        .repeat-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 2fr) auto; gap: 10px; align-items: start; }
        .repeat-actions { padding-top: 23px; }
        .repeat-row:not(:first-child) .repeat-actions { padding-top: 0; }
        .mini-button, .link-button { border-radius: 10px; cursor: pointer; font-weight: 750; }
        .mini-button { border: 1px solid var(--wm-border); background: #fff; padding: 10px 12px; color: var(--wm-danger); }
        .link-button { justify-self: start; border: 1px dashed var(--wm-accent); background: transparent; padding: 8px 14px; color: var(--wm-accent); }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 4px; }
        .button {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 0 15px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }
        .button.primary { color: #3f2a0d; background: var(--wm-highlight); box-shadow: 0 10px 22px rgba(200, 148, 50, .16); }
        .button.secondary { border-color: var(--wm-border); background: #fff; color: var(--wm-text); }
        .button.danger { border-color: rgba(180, 35, 24, .22); background: rgba(180, 35, 24, .08); color: var(--wm-danger); }
        .button.info { border-color: rgba(49, 93, 131, .2); background: rgba(49, 93, 131, .09); color: var(--wm-info); }
        .button.small { min-height: 36px; padding: 0 13px; font-size: .82rem; }
        .button:hover { transform: translateY(-1px); }
        .filters { display: grid; grid-template-columns: minmax(220px, 2fr) minmax(160px, 1fr) auto; gap: 10px; align-items: end; margin-bottom: 18px; }
        .filters.four { grid-template-columns: 1.5fr 1fr 1fr 1fr auto; }
        .record-list { display: grid; gap: 12px; }
        .record-card { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 20px; align-items: center; padding: 18px; }
        .record-card h2 { margin-top: 8px; color: var(--wm-accent); font-size: 1.2rem; }
        .record-card p { margin: 6px 0 0; color: var(--wm-muted); line-height: 1.5; }
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
        .empty-state h2 { margin-bottom: 7px; color: var(--wm-accent); }
        .empty-state p { margin: 0 0 18px; color: var(--wm-muted); }
        .detail-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr); gap: 16px; align-items: start; }
        .detail-stack { display: grid; gap: 16px; }
        .definition-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 22px; margin: 0; }
        .definition-grid > div { border-bottom: 1px solid var(--wm-border); padding-bottom: 11px; }
        .definition-grid .full { grid-column: 1 / -1; }
        dt { color: var(--wm-muted); font-size: .76rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
        dd { margin: 5px 0 0; white-space: pre-line; overflow-wrap: anywhere; }
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
        .notification { border-left: 3px solid var(--wm-highlight); border-radius: 0 10px 10px 0; padding: 11px 12px; background: rgba(255,255,255,.72); }
        .notification + .notification { margin-top: 8px; }
        .notification.unread { background: rgba(199, 154, 40, .1); }
        .notification p { margin: 4px 0 0; color: var(--wm-muted); font-size: .83rem; }
        .pagination { margin-top: 18px; }
        nav[role='navigation'] svg { width: 18px; height: 18px; }

        @media (max-width: 850px) {
            .user-shell, .user-shell.nav-collapsed { display: block; width: 100%; max-width: 100%; }
            .user-main { width: 100%; min-width: 0; overflow-x: hidden; }
            .user-sidebar {
                position: fixed;
                z-index: 40;
                left: 0;
                top: 0;
                width: min(88vw, 340px);
                height: 100dvh;
                transform: translateX(-105%);
                transition: transform .2s ease;
                box-shadow: 18px 0 45px rgba(44,18,12,.22);
            }
            .user-shell.nav-open .user-sidebar { transform: translateX(0); }
            .user-shell.nav-open .user-nav-backdrop { display: block; position: fixed; z-index: 30; inset: 0; border: 0; background: rgba(34,16,12,.42); cursor: pointer; }
            .user-shell.nav-collapsed .user-brand { justify-content: flex-start; padding-inline: 10px; }
            .user-shell.nav-collapsed .user-brand-word,
            .user-shell.nav-collapsed .user-nav-label,
            .user-shell.nav-collapsed .user-nav-text,
            .user-shell.nav-collapsed .user-nav-chevron,
            .user-shell.nav-collapsed .user-sidebar-name,
            .user-shell.nav-collapsed .user-sidebar-role { display: block; }
            .user-shell.nav-collapsed .user-nav-item { width: auto; min-height: 0; justify-content: flex-start; margin-inline: 0; padding-inline: 12px; overflow: visible; }
            .user-shell.nav-collapsed .user-nav-group { justify-items: stretch; }
            .user-shell.nav-collapsed .user-nav-submenu { display: grid; margin: 3px 0 4px 18px; padding-left: 13px; border-left: 1px solid rgba(255, 255, 255, .12); }
            .user-topbar, .page-shell { padding-inline: 20px; }
            .detail-grid, .record-card { grid-template-columns: 1fr; }
            .record-actions { justify-content: start; }
        }

        @media (max-width: 620px) {
            .user-topbar, .page-header { display: grid; grid-template-columns: minmax(0, 1fr); }
            .user-topbar { width: 100%; max-width: 100%; min-height: 0; align-items: start; justify-content: stretch; gap: 14px; overflow: hidden; }
            .user-topbar-inner { display: grid; width: 100%; max-width: 100%; grid-template-columns: auto minmax(0, 1fr); align-items: start; gap: 12px; }
            .user-topbar-inner > div, .user-topbar-actions { min-width: 0; }
            .user-topbar h2 { font-size: 1.1rem; line-height: 1.15; overflow-wrap: anywhere; }
            .user-topbar p { max-width: 100%; font-size: .76rem; line-height: 1.35; overflow-wrap: anywhere; }
            .user-topbar-actions { width: 100%; justify-content: start; }
            .user-topbar-link { max-width: 100%; min-height: 36px; white-space: normal; text-align: center; }
            .page-shell { width: 100%; padding: 22px 16px 34px; }
            .page-header { max-width: 100%; padding: 22px; border-radius: 16px; }
            .page-header > div { width: 100%; min-width: 0; max-width: 100%; }
            .page-header h1 { font-size: 2.1rem; line-height: 1.05; overflow-wrap: anywhere; }
            .page-header p:last-child { display: block; max-width: 100%; overflow-wrap: anywhere; }
            .page-header .actions { display: grid; width: 100%; min-width: 0; max-width: 100%; }
            .field-grid, .field-grid.three, .definition-grid, .filters, .filters.four { grid-template-columns: 1fr; }
            .soft-card-head { display: none; }
            .soft-card-row { grid-template-columns: 1fr; gap: 7px; padding: 12px; }
            .close-cell { align-items: stretch; flex-direction: column; }
            .repeat-row { grid-template-columns: 1fr; }
            .repeat-actions { padding-top: 0; }
            .actions .button, .record-actions .button { width: 100%; white-space: normal; text-align: center; }
            .media-grid { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); }
        }

        @media (max-width: 420px) {
            input:not([type='hidden']):not([type='checkbox']):not([type='radio']), textarea, select { font-size: .92rem; }
            .page-header h1 { font-size: 1.72rem; }
        }
    </style>
    @stack('styles')
</head>
<body class="@yield('body-class')">
    <div class="user-shell" data-user-nav>
        @include('partials.user-sidebar')
        <button class="user-nav-backdrop" id="user-nav-backdrop" type="button" aria-label="{{ __('Close navigation') }}"></button>

        <section class="user-main">
            <header class="user-topbar">
                <div class="user-topbar-inner">
                    <button class="user-nav-toggle" id="user-nav-toggle" type="button" aria-controls="user-sidebar" aria-expanded="true">
                        <span aria-hidden="true">&#9776;</span><span id="user-nav-toggle-label">{{ __('Menu') }}</span>
                    </button>
                    <div>
                        <h2>@yield('user-topbar-title', __('WarisanMakan'))</h2>
                        <p>@yield('user-topbar-subtitle', __('WarisanMakan heritage food portal'))</p>
                    </div>
                </div>
                <div class="user-topbar-actions">
                    @hasSection('user-topbar-actions')
                        @yield('user-topbar-actions')
                    @else
                        <a class="user-topbar-link" href="{{ route('home') }}">{{ __('Back to Home') }}</a>
                        <a class="user-topbar-link" href="{{ route('heritage-shops.index') }}">{{ __('Heritage Shops') }}</a>
                    @endif
                </div>
            </header>

            <main class="page-shell">
                @if (session('status'))
                    <div class="status-banner success" role="status">{{ session('status') }}</div>
                @endif

                @if (session('success'))
                    <div class="status-banner success" role="status">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="status-banner error" role="alert">
                        <strong>{{ __('Please fix the following:') }}</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </section>
    </div>

    <script>
    (() => {
        const shell = document.querySelector('[data-user-nav]');
        const toggle = document.getElementById('user-nav-toggle');
        const label = document.getElementById('user-nav-toggle-label');
        const backdrop = document.getElementById('user-nav-backdrop');
        if (!shell || !toggle || !label) return;
        const key = 'warisan-user-nav-collapsed';
        const mobile = () => window.matchMedia('(max-width: 850px)').matches;
        const sync = () => {
            if (mobile()) {
                shell.classList.remove('nav-collapsed');
                const open = shell.classList.contains('nav-open');
                toggle.setAttribute('aria-expanded', String(open));
                toggle.setAttribute('aria-label', open ? @json(__('Close navigation')) : @json(__('Open navigation')));
                label.textContent = open ? @json(__('Close')) : @json(__('Menu'));
            } else {
                shell.classList.remove('nav-open');
                const collapsed = localStorage.getItem(key) === 'true';
                shell.classList.toggle('nav-collapsed', collapsed);
                toggle.setAttribute('aria-expanded', String(!collapsed));
                toggle.setAttribute('aria-label', collapsed ? @json(__('Expand navigation')) : @json(__('Collapse navigation')));
                label.textContent = @json(__('Menu'));
            }
        };
        toggle.addEventListener('click', () => {
            if (mobile()) shell.classList.toggle('nav-open');
            else {
                const collapsed = !shell.classList.contains('nav-collapsed');
                shell.classList.toggle('nav-collapsed', collapsed);
                localStorage.setItem(key, String(collapsed));
            }
            sync();
        });
        backdrop?.addEventListener('click', () => { shell.classList.remove('nav-open'); sync(); });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') { shell.classList.remove('nav-open'); sync(); }
        });
        window.addEventListener('resize', sync, { passive: true });
        sync();
    })();
    </script>

    @stack('scripts')
</body>
</html>
