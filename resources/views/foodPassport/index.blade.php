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
            grid-template-columns: 108px 1fr auto;
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

        .shop-item img {
            width: 108px;
            height: 88px;
            object-fit: cover;
            border-radius: 14px;
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
            font-family: "SFMono-Regular", Consolas, monospace;
            font-size: 0.82rem;
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

            .shop-item {
                grid-template-columns: 88px 1fr;
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

                <div class="hero-media" aria-label="Featured heritage shop image">
                    <img src="{{ $shops[0]['image'] }}" alt="{{ $shops[0]['name'] }}">
                    <div class="floating-card">
                        <span class="label">Featured stop</span>
                        <h3>{{ $shops[0]['name'] }}</h3>
                        <p>{{ $shops[0]['founder'] }}</p>
                    </div>
                </div>
            </section>

            <section class="content-grid" id="nearby">
                <div class="panel">
                    <div class="panel-inner">
                        <div class="section-header">
                            <h2>Nearby heritage stop</h2>
                            <span class="tag">Live</span>
                        </div>

                        <div class="shop-list" id="shopList">
                            @foreach ($shops as $shop)
                                <article class="shop-item {{ $loop->first ? 'active' : '' }}" data-id="{{ $shop['id'] }}" data-name="{{ $shop['name'] }}" data-founder="{{ $shop['founder'] }}" data-lat="{{ $shop['lat'] }}" data-lng="{{ $shop['lng'] }}" data-image="{{ $shop['image'] }}">
                                    <img src="{{ $shop['image'] }}" alt="{{ $shop['name'] }}">
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
                    </div>
                </div>

                <aside class="panel check-in-panel" id="check-in">
                    <div class="section-header">
                        <h2>Check In</h2>
                        <span class="tag">GPS</span>
                    </div>

                    <div class="selected-shop">
                        <img id="selectedShopImage" src="{{ $shops[0]['image'] }}" alt="Selected heritage shop">
                        <div>
                            <p class="label">Selected stop</p>
                            <h3 id="selectedShopName">{{ $shops[0]['name'] }}</h3>
                            <p id="selectedShopFounder">{{ $shops[0]['founder'] }}</p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label for="radius">Radius (m)</label>
                            <input id="radius" type="number" value="100" min="10" step="10">
                        </div>
                        <div>
                            <label for="shopId">Shop ID</label>
                            <input id="shopId" type="number" value="{{ $shops[0]['id'] }}" readonly>
                        </div>
                    </div>

                    <div class="button-row">
                        <button type="button" id="btnCheckIn" class="btn primary">Use my location</button>
                        <button type="button" id="btnDemoCheckIn" class="btn secondary">Use demo location</button>
                        <button type="button" id="btnResetDemo" class="btn secondary">Reset demo</button>
                        <button type="button" id="btnRefresh" class="btn secondary">Refresh</button>
                    </div>

                    <pre id="result" class="result-box">Ready to check in. Select a shop and allow location access.</pre>
                </aside>
            </section>

            <section class="panel" style="margin-top: 26px;">
                <div class="panel-inner">
                    <div class="section-header">
                        <h2>Visited locations</h2>
                        <span class="tag">History</span>
                    </div>

                    @if (!empty($visitedLocations))
                        <div class="shop-list">
                            @foreach ($visitedLocations as $location)
                                <article class="shop-item active" style="grid-template-columns: 90px 1fr;">
                                    <img src="{{ $location['image'] ?? asset('images/shop1.jpg') }}" alt="{{ $location['shop_name'] }}">
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
                            <div class="badge-card">
                                <div class="badge-crest">{{ $badge['icon'] }}</div>
                                <h4>{{ $badge['name'] }}</h4>
                                <p>{{ $badge['description'] }}</p>
                                <p style="margin-top: 8px; color: {{ $badge['earned'] ? '#3E6C4F' : '#675B54' }}; font-weight: 700;">
                                    {{ $badge['earned'] ? 'Unlocked' : ($badge['eligible'] ? 'Ready' : 'Need ' . $badge['threshold'] . ' visits') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </main>

        <footer>
            Discover heritage flavour. Preserve the stories behind every bowl.
        </footer>
    </div>

    <script>
        const shops = @json($shops);
        let activeShop = shops[0];

        function setActiveShop(shop) {
            activeShop = shop;
            document.getElementById('selectedShopName').textContent = shop.name;
            document.getElementById('selectedShopFounder').textContent = shop.founder;
            document.getElementById('selectedShopImage').src = shop.image;
            document.getElementById('shopId').value = shop.id;

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

        function setResult(value) {
            out.textContent = typeof value === 'string' ? value : JSON.stringify(value, null, 2);
        }

        function submitCheckIn(payload) {
            setResult('Sending check-in request...');

            const endpoint = payload.demo_mode ? '/passport/check-in-test' : '/passport/check-in';

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
                    window.location.reload();
                }
            }).catch(error => {
                setResult('Network or server error: ' + error.message);
            });
        }

        function resetDemoPassport() {
            setResult('Resetting demo passport...');

            fetch('/passport/demo-reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ demo_mode: true })
            }).then(async response => {
                const json = await response.json();
                setResult(json);
                window.location.reload();
            }).catch(error => {
                setResult('Demo reset failed: ' + error.message);
            });
        }

        document.getElementById('btnRefresh').addEventListener('click', () => {
            setResult('Ready to check in. Select a shop and allow location access.');
        });

        document.getElementById('btnResetDemo').addEventListener('click', resetDemoPassport);

        document.getElementById('btnDemoCheckIn').addEventListener('click', function () {
            const payload = {
                shop_id: Number(activeShop.id),
                shop_latitude: Number(activeShop.lat),
                shop_longitude: Number(activeShop.lng),
                user_latitude: Number((Number(activeShop.lat) + 0.00025).toFixed(6)),
                user_longitude: Number((Number(activeShop.lng) + 0.00025).toFixed(6)),
                radius_meters: Number(document.getElementById('radius').value || 100),
                is_participating: true,
                is_published: true,
                demo_mode: true,
                allow_repeat: true
            };

            submitCheckIn(payload);
        });

        document.getElementById('btnCheckIn').addEventListener('click', function () {
            if (!navigator.geolocation) {
                setResult('Geolocation is not supported by this browser. Using demo mode instead.');
                document.getElementById('btnDemoCheckIn').click();
                return;
            }

            setResult('Requesting location for your heritage check-in...');

            navigator.geolocation.getCurrentPosition(function (position) {
                const payload = {
                    shop_id: Number(activeShop.id),
                    shop_latitude: Number(activeShop.lat),
                    shop_longitude: Number(activeShop.lng),
                    user_latitude: position.coords.latitude,
                    user_longitude: position.coords.longitude,
                    radius_meters: Number(document.getElementById('radius').value || 100),
                    is_participating: true,
                    is_published: true,
                    demo_mode: false
                };

                submitCheckIn(payload);
            }, function (error) {
                setResult('Failed to get location: ' + (error.message || error.code) + '. Using demo coordinates instead.');
                document.getElementById('btnDemoCheckIn').click();
            }, { enableHighAccuracy: true, timeout: 10000 });
        });
    </script>
</body>
</html>
