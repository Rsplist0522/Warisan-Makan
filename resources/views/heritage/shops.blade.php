<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($shop) ? $shop->shop_name.' - Heritage Shop' : 'Heritage Shops' }} - Warisan Makan</title>
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
        button, input, select { font: inherit; }
        h1, h2, h3, p { overflow-wrap: anywhere; }
        h1, h2, h3 { font-family: Georgia, 'Times New Roman', serif; }
        .shell { min-height: 100vh; display: grid; grid-template-columns: 268px minmax(0, 1fr); }
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
        .brand-mark { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 12px; color: #3b1b16; background: var(--wm-gold); }
        .nav-label { margin: 27px 12px 10px; color: rgba(255, 245, 236, .48); font-size: .68rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
        .nav { display: grid; gap: 5px; }
        .nav-item { display: flex; align-items: center; gap: 11px; padding: 11px 12px; border-radius: 10px; color: rgba(255, 245, 236, .72); font-size: .88rem; font-weight: 700; text-decoration: none; }
        .nav-item::before { content: ''; width: 7px; height: 7px; flex: 0 0 auto; border: 1px solid currentColor; border-radius: 50%; }
        .nav-item.active, .nav-item:hover { color: #fff; background: var(--wm-sidebar-soft); }
        .nav-item.active::before { border-color: var(--wm-gold); background: var(--wm-gold); }
        .sidebar-footer { margin-top: auto; padding-top: 22px; border-top: 1px solid rgba(255, 255, 255, .1); }
        .user-name { margin: 0 0 3px; font-size: .88rem; font-weight: 800; }
        .user-role { margin: 0 0 14px; color: rgba(255, 245, 236, .52); font-size: .76rem; }
        .logout { width: 100%; padding: 9px 12px; border: 1px solid rgba(255, 255, 255, .16); border-radius: 9px; color: #fff5ec; background: transparent; cursor: pointer; text-align: left; }
        .logout:hover { background: rgba(255, 255, 255, .08); }
        .main { min-width: 0; background: linear-gradient(135deg, rgba(163, 58, 45, .06), transparent 34%), linear-gradient(315deg, rgba(61, 111, 85, .07), transparent 38%), var(--wm-bg); }
        .topbar { min-height: 76px; display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 16px 34px; border-bottom: 1px solid var(--wm-line); background: rgba(255, 253, 249, .9); }
        .topbar h2 { margin: 0; font-family: Georgia, 'Times New Roman', serif; font-size: 1.35rem; }
        .topbar p { margin: 3px 0 0; color: var(--wm-muted); font-size: .82rem; }
        .topbar-link { display: inline-flex; align-items: center; min-height: 38px; padding: 0 14px; border: 1px solid var(--wm-line); border-radius: 999px; color: var(--wm-accent); background: #fff; font-size: .82rem; font-weight: 800; text-decoration: none; }
        .content { width: min(1180px, 100%); margin: 0 auto; padding: 34px; }
        .page-header { position: relative; overflow: hidden; display: flex; justify-content: space-between; align-items: end; gap: 20px; margin-bottom: 24px; padding: 30px; border-radius: 18px; color: #fffaf4; background: linear-gradient(125deg, #96352c, #54201b); box-shadow: 0 20px 50px rgba(91, 29, 29, .18); }
        .page-header::after { content: ''; position: absolute; width: 240px; height: 240px; right: -68px; top: -100px; border: 1px solid rgba(255,255,255,.16); border-radius: 50%; box-shadow: 0 0 0 22px rgba(255,255,255,.04), 0 0 0 46px rgba(255,255,255,.025); pointer-events: none; }
        .page-header > * { position: relative; z-index: 1; }
        .eyebrow { margin: 0 0 8px; color: #e7bf74; font-size: .72rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
        .page-header h1 { margin: 0; font-size: clamp(2rem, 5vw, 3.2rem); line-height: 1; }
        .page-header p:last-child { max-width: 720px; margin: 11px 0 0; color: rgba(255, 250, 244, .74); line-height: 1.6; }
        .header-pill { display: inline-flex; align-items: center; min-height: 38px; padding: 0 14px; border: 1px solid rgba(255, 255, 255, .18); border-radius: 999px; color: #fff5ec; background: rgba(255, 255, 255, .08); font-size: .8rem; font-weight: 800; white-space: nowrap; }
        .filter-panel, .shop-card, .detail-panel, .empty-state { border: 1px solid var(--wm-line); border-radius: 14px; background: var(--wm-panel); box-shadow: 0 12px 32px rgba(77, 48, 34, .07); }
        .filter-panel { margin-bottom: 22px; padding: 20px; }
        .filter-grid { display: grid; grid-template-columns: minmax(220px, 2fr) repeat(3, minmax(140px, 1fr)) auto; gap: 10px; align-items: end; }
        .field { display: grid; gap: 7px; }
        .field label { color: var(--wm-muted); font-size: .76rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .field input, .field select { width: 100%; min-height: 42px; padding: 0 12px; border: 1px solid var(--wm-line); border-radius: 10px; color: var(--wm-ink); background: #fff; outline: none; }
        .field input:focus, .field select:focus { border-color: rgba(163, 58, 45, .45); box-shadow: 0 0 0 4px rgba(163, 58, 45, .1); }
        .button { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid transparent; border-radius: 10px; padding: 0 15px; font-weight: 800; text-decoration: none; cursor: pointer; }
        .button.primary { color: #3f2a0d; background: var(--wm-gold); }
        .button.secondary { border-color: var(--wm-line); color: var(--wm-ink); background: #fff; }
        .filter-actions { display: flex; gap: 8px; align-items: end; }
        .result-summary { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 0 0 14px; color: var(--wm-muted); font-size: .86rem; }
        .shop-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .shop-card { overflow: hidden; display: flex; flex-direction: column; min-width: 0; transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
        .shop-card:hover { transform: translateY(-4px); border-color: rgba(163, 58, 45, .25); box-shadow: 0 20px 42px rgba(77, 48, 34, .12); }
        .image-frame { position: relative; min-height: 190px; background: #f1e5d7; }
        .image-frame img { display: block; width: 100%; height: 190px; object-fit: cover; }
        .image-placeholder { min-height: 190px; display: grid; place-items: center; padding: 20px; color: var(--wm-muted); background: linear-gradient(135deg, #efe0cf, #fff8ef); font-size: .82rem; font-weight: 800; text-align: center; }
        .shop-card-body { display: flex; flex: 1; flex-direction: column; padding: 20px; }
        .shop-card h2 { margin: 0 0 7px; color: var(--wm-accent); font-size: 1.18rem; }
        .meta { margin: 0 0 10px; color: var(--wm-gold); font-size: .72rem; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
        .description { margin: 0; color: var(--wm-muted); font-size: .88rem; line-height: 1.55; }
        .card-facts { display: grid; gap: 7px; margin: 16px 0; padding-top: 14px; border-top: 1px solid var(--wm-line); color: var(--wm-muted); font-size: .82rem; }
        .card-facts strong { color: var(--wm-ink); }
        .card-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: auto; }
        .status { display: inline-flex; width: fit-content; align-items: center; border-radius: 999px; padding: 5px 9px; color: var(--wm-green); background: rgba(61, 111, 85, .11); font-size: .7rem; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
        .pagination { display:flex; justify-content:center; gap:8px; margin-top:22px; }
        .pagination a, .pagination span { display:inline-flex; align-items:center; justify-content:center; min-width:38px; min-height:38px; padding:0 10px; border:1px solid var(--wm-line); border-radius:9px; color:var(--wm-ink); background:#fff; text-decoration:none; font-weight:800; }
        .pagination span[aria-current="page"] { color:#3f2a0d; background:var(--wm-gold); }
        .empty-state { padding: 52px 24px; text-align: center; }
        .empty-state h2 { margin: 0 0 8px; color: var(--wm-accent); font-size: 1.45rem; }
        .empty-state p { max-width: 520px; margin: 0 auto 18px; color: var(--wm-muted); }
        .back-link { display: inline-flex; margin-bottom: 15px; color: var(--wm-accent); font-weight: 800; text-decoration: none; }
        .detail-panel { padding: 24px; }
        .detail-heading { display: flex; justify-content: space-between; align-items: start; gap: 16px; margin-bottom: 20px; }
        .detail-heading h1 { margin: 0 0 8px; color: var(--wm-accent); font-size: clamp(2rem, 4vw, 3rem); line-height: 1.05; }
        .detail-grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr); gap: 22px; align-items: start; }
        .detail-main-image { width: 100%; max-height: 460px; min-height: 250px; object-fit: cover; border-radius: 14px; border: 1px solid var(--wm-line); background: #f1e5d7; }
        .detail-placeholder { min-height: 250px; display: grid; place-items: center; border: 1px dashed var(--wm-line); border-radius: 14px; color: var(--wm-muted); background: #f8efe5; font-weight: 800; }
        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 12px; }
        .gallery img { width: 100%; height: 90px; object-fit: cover; border: 1px solid var(--wm-line); border-radius: 10px; background: #f1e5d7; }
        .info-section { padding: 18px; border: 1px solid var(--wm-line); border-radius: 12px; background: rgba(255, 253, 249, .74); }
        .info-section + .info-section { margin-top: 12px; }
        .info-section h2 { margin: 0 0 12px; color: var(--wm-accent); font-size: 1.15rem; }
        .definition-list { display: grid; gap: 11px; margin: 0; }
        .definition-list div { padding-bottom: 10px; border-bottom: 1px solid var(--wm-line); }
        .definition-list div:last-child { padding-bottom: 0; border-bottom: 0; }
        dt { color: var(--wm-muted); font-size: .7rem; font-weight: 850; letter-spacing: .06em; text-transform: uppercase; }
        dd { margin: 4px 0 0; white-space: pre-line; }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; }
        .menu-card { padding: 14px 16px; border: 1px solid var(--wm-line); border-radius: 12px; background: #fff; }
        .menu-card h3 { margin: 0 0 5px; color: var(--wm-accent); font-size: 1rem; }
        .menu-card p { margin: 0; color: var(--wm-muted); font-size: .88rem; line-height: 1.5; }
        .menu-price { display: inline-flex; margin-bottom: 9px; padding: 5px 9px; border-radius: 999px; color: #3f2a0d; background: rgba(200, 148, 50, .2); font-size: .74rem; font-weight: 850; }
                .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
        .ai-guide-panel { position: relative; overflow: hidden; margin-top: 22px;
 padding: 22px; border: 1px solid rgba(163, 58, 45, .2); border-radius: 16px; color: var(--wm-ink); background: linear-gradient(135deg, rgba(255, 247, 236, .98), rgba(255, 253, 249, .98)); box-shadow: 0 16px 38px rgba(91, 29, 29, .08); }
        .ai-guide-panel::after { content: '✦'; position: absolute; right: 20px; top: 10px; color: rgba(200, 148, 50, .28); font-family: Georgia, serif; font-size: 5rem; line-height: 1; pointer-events: none; }
        .ai-guide-head, .ai-guide-form, .ai-guide-answer { position: relative; z-index: 1; }
        .ai-guide-head { display: flex; justify-content: space-between; gap: 18px; align-items: start; }
        .ai-guide-kicker { display: inline-flex; align-items: center; gap: 7px; margin-bottom: 8px; color: var(--wm-accent); font-size: .7rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .ai-guide-kicker::before { content: '✦'; color: var(--wm-gold); font-size: 1rem; }
        .ai-guide-head h2 { margin: 0 0 7px; color: var(--wm-accent); font-family: Georgia, serif; font-size: 1.45rem; }
        .ai-guide-head p { max-width: 640px; margin: 0; color: var(--wm-muted); line-height: 1.55; }
        .ai-guide-badge { display: inline-flex; align-items: center; min-height: 30px; padding: 0 10px; border: 1px solid rgba(61, 111, 85, .2); border-radius: 999px; color: var(--wm-green); background: rgba(61, 111, 85, .08); font-size: .7rem; font-weight: 900; white-space: nowrap; }
        .ai-guide-quick { display: flex; flex-wrap: wrap; gap: 8px; margin: 17px 0 13px; }
        .ai-guide-chip { min-height: 34px; padding: 0 11px; border: 1px solid var(--wm-line); border-radius: 999px; color: var(--wm-accent); background: rgba(255,255,255,.8); font-size: .78rem; font-weight: 800; cursor: pointer; }
        .ai-guide-chip:hover, .ai-guide-chip:focus-visible { border-color: rgba(163, 58, 45, .4); background: #fff; box-shadow: 0 0 0 4px rgba(163, 58, 45, .08); outline: none; }
        .ai-guide-form { display: flex; gap: 10px; align-items: stretch; }
        .ai-guide-form input { min-height: 46px; flex: 1; border: 1px solid var(--wm-line); border-radius: 11px; padding: 0 14px; color: var(--wm-ink); background: #fff; outline: none; }
        .ai-guide-form input:focus { border-color: rgba(163, 58, 45, .45); box-shadow: 0 0 0 4px rgba(163, 58, 45, .1); }
        .ai-guide-form button { min-height: 46px; padding: 0 17px; border: 0; border-radius: 11px; color: #3f2a0d; background: var(--wm-gold); font-weight: 900; cursor: pointer; }
        .ai-guide-form button:disabled { opacity: .65; cursor: wait; }
        .ai-guide-status { min-height: 20px; margin-top: 10px; color: var(--wm-muted); font-size: .8rem; }
        .ai-guide-status.error { color: #a33a2d; }
        .ai-guide-answer { display: none; margin-top: 14px; padding: 16px; border: 1px solid rgba(61, 111, 85, .18); border-radius: 12px; background: rgba(255,255,255,.76); }
        .ai-guide-answer.visible { display: block; animation: guide-in .25s ease-out; }
        .ai-guide-answer p { margin: 0; line-height: 1.6; }
        .ai-guide-highlights { display: flex; flex-wrap: wrap; gap: 7px; margin: 12px 0 0; padding: 0; list-style: none; }
        .ai-guide-highlights li { padding: 5px 9px; border-radius: 999px; color: var(--wm-green); background: rgba(61,111,85,.09); font-size: .74rem; font-weight: 800; }
        .ai-guide-source { margin-top: 11px; color: var(--wm-muted); font-size: .72rem; }
        @keyframes guide-in { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 1080px) { .filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .filter-actions { grid-column: 1 / -1; } .shop-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 850px) { .shell { grid-template-columns: 1fr; } .sidebar { position: static; height: auto; } .nav { grid-template-columns: repeat(2, minmax(0, 1fr)); } .sidebar-footer { margin-top: 24px; } .topbar, .content { padding-inline: 20px; } .detail-grid { grid-template-columns: 1fr; } }
        @media (max-width: 620px) { .nav, .filter-grid, .shop-grid { grid-template-columns: 1fr; } .topbar, .page-header, .detail-heading { display: grid; } .content { padding: 22px 16px 34px; } .page-header, .detail-panel { padding: 22px; } .header-pill { justify-self: start; } .filter-actions { grid-column: auto; flex-direction: column; align-items: stretch; } .filter-actions .button { width: 100%; } }
    </style>
</head>
<body>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand"><span class="brand-mark">W</span> WarisanMakan</div>
            <p class="nav-label">Home</p>
            <nav class="nav" aria-label="User home navigation">
                @auth
                    <a class="nav-item" href="{{ route('home') }}">Dashboard</a>
                    <a class="nav-item" href="{{ route('profile.show') }}">Profile</a>
                @else
                    <a class="nav-item" href="{{ route('landing') }}">Landing page</a>
                    <a class="nav-item" href="{{ route('login') }}">Log in</a>
                @endauth
            </nav>
            <p class="nav-label">Modules</p>
            <nav class="nav" aria-label="WarisanMakan modules">
                <a class="nav-item active" href="{{ route('heritage-shops.index') }}">Heritage Shop Tracking</a>
                <a class="nav-item" href="{{ route('passport.index') }}">Food Passport</a>
                <a class="nav-item" href="{{ url('/foodtrails') }}">Food Trail & Navigation</a>
            </nav>
            @auth
                <div class="sidebar-footer">
                    <p class="user-name">{{ auth()->user()->name }}</p>
                    <p class="user-role">WarisanMakan member</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="logout" type="submit">Log out</button>
                    </form>
                </div>
            @endauth
        </aside>

        <section class="main">
            <header class="topbar">
                <div>
                    <h2>Heritage Shop Tracking</h2>
                    <p>Explore verified heritage food businesses and their cultural stories.</p>
                </div>
                @auth
                    <a class="topbar-link" href="{{ route('home') }}">Back to dashboard</a>
                @else
                    <a class="topbar-link" href="{{ route('login') }}">Log in</a>
                @endauth
            </header>

            <main class="content">
                @if (isset($shop))
                    @php
                        $primaryImage = $shop->images->first();
                        $primaryImageUrl = $primaryImage ? $imageService->url($primaryImage) : null;
                    @endphp
                    <a class="back-link" href="{{ route('heritage-shops.index') }}">← Back to Heritage Shop list</a>
                    <article class="detail-panel">
                        <header class="detail-heading">
                            <div>
                                <p class="eyebrow" style="color:var(--wm-accent);">Heritage profile</p>
                                <h1>{{ $shop->shop_name }}</h1>
                                <p class="meta">{{ $shop->primary_food_category ?: 'Heritage food business' }} · {{ $shop->state ?: ($shop->city ?: 'Location not provided') }}</p>
                            </div>
                        </header>

                        <div class="detail-grid">
                            <div>
                                @if ($primaryImageUrl)
                                    <img class="detail-main-image" src="{{ $primaryImageUrl }}" alt="{{ $shop->shop_name }} heritage food shop" onerror="this.remove()">
                                @else
                                    <div class="detail-placeholder" role="img" aria-label="No image available for {{ $shop->shop_name }}">No image available</div>
                                @endif
                                @if ($shop->images->count() > 1)
                                    <div class="gallery" aria-label="Additional images">
                                        @foreach ($shop->images->skip(1) as $image)
                                            <img src="{{ $imageService->url($image) }}" alt="Additional view of {{ $shop->shop_name }}" loading="lazy" onerror="this.remove()">
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div>
                                <section class="info-section">
                                    <h2>Heritage information</h2>
                                    <dl class="definition-list">
                                        @if ($shop->heritage_story)
                                            <div><dt>Cultural significance</dt><dd>{{ $shop->heritage_story }}</dd></div>
                                        @endif
                                        @if ($shop->founder_name || $shop->establishment_year)
                                            <div><dt>Origins</dt><dd>{{ $shop->founder_name ? 'Founder: '.$shop->founder_name : '' }}{{ $shop->founder_name && $shop->establishment_year ? ' · ' : '' }}{{ $shop->establishment_year ? 'Established: '.$shop->establishment_year : '' }}</dd></div>
                                        @endif
                                        @if ($shop->founder_background)
                                            <div><dt>Founder background</dt><dd>{{ $shop->founder_background }}</dd></div>
                                        @endif
                                        @if ($shop->current_owner_name || $shop->current_owner_details)
                                            <div><dt>Current ownership</dt><dd>{{ $shop->current_owner_name }}{{ $shop->current_owner_name && $shop->current_owner_details ? ' — ' : '' }}{{ $shop->current_owner_details }}</dd></div>
                                        @endif
                                    </dl>
                                </section>

                                <section class="info-section">
                                    <h2>Visit information</h2>
                                    <dl class="definition-list">
                                        @if ($shop->address || $shop->city || $shop->state || $shop->postal_code)
                                            <div><dt>Address</dt><dd>{{ collect([$shop->address, $shop->city, $shop->state, $shop->postal_code])->filter()->implode(', ') }}</dd></div>
                                        @endif
                                        @if ($shop->operating_hours)
                                            <div><dt>Operating information</dt><dd>{{ is_array($shop->operating_hours) ? implode('; ', $shop->operating_hours) : $shop->operating_hours }}</dd></div>
                                        @endif
                                        @if ($shop->contact_number)
                                            <div><dt>Contact</dt><dd>{{ $shop->contact_number }}</dd></div>
                                        @endif
                                    </dl>
                                </section>
                            </div>
                        </div>

                        <section class="ai-guide-panel" aria-labelledby="ai-guide-title" data-ai-guide>
                            <div class="ai-guide-head">
                                <div>
                                    <span class="ai-guide-kicker">Heritage AI guide</span>
                                    <h2 id="ai-guide-title">Ask the story behind this place</h2>
                                    <p>Ask about the recorded history, location, operating information, or menu highlights. Answers are grounded in this verified profile.</p>
                                </div>
                                <span class="ai-guide-badge">Grounded answers</span>
                            </div>
                            <div class="ai-guide-quick" aria-label="Suggested Heritage AI questions">
                                <button class="ai-guide-chip" type="button" data-ai-question="What is special about this shop's heritage story?">Why is it special?</button>
                                <button class="ai-guide-chip" type="button" data-ai-question="What menu highlights are recorded for this shop?">What should I notice?</button>
                                <button class="ai-guide-chip" type="button" data-ai-question="Where is this shop and what operating information is recorded?">Plan a visit</button>
                            </div>
                            <form class="ai-guide-form" data-ai-form>
                                <label class="sr-only" for="ai-guide-question">Ask the Heritage AI guide</label>
                                <input id="ai-guide-question" name="question" maxlength="500" placeholder="Ask a question about this heritage shop…" autocomplete="off" required>
                                <button type="submit">Ask the guide</button>
                            </form>
                            <div class="ai-guide-status" data-ai-status aria-live="polite"></div>
                            <div class="ai-guide-answer" data-ai-answer aria-live="polite">
                                <p data-ai-answer-text></p>
                                <ul class="ai-guide-highlights" data-ai-highlights></ul>
                                <p class="ai-guide-source" data-ai-source></p>
                            </div>
                        </section>

                        @if (!empty($menuItems) && is_array($menuItems))
                            <section class="info-section" style="margin-top:22px;">
                                <h2>Menu highlights</h2>
                                <div class="menu-grid">
                                    @foreach ($menuItems as $item)
                                        <div class="menu-card">
                                            @if (!empty($item['price'])) <span class="menu-price">{{ $item['price'] }}</span> @endif
                                            <h3>{{ $item['name'] ?? 'House special' }}</h3>
                                            @if (!empty($item['desc'])) <p>{{ $item['desc'] }}</p> @endif
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if ($shop->source_url)
                            <section class="info-section" style="margin-top:12px;">
                                <h2>Related information</h2>
                                <p class="description">The profile was prepared from the registered source information. <a href="{{ $shop->source_url }}" target="_blank" rel="noopener noreferrer" style="color:var(--wm-accent);font-weight:800;">View source</a></p>
                            </section>
                        @endif

                        @auth
                            <div class="card-actions" style="margin-top:22px;">
                                <a class="button secondary" href="{{ route('heritage-shops.correction-requests.create', $shop) }}">Report incorrect information</a>
                            </div>
                        @endauth
                    </article>
                @else
                    <header class="page-header">
                        <div>
                            <p class="eyebrow">Heritage food explorer</p>
                            <h1>Discover Malaysia's food heritage</h1>
                            <p>Browse verified heritage food shops, learn their cultural significance, and explore the stories preserved by the WarisanMakan community.</p>
                        </div>
                        <span class="header-pill">{{ $shops->total() }} published record{{ $shops->total() === 1 ? '' : 's' }}</span>
                    </header>

                    <section class="filter-panel" aria-labelledby="filter-heading">
                        <h2 id="filter-heading" style="margin:0 0 15px; font-family:Georgia,serif; color:var(--wm-accent);">Find a heritage shop</h2>
                        <form action="{{ route('heritage-shops.index') }}" method="GET">
                            <div class="filter-grid">
                                <div class="field">
                                    <label for="search">Search</label>
                                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Name, location, story">
                                </div>
                                <div class="field">
                                    <label for="category">Category</label>
                                    <select id="category" name="category">
                                        <option value="">All categories</option>
                                        @foreach ($categories as $option)
                                            <option value="{{ $option }}" @selected(strtolower($category) === strtolower($option))>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="state">State / region</label>
                                    <select id="state" name="state">
                                        <option value="">All states</option>
                                        @foreach ($states as $option)
                                            <option value="{{ $option }}" @selected(strtolower($state) === strtolower($option))>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="sort">Sort by</label>
                                    <select id="sort" name="sort">
                                        <option value="name_asc" @selected($sort === 'name_asc')>Name A–Z</option>
                                        <option value="name_desc" @selected($sort === 'name_desc')>Name Z–A</option>
                                        <option value="newest" @selected($sort === 'newest')>Newest</option>
                                        <option value="oldest" @selected($sort === 'oldest')>Oldest</option>
                                    </select>
                                </div>
                                <div class="filter-actions">
                                    <button class="button primary" type="submit">Apply filters</button>
                                    <a class="button secondary" href="{{ route('heritage-shops.index') }}">Reset</a>
                                </div>
                            </div>
                        </form>
                    </section>

                    @if ($shops->isEmpty())
                        <section class="empty-state" aria-live="polite">
                            <h2>No heritage shops found</h2>
                            <p>{{ $search || $category || $state ? 'No records match the selected search or filters. Try clearing a filter or using a broader keyword.' : 'There are no published heritage shop records available yet.' }}</p>
                            @if ($search || $category || $state)
                                <a class="button secondary" href="{{ route('heritage-shops.index') }}">Clear search and filters</a>
                            @endif
                        </section>
                    @else
                        <div class="result-summary"><span>Showing {{ $shops->count() }} of {{ $shops->total() }} published record{{ $shops->total() === 1 ? '' : 's' }}.</span><span>Images are shown when a verified gallery is available.</span></div>
                        <section class="shop-grid" aria-label="Heritage shop records">
                            @foreach ($shops as $shop)
                                @php
                                    $primaryImage = $shop->images->first();
                                    $primaryImageUrl = $primaryImage ? $imageService->url($primaryImage) : null;
                                @endphp
                                <article class="shop-card">
                                    <div class="image-frame">
                                        @if ($primaryImageUrl)
                                            <img src="{{ $primaryImageUrl }}" alt="{{ $shop->shop_name }} heritage food shop" loading="lazy" onerror="this.remove()">
                                        @else
                                            <div class="image-placeholder" role="img" aria-label="No image available for {{ $shop->shop_name }}">No image available</div>
                                        @endif
                                    </div>
                                    <div class="shop-card-body">
                                        <p class="meta">{{ $shop->primary_food_category ?: 'Heritage food business' }} · {{ $shop->state ?: ($shop->city ?: 'Location not provided') }}</p>
                                        <h2>{{ $shop->shop_name }}</h2>
                                        <p class="description">{{ \Illuminate\Support\Str::limit($shop->heritage_story ?: 'Heritage information is being prepared.', 150) }}</p>
                                        <div class="card-facts">
                                            <div><strong>Location:</strong> {{ $shop->location ?: 'Not provided' }}</div>
                                                            @if ($shop->operating_hours)<div><strong>Hours:</strong> {{ is_array($shop->operating_hours) ? implode('; ', $shop->operating_hours) : $shop->operating_hours }}</div>@endif
                                        </div>
                                        <div class="card-actions"><a class="button primary" href="{{ route('heritage-shops.show', ['id' => $shop->id]) }}">View details</a></div>
                                    </div>
                                </article>
                            @endforeach
                        </section>
                        <nav class="pagination" aria-label="Heritage shop pages">{{ $shops->onEachSide(1)->links() }}</nav>
                    @endif
                @endif
            </main>
        </section>
    </div>
    @if (isset($shop))
        <script>
            (() => {
                const root = document.querySelector('[data-ai-guide]');
                if (!root) return;
                const form = root.querySelector('[data-ai-form]');
                const input = root.querySelector('#ai-guide-question');
                const submit = form.querySelector('button[type="submit"]');
                const status = root.querySelector('[data-ai-status]');
                const answer = root.querySelector('[data-ai-answer]');
                const answerText = root.querySelector('[data-ai-answer-text]');
                const highlights = root.querySelector('[data-ai-highlights]');
                const source = root.querySelector('[data-ai-source]');
                const endpoint = @json(route('heritage-shops.ai-guide', ['heritageShop' => $shop->id]));
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                async function askGuide(question) {
                    const trimmed = String(question || '').trim();
                    if (!trimmed) return;
                    submit.disabled = true;
                    status.className = 'ai-guide-status';
                    status.textContent = 'The guide is checking the verified profile…';
                    answer.classList.remove('visible');
                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({ question: trimmed }),
                        });
                        const payload = await response.json();
                        if (!response.ok) throw new Error(payload.message || 'The guide is unavailable right now.');
                        answerText.textContent = payload.answer || 'No grounded answer was available.';
                        highlights.replaceChildren();
                        (Array.isArray(payload.highlights) ? payload.highlights : []).forEach((item) => {
                            const li = document.createElement('li');
                            li.textContent = item;
                            highlights.appendChild(li);
                        });
                        source.textContent = payload.source_note || 'Grounded in the verified profile shown on this page.';
                        answer.classList.add('visible');
                        status.textContent = payload.status === 'fallback' ? 'AI is taking a break; the saved verified facts are still available.' : 'Answer grounded in this HeritageShop profile.';
                    } catch (error) {
                        status.className = 'ai-guide-status error';
                        status.textContent = error.message || 'The guide is unavailable right now.';
                    } finally {
                        submit.disabled = false;
                    }
                }

                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    askGuide(input.value);
                });
                root.querySelectorAll('[data-ai-question]').forEach((button) => {
                    button.addEventListener('click', () => {
                        input.value = button.dataset.aiQuestion || '';
                        askGuide(input.value);
                    });
                });
            })();
        </script>
    @endif
</body>
</html>
