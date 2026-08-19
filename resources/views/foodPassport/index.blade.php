<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Food Passport</title>
    <style>
        :root {
            --primary: #8C1F1F;
            --primary-deep: #6D1717;
            --accent: #D4A017;
            --bg: #F7F1E7;
            --panel: #FFFDF9;
            --surface: #F2E5D0;
            --ink: #2F251F;
            --muted: #675B54;
            --line: rgba(86, 59, 48, 0.12);
            --success: #3E6C4F;
            --shadow: rgba(47, 37, 31, 0.08);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Inter, Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(140,31,31,0.06), transparent 25%),
                linear-gradient(180deg, rgba(255,255,255,.25), rgba(255,255,255,0)),
                var(--bg);
            color: var(--ink);
        }

       img { max-width: 100%; display: block; }

        .passport-shell {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 20px 40px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0 20px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-size: 0.82rem;
            color: var(--primary);
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-deep));
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 1.1rem;
            box-shadow: 0 12px 24px rgba(140,31,31,0.18);
        }

        .nav {
            display: flex;
            gap: 16px;
            align-items: center;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .nav a {
            color: var(--ink);
            text-decoration: none;
            font-weight: 600;
        }

        .nav .chip {
            background: rgba(212,160,23,0.12);
            border: 1px solid rgba(212,160,23,0.25);
            color: var(--primary);
            padding: 8px 12px;
            border-radius: 999px;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 22px;
            align-items: stretch;
            background: rgba(255,255,255,0.42);
            border: 1px solid var(--line);
            border-radius: 22px;
            box-shadow: 0 18px 38px var(--shadow);
            overflow: hidden;
            position: relative;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, rgba(140,31,31,0.05), transparent 50%);
            pointer-events: none;
        }

        .hero-copy {
            padding: 32px;
            position: relative;
            z-index: 1;
        }

        .eyebrow {
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 0.72rem;
            font-weight: 800;
            margin: 0 0 12px;
        }

        h1, h2, h3 {
            font-family: Georgia, "Times New Roman", serif;
            margin-top: 0;
            letter-spacing: -0.03em;
        }

        h1 {
            font-size: clamp(2.2rem, 5vw, 4rem);
            line-height: 0.98;
            margin-bottom: 16px;
            color: var(--primary);
        }

        .hero-copy p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            max-width: 620px;
        }

        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            padding: 12px 18px;
            border: none;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .btn:hover { transform: translateY(-1px); }

        .btn.primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-deep));
            color: #fff;
            box-shadow: 0 16px 24px rgba(140,31,31,0.18);
        }

        .btn.secondary {
            background: rgba(212,160,23,0.09);
            color: var(--ink);
            border: 1px solid rgba(212,160,23,0.24);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 28px;
        }

        .stat {
            background: rgba(255,255,255,0.52);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px 12px;
            text-align: center;
        }

        .stat strong {
            display: block;
            color: var(--primary);
            font-size: clamp(1.3rem, 2vw, 1.8rem);
            margin-bottom: 4px;
        }

        .stat span {
            color: var(--muted);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .hero-media {
            position: relative;
            min-height: 360px;
            background:
                linear-gradient(135deg, rgba(212,160,23,0.10), rgba(140,31,31,0.04)),
                repeating-linear-gradient(45deg, rgba(140,31,31,0.04), rgba(140,31,31,0.04) 10px, transparent 10px, transparent 20px),
                #f5ebdf;
            border-left: 1px solid var(--line);
        }

        .hero-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: saturate(0.9) contrast(1.04);
        }

        .floating-card {
            position: absolute;
            left: 22px;
            right: 22px;
            bottom: 18px;
            background: rgba(255,253,249,0.88);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(140,31,31,0.08);
            border-radius: 18px;
            padding: 14px 16px;
            box-shadow: 0 18px 28px rgba(47,37,31,0.08);
        }

        .floating-card .label {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 0.66rem;
            margin-bottom: 8px;
            display: block;
        }

        .floating-card h3 {
            font-size: 1.5rem;
            color: var(--ink);
            margin-bottom: 4px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 22px;
            margin-top: 26px;
        }

        .panel {
            background: rgba(255,255,255,0.46);
            border: 1px solid var(--line);
            border-radius: 20px;
            box-shadow: 0 12px 28px var(--shadow);
        }

        .panel-inner {
            padding: 22px 22px 18px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .section-header h2 {
            font-size: clamp(1.5rem, 2vw, 2.1rem);
            margin: 0;
            color: var(--ink);
        }

        .tag {
            background: rgba(140,31,31,0.08);
            border: 1px solid rgba(140,31,31,0.12);
            border-radius: 999px;
            color: var(--primary);
            padding: 7px 11px;
            font-weight: 700;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .shop-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .shop-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: rgba(255,255,255,0.54);
            cursor: pointer;
        }

        .shop-item.active {
            border-color: rgba(140,31,31,0.24);
            box-shadow: inset 0 0 0 1px rgba(140,31,31,0.08);
        }

        .shop-body {
            min-width: 0;
        }

        .shop-body h3 {
            font-size: 1.2rem;
            margin-bottom: 6px;
            color: var(--ink);
        }

        .shop-body p {
            margin: 0;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .shop-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
            font-size: 0.76rem;
            color: var(--muted);
        }

        .shop-meta span {
            background: rgba(212,160,23,0.08);
            border-radius: 999px;
            padding: 6px 8px;
        }

        .mini-action {
            background: rgba(140,31,31,0.04);
            color: var(--primary);
            border: 1px solid rgba(140,31,31,0.13);
            border-radius: 10px;
            padding: 9px 12px;
            font-weight: 700;
            font-size: 0.8rem;
        }

        .check-in-panel {
            padding: 22px;
        }

        .selected-shop {
            display: flex;
            gap: 16px;
            background: rgba(255,255,255,0.62);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 12px;
            margin: 16px 0 20px;
        }

        .selected-shop img {
            width: 96px;
            height: 96px;
            border-radius: 14px;
            object-fit: cover;
        }

        .selected-shop .label {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 0.64rem;
            margin: 0 0 6px;
        }

        .selected-shop h3 {
            font-size: 1.8rem;
            margin: 0 0 4px;
        }

        .selected-shop p {
            margin: 0;
            color: var(--muted);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        label {
            display: block;
            color: var(--muted);
            font-size: 0.82rem;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            background: #fffdf9;
            border: 1px solid rgba(86,59,48,0.12);
            border-radius: 10px;
            padding: 11px 12px;
            font: inherit;
            color: var(--ink);
        }

        .button-row {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .pagination {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 16px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            color: var(--primary);
            text-decoration: none;
        }

        .pagination .active {
            background: var(--primary);
            color: white;
        }

        .result-box {
            margin-top: 18px;
            background: #f8f2ea;
            border-radius: 12px;
            border: 1px solid rgba(86,59,48,0.1);
            color: var(--ink);
            padding: 14px;
            white-space: pre-wrap;
            word-break: break-word;
            min-height: 120px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .badge-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 12px;
            margin-top: 18px;
        }

        .badge-card {
            background: rgba(255,255,255,0.48);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 16px 14px;
            text-align: center;
        }

        .badge-card-shareable {
            cursor: pointer;
            transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
        }

        .badge-card-shareable:hover {
            transform: translateY(-3px);
            border-color: rgba(212,160,23,0.65);
            box-shadow: 0 12px 24px rgba(86,59,48,0.1);
        }

        .badge-card-shareable:focus-visible {
            outline: 3px solid rgba(212,160,23,0.5);
            outline-offset: 3px;
        }

        .badge-share-hint {
            margin-top: 10px !important;
            color: var(--primary) !important;
            font-size: 0.74rem !important;
            font-weight: 800;
        }

        .badge-crest {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            margin: 0 auto 10px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(212,160,23,0.18), rgba(140,31,31,0.06));
            color: var(--primary);
            font-size: 1.4rem;
            font-weight: 800;
        }

        .badge-card h4 {
            font-size: 1.05rem;
            margin-bottom: 6px;
        }

        .badge-card p {
            margin: 0;
            color: var(--muted);
            font-size: 0.8rem;
        }

        .leaderboard-intro {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .leaderboard-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 18px;
        }

        .leaderboard-row {
            display: grid;
            grid-template-columns: 54px minmax(0, 1fr) auto;
            align-items: center;
            gap: 14px;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(255,255,255,0.54);
        }

        .leaderboard-rank {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(212,160,23,0.14);
            color: var(--primary);
            font-weight: 800;
        }

        .leaderboard-user h3 {
            margin: 0 0 4px;
            font-size: 1.05rem;
            color: var(--ink);
        }

        .leaderboard-user p {
            margin: 0;
            color: var(--muted);
            font-size: 0.78rem;
        }

        .leaderboard-metrics {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .leaderboard-metrics span {
            padding: 7px 9px;
            border-radius: 999px;
            background: rgba(140,31,31,0.07);
            color: var(--primary);
            font-size: 0.76rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .leaderboard-empty {
            margin: 18px 0 0;
            color: var(--muted);
        }

        body.modal-open {
            overflow: hidden;
        }

        .badge-modal[hidden] {
            display: none;
        }

        .badge-modal {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: grid;
            place-items: center;
            padding: 20px;
        }

        .badge-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(47, 37, 31, 0.56);
            backdrop-filter: blur(4px);
        }

        .badge-modal-card {
            position: relative;
            width: min(100%, 480px);
            max-height: min(680px, calc(100vh - 40px));
            overflow: auto;
            padding: 30px 26px 24px;
            border-radius: 24px;
            background: var(--panel);
            border: 1px solid rgba(212,160,23,0.3);
            box-shadow: 0 28px 70px rgba(47,37,31,0.25);
            text-align: center;
        }

        .badge-modal-close {
            position: absolute;
            top: 12px;
            right: 14px;
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 50%;
            background: rgba(140,31,31,0.08);
            color: var(--primary);
            font-size: 1.35rem;
            cursor: pointer;
        }

        .badge-modal-icon {
            display: grid;
            place-items: center;
            width: 76px;
            height: 76px;
            margin: 4px auto 14px;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(212,160,23,0.24), rgba(140,31,31,0.1));
            color: var(--primary);
            font-size: 2rem;
            font-weight: 800;
        }

        .badge-modal-card h2 {
            margin-bottom: 8px;
            color: var(--primary);
            font-size: 2rem;
        }

        .badge-modal-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .badge-modal-badge-name {
            margin: 12px 0 6px !important;
            color: var(--ink) !important;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1.45rem;
            font-weight: 700;
        }

        .share-label {
            margin-top: 22px !important;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .share-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .share-btn {
            padding: 11px 12px;
            border: 1px solid rgba(140,31,31,0.14);
            border-radius: 11px;
            background: rgba(140,31,31,0.05);
            color: var(--primary);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .share-btn:hover {
            background: rgba(212,160,23,0.15);
        }

        .share-status {
            min-height: 24px;
            margin-top: 12px !important;
            font-size: 0.82rem;
        }

        .badge-modal-continue {
            width: 100%;
            margin-top: 16px;
        }

        footer {
            text-align: center;
            color: var(--muted);
            padding: 28px 0 10px;
            font-size: 0.88rem;
        }

        @media (max-width: 900px) {
            .hero, .content-grid {
                grid-template-columns: 1fr;
            }

            .hero-media {
                min-height: 260px;
            }
        }

        @media (max-width: 640px) {
            .topbar {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }

            .hero-copy {
                padding: 24px 18px 20px;
            }

            .stats-row, .badge-grid, .form-row {
                grid-template-columns: 1fr;
            }

            .leaderboard-row {
                grid-template-columns: 46px minmax(0, 1fr);
            }

            .leaderboard-metrics {
                grid-column: 2;
                justify-content: flex-start;
            }

            .shop-item {
                grid-template-columns: 1fr;
            }

            .mini-action {
                grid-column: 1 / -1;
                justify-self: start;
            }
        }
    </style>
</head>
<body>
    <div class="passport-shell">
        <header class="topbar">
            <div class="brand">
                <span class="brand-mark">W</span>
                <span>Warisan Makan</span>
            </div>
            <nav class="nav" aria-label="Main navigation">
                <a href="#">Map</a>
                <a href="#">Passport</a>
                <a href="#">Rewards</a>
                <a href="#leaderboard">Leaderboard</a>
                <span class="chip">Heritage Trail</span>
            </nav>
        </header>

        <main>
            <section class="hero" aria-label="Heritage passport hero section">
                <div class="hero-copy">
                    <p class="eyebrow">Food Passport</p>
                    <h1>Your Heritage Passport</h1>
                    <p>Collect stamps from authentic heritage food stops, uncover founder stories, and unlock rewards as you explore the city’s living culinary heritage.</p>

                    <div class="action-row">
                        <a href="#check-in" class="btn primary">Check In</a>
                        <a href="#nearby" class="btn secondary">Nearby Stops</a>
                        <a href="#leaderboard" class="btn secondary">Leaderboard</a>
                    </div>

                    <div class="stats-row" aria-label="Passport progress statistics">
                        <div class="stat">
                            <strong>{{ $stats['visited'] ?? 0 }}</strong>
                            <span>Visited</span>
                        </div>
                        <div class="stat">
                            <strong>{{ $stats['completion'] ?? 0 }}%</strong>
                            <span>Progress</span>
                        </div>
                        <div class="stat">
                            <strong>{{ $stats['badges'] ?? 0 }}</strong>
                            <span>Badges</span>
                        </div>
                    </div>
                </div>

                @if (!empty($shops))
                    <div class="hero-media" aria-label="Featured heritage shop image">
                            @if ($shops[0]['image'])
                            <img id="featuredShopImage" src="{{ $shops[0]['image'] }}" alt="{{ $shops[0]['name'] }}">
                        @endif
                        <div class="floating-card">
                            <span class="label">Featured stop</span>
                            <h3 id="featuredShopName">{{ $shops[0]['name'] }}</h3>
                            <p id="featuredShopFounder">{{ $shops[0]['founder'] }}</p>
                        </div>
                    </div>
                @endif
            </section>

            <section class="content-grid" id="nearby">
                <div class="panel">
                    <div class="panel-inner">
                        <div class="section-header">
                            <h2>Nearby heritage stop</h2>
                            <span class="tag">Live</span>
                        </div>

                        @if (!empty($shops))
                            <div class="shop-list" id="shopList">
                                @foreach ($shops as $shop)
                                    <article class="shop-item {{ $loop->first ? 'active' : '' }}" data-id="{{ $shop['id'] }}" data-name="{{ $shop['name'] }}" data-founder="{{ $shop['founder'] }}" data-lat="{{ $shop['lat'] }}" data-lng="{{ $shop['lng'] }}" data-image="{{ $shop['image'] }}">
                                        <div class="shop-body">
                                            <h3>{{ $shop['name'] }}</h3>
                                            <p>{{ $shop['founder'] }}</p>
                                            <div class="shop-meta">
                                                <span>{{ $shop['distance'] }}</span>
                                                <span>{{ $shop['status'] }}</span>
                                            </div>
                                        </div>
                                        <button type="button" class="mini-action select-shop">Check In</button>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <p style="margin: 0; color: var(--muted);">No approved heritage shops with GPS coordinates are available for check-in yet. Add the shop location and approve it in the Heritage Shop module.</p>
                        @endif
                    </div>
                </div>

                <aside class="panel check-in-panel" id="check-in">
                    <div class="section-header">
                        <h2>Check In</h2>
                        <span class="tag">GPS</span>
                    </div>

                    @if (!empty($shops))
                        <div class="selected-shop">
                            <div>
                                <p class="label">Selected stop</p>
                                <h3 id="selectedShopName">{{ $shops[0]['name'] }}</h3>
                                <p id="selectedShopFounder">{{ $shops[0]['founder'] }}</p>
                            </div>
                        </div>

                        <div class="button-row">
                            <button type="button" id="btnCheckIn" class="btn primary">Use my location</button>
                            <button type="button" id="btnDemoCheckIn" class="btn secondary">Use demo location</button>
                            <button type="button" id="btnRefresh" class="btn secondary">Reset demo</button>
                        </div>

                        <pre id="result" class="result-box">Ready to check in. Select a shop and allow location access.</pre>
                    @else
                        <p style="margin: 16px 0 0; color: var(--muted);">Check-in will be available after an approved Heritage Shop has latitude and longitude coordinates.</p>
                    @endif
                </aside>
            </section>

            <section class="panel" style="margin-top: 26px;">
                <div class="panel-inner">
                    <div class="section-header">
                        <h2>Visited locations</h2>
                        <span class="tag">History</span>
                    </div>

                    @if ($visitedLocations->isNotEmpty())
                        <div class="shop-list">
                            @foreach ($visitedLocations as $location)
                                <article class="shop-item active" data-visited-location>
                                    <div class="shop-body">
                                        <h3>{{ $location['shop_name'] }}</h3>
                                        <p>{{ $location['founder'] }}</p>
                                        <div class="shop-meta">
                                            <span>{{ $location['stamped_at'] ?? 'Checked in' }}</span>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        @if ($visitedLocations->total() > 0)
                            <nav class="pagination" aria-label="Visited locations pages">
                                @if ($visitedLocations->onFirstPage())
                                    <span aria-disabled="true">Previous</span>
                                @else
                                    <a href="{{ $visitedLocations->previousPageUrl() }}">Previous</a>
                                @endif
                                <span class="active">Page {{ $visitedLocations->currentPage() }}</span>
                                @if ($visitedLocations->hasMorePages())
                                    <a href="{{ $visitedLocations->nextPageUrl() }}">Next</a>
                                @else
                                    <span aria-disabled="true">Next</span>
                                @endif
                            </nav>
                        @endif
                    @else
                        <p style="margin: 0; color: var(--muted);">No visited heritage locations yet. Complete a check-in to start building your food passport.</p>
                    @endif
                </div>
            </section>

            <section class="panel" style="margin-top: 26px;">
                <div class="panel-inner">
                    <div class="section-header">
                        <h2>Passport progress & statistics</h2>
                        <span class="tag">Live</span>
                    </div>

                    <div class="stats-row" style="margin-top: 0;">
                        <div class="stat">
                            <strong>{{ $stats['visited'] ?? 0 }}</strong>
                            <span>Visited</span>
                        </div>
                        <div class="stat">
                            <strong>{{ $stats['completion'] ?? 0 }}%</strong>
                            <span>Completion</span>
                        </div>
                        <div class="stat">
                            <strong>{{ $stats['stamps'] ?? 0 }}</strong>
                            <span>Stamps</span>
                        </div>
                    </div>

                    <div style="margin-top: 16px; padding: 14px 16px; border-radius: 14px; background: rgba(140,31,31,0.04); border: 1px solid rgba(140,31,31,0.08);">
                        <div style="display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 8px; color: var(--muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em;">
                            <span>Progress</span>
                            <strong style="color: var(--primary);">{{ $stats['visited'] ?? 0 }}/{{ $stats['goal'] ?? 0 }}</strong>
                        </div>
                        <div style="height: 12px; background: rgba(86,59,48,0.08); border-radius: 999px; overflow: hidden;">
                            <div style="width: {{ $stats['completion'] ?? 0 }}%; height: 100%; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 999px;"></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="panel" style="margin-top: 26px;">
                <div class="panel-inner">
                    <div class="section-header">
                        <h2>Passport rewards</h2>
                        <span class="tag">Unlocked</span>
                    </div>

                    <div class="badge-grid">
                        @foreach ($badges as $badge)
                            <div
                                class="badge-card {{ $badge['earned'] ? 'badge-card-shareable' : '' }}"
                                @if ($badge['earned'])
                                    role="button"
                                    tabindex="0"
                                    data-badge-share
                                    data-badge-name="{{ $badge['name'] }}"
                                    data-badge-description="{{ $badge['description'] }}"
                                    data-badge-icon="{{ $badge['icon'] }}"
                                    aria-label="Share your {{ $badge['name'] }} badge"
                                @endif
                            >
                                <div class="badge-crest">{{ $badge['icon'] }}</div>
                                <h4>{{ $badge['name'] }}</h4>
                                <p>{{ $badge['description'] }}</p>
                                <p style="margin-top: 8px; color: {{ $badge['earned'] ? '#3E6C4F' : '#675B54' }}; font-weight: 700;">
                                    {{ $badge['earned'] ? 'Unlocked' : ($badge['eligible'] ? 'Ready' : 'Need ' . $badge['threshold'] . ' visits') }}
                                </p>
                                @if ($badge['earned'])
                                    <p class="badge-share-hint">Click to share</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="panel" id="leaderboard" style="margin-top: 26px;">
                <div class="panel-inner">
                    <div class="section-header">
                        <h2>Leaderboard</h2>
                        <span class="tag">Top 10</span>
                    </div>
                    <p class="leaderboard-intro">Ranked by badges received, then total check-ins. Recent check-ins decide ties.</p>

                    @if ($leaderboard->isNotEmpty())
                        <div class="leaderboard-list">
                            @foreach ($leaderboard as $entry)
                                <article class="leaderboard-row">
                                    <div class="leaderboard-rank">#{{ $entry->rank }}</div>
                                    <div class="leaderboard-user">
                                        <h3>{{ $entry->name ?: 'Heritage Explorer' }}</h3>
                                        <p>Last check-in: {{ $entry->last_check_in_label }}</p>
                                    </div>
                                    <div class="leaderboard-metrics">
                                        <span>{{ $entry->badges_received }} badges</span>
                                        <span>{{ $entry->check_ins }} check-ins</span>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @if ($leaderboard->total() > 0)
                            <nav class="pagination" aria-label="Leaderboard pages">
                                @if ($leaderboard->onFirstPage())
                                    <span aria-disabled="true">Previous</span>
                                @else
                                    <a href="{{ $leaderboard->previousPageUrl() }}#leaderboard">Previous</a>
                                @endif
                                <span class="active">Page {{ $leaderboard->currentPage() }}</span>
                                @if ($leaderboard->hasMorePages())
                                    <a href="{{ $leaderboard->nextPageUrl() }}#leaderboard">Next</a>
                                @else
                                    <span aria-disabled="true">Next</span>
                                @endif
                            </nav>
                        @endif
                    @else
                        <p class="leaderboard-empty">The leaderboard will appear after users start checking in and earning badges.</p>
                    @endif
                </div>
            </section>
        </main>

        <footer>
            Discover heritage flavour. Preserve the stories behind every bowl.
        </footer>
    </div>

    <div id="badgeModal" class="badge-modal" hidden role="dialog" aria-modal="true" aria-labelledby="badgeModalTitle">
        <div class="badge-modal-backdrop" data-close-badge-modal></div>
        <div class="badge-modal-card">
            <button type="button" class="badge-modal-close" data-close-badge-modal aria-label="Close badge announcement">&times;</button>
            <div id="badgeModalIcon" class="badge-modal-icon">★</div>
            <h2 id="badgeModalTitle">Congratulations!</h2>
            <p>You have unlocked a new Heritage Passport badge.</p>
            <p id="badgeModalBadgeName" class="badge-modal-badge-name">Heritage Explorer</p>
            <p id="badgeModalBadgeDescription">Keep exploring and sharing the stories behind local food.</p>
            <p class="share-label">Would you like to share your achievement?</p>
            <div class="share-actions">
                <button type="button" class="share-btn" data-share="instagram">Instagram</button>
                <button type="button" class="share-btn" data-share="facebook">Facebook</button>
                <button type="button" class="share-btn" data-share="whatsapp">WhatsApp</button>
                <button type="button" class="share-btn" data-share="copy">Copy message</button>
            </div>
            <p id="shareStatus" class="share-status" aria-live="polite"></p>
            <button type="button" class="btn primary badge-modal-continue" data-close-badge-modal>Continue exploring</button>
        </div>
    </div>

    <script>
        const shops = @json($shops);
        let activeShop = shops[0] || null;

        function setActiveShop(shop) {
            activeShop = shop;

            document.getElementById('selectedShopName').textContent = shop.name;
            document.getElementById('selectedShopFounder').textContent = shop.founder;

            const featuredShopName = document.getElementById('featuredShopName');
            const featuredShopFounder = document.getElementById('featuredShopFounder');
            const featuredShopImage = document.getElementById('featuredShopImage');

            if (featuredShopName) featuredShopName.textContent = shop.name;
            if (featuredShopFounder) featuredShopFounder.textContent = shop.founder;
            if (featuredShopImage && shop.image) {
                featuredShopImage.src = shop.image;
                featuredShopImage.alt = shop.name;
            }

            document.querySelectorAll('.shop-item').forEach(item => {
                item.classList.toggle('active', Number(item.dataset.id) === Number(shop.id));
            });
        }

        document.querySelectorAll('.select-shop').forEach(button => {
            button.addEventListener('click', () => {
                const article = button.closest('.shop-item');
                const shop = shops.find(item => Number(item.id) === Number(article.dataset.id));
                setActiveShop(shop);
            });
        });

        const out = document.getElementById('result');
        const badgeModal = document.getElementById('badgeModal');
        const badgeModalIcon = document.getElementById('badgeModalIcon');
        const badgeModalBadgeName = document.getElementById('badgeModalBadgeName');
        const badgeModalBadgeDescription = document.getElementById('badgeModalBadgeDescription');
        const shareStatus = document.getElementById('shareStatus');
        let badgeShareText = '';
        let reloadAfterBadgeModal = false;

        function setResult(value) {
            if (typeof value === 'string') {
                out.textContent = value;
                return;
            }

            out.textContent = value.message || 'We could not complete the request. Please try again.';
        }

        function openBadgeModal(unlockedBadges, refreshOnClose = false) {
            const primaryBadge = unlockedBadges[0];
            const badgeNames = unlockedBadges.map(badge => badge.name).join(', ');
            const additionalBadges = unlockedBadges.length > 1
                ? ' Also unlocked: ' + unlockedBadges.slice(1).map(badge => badge.name).join(', ') + '.'
                : '';

            badgeModalIcon.textContent = primaryBadge.icon || '★';
            badgeModalBadgeName.textContent = primaryBadge.name;
            badgeModalBadgeDescription.textContent = (primaryBadge.description || 'Keep exploring local food heritage.') + additionalBadges;
            badgeShareText = 'I just unlocked the ' + badgeNames + ' badge' + (unlockedBadges.length > 1 ? 's' : '') + ' on the Warisan Makan Heritage Passport!';
            shareStatus.textContent = '';
            badgeModal.hidden = false;
            document.body.classList.add('modal-open');
            reloadAfterBadgeModal = refreshOnClose;
        }

        function closeBadgeModal() {
            badgeModal.hidden = true;
            document.body.classList.remove('modal-open');

            if (reloadAfterBadgeModal) {
                reloadAfterBadgeModal = false;
                window.setTimeout(() => window.location.reload(), 250);
            }
        }

        async function copyShareText() {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(badgeShareText);
                return true;
            }

            const helper = document.createElement('textarea');
            helper.value = badgeShareText;
            helper.setAttribute('readonly', '');
            helper.style.position = 'fixed';
            helper.style.opacity = '0';
            document.body.appendChild(helper);
            helper.select();
            const copied = document.execCommand('copy');
            helper.remove();
            return copied;
        }

        async function shareBadge(channel) {
            try {
                if (channel === 'copy') {
                    await copyShareText();
                    shareStatus.textContent = 'Badge message copied. You can paste it anywhere.';
                    return;
                }

                if (channel === 'instagram') {
                    await copyShareText();
                    window.open('https://www.instagram.com/', '_blank', 'noopener');
                    shareStatus.textContent = 'Message copied. Paste it into your Instagram post or story.';
                    return;
                }

                if (channel === 'facebook') {
                    const url = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href) + '&quote=' + encodeURIComponent(badgeShareText);
                    window.open(url, '_blank', 'noopener');
                    shareStatus.textContent = 'Facebook sharing opened in a new tab.';
                    return;
                }

                if (channel === 'whatsapp') {
                    const url = 'https://wa.me/?text=' + encodeURIComponent(badgeShareText + ' ' + window.location.href);
                    window.open(url, '_blank', 'noopener');
                    shareStatus.textContent = 'WhatsApp sharing opened in a new tab.';
                }
            } catch (error) {
                shareStatus.textContent = 'We could not prepare the share message. Please copy it manually.';
            }
        }

        document.querySelectorAll('[data-close-badge-modal]').forEach(element => {
            element.addEventListener('click', closeBadgeModal);
        });

        document.querySelectorAll('[data-share]').forEach(button => {
            button.addEventListener('click', () => shareBadge(button.dataset.share));
        });

        document.querySelectorAll('[data-badge-share]').forEach(badgeCard => {
            const shareSelectedBadge = () => {
                openBadgeModal([{
                    name: badgeCard.dataset.badgeName,
                    description: badgeCard.dataset.badgeDescription,
                    icon: badgeCard.dataset.badgeIcon
                }], false);
            };

            badgeCard.addEventListener('click', shareSelectedBadge);
            badgeCard.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    shareSelectedBadge();
                }
            });
        });

        function submitCheckIn(payload) {
            setResult('Sending check-in request...');

            const endpoint = '/passport/check-in';

            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            }).then(async response => {
                const json = await response.json();
                setResult(json);

                if (json && json.success) {
                    const newlyUnlockedBadges = Array.isArray(json.newly_unlocked_badges)
                        ? json.newly_unlocked_badges
                        : [];

                    if (newlyUnlockedBadges.length > 0) {
                        openBadgeModal(newlyUnlockedBadges, true);
                    } else {
                        window.setTimeout(() => window.location.reload(), 1200);
                    }
                }
            }).catch(error => {
                setResult('Network or server error: ' + error.message);
            });
        }

        document.getElementById('btnRefresh')?.addEventListener('click', async () => {
            if (!window.confirm('Reset the demo passport and start again?')) {
                return;
            }

            setResult('Resetting the demo passport...');

            try {
                const response = await fetch('/passport/reset-demo', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });
                const json = await response.json();
                setResult(json);

                if (response.ok && json.success) {
                    window.setTimeout(() => window.location.reload(), 700);
                }
            } catch (error) {
                setResult('We could not reset the demo right now. Please try again.');
            }
        });

        document.getElementById('btnDemoCheckIn')?.addEventListener('click', function () {
            if (!activeShop) {
                setResult('Please select a heritage shop first.');
                return;
            }

            setResult('Using a demo location near the selected shop...');
            submitCheckIn({
                shop_id: Number(activeShop.id),
                user_latitude: Number((Number(activeShop.lat) + 0.0002).toFixed(6)),
                user_longitude: Number((Number(activeShop.lng) + 0.0002).toFixed(6)),
                demo_mode: true
            });
        });

        document.getElementById('btnCheckIn')?.addEventListener('click', function () {
            if (!activeShop) {
                setResult('Please select a heritage shop first.');
                return;
            }
            if (!navigator.geolocation) {
                setResult('Geolocation is not supported by this browser.');
                return;
            }

            setResult('Requesting location for your heritage check-in...');

            navigator.geolocation.getCurrentPosition(function (position) {
                const payload = {
                    shop_id: Number(activeShop.id),
                    user_latitude: position.coords.latitude,
                    user_longitude: position.coords.longitude
                };

                submitCheckIn(payload);
            }, function (error) {
                setResult('Failed to get location: ' + (error.message || error.code));
            }, { enableHighAccuracy: true, timeout: 10000 });
        });
    </script>
</body>
</html>
