<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($shop) ? $shop->shop_name.' - '.__('Heritage Shop') : __('Heritage Shops') }} - Warisan Makan</title>
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
        .page-header { display: flex; justify-content: space-between; align-items: end; gap: 20px; margin-bottom: 24px; padding: 30px; border-radius: 14px; color: #fffaf4; background: linear-gradient(125deg, #96352c, #54201b); box-shadow: 0 20px 50px rgba(91, 29, 29, .18); }
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
        .guest-trigger { display:inline-flex; align-items:center; gap:8px; min-height:42px; padding:0 14px; border:1px solid var(--wm-line); border-radius:999px; color:var(--wm-accent); background:#fff; font-weight:800; cursor:pointer; }
        .guest-trigger::before { content:''; width:9px; height:9px; border-radius:50%; background:var(--wm-gold); }
        .filter-actions { display: flex; gap: 8px; align-items: end; }
        .result-summary { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 0 0 14px; color: var(--wm-muted); font-size: .86rem; }
        .shop-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .shop-card { overflow: hidden; display: flex; flex-direction: column; min-width: 0; }
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
        @media (max-width: 1080px) { .filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .filter-actions { grid-column: 1 / -1; } .shop-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 850px) { .shell { grid-template-columns: 1fr; } .sidebar { position: static; height: auto; } .nav { grid-template-columns: repeat(2, minmax(0, 1fr)); } .sidebar-footer { margin-top: 24px; } .topbar, .content { padding-inline: 20px; } .detail-grid { grid-template-columns: 1fr; } }
        @media (max-width: 620px) { .nav, .filter-grid, .shop-grid { grid-template-columns: 1fr; } .topbar, .page-header, .detail-heading { display: grid; } .content { padding: 22px 16px 34px; } .page-header, .detail-panel { padding: 22px; } .header-pill { justify-self: start; } .filter-actions { grid-column: auto; flex-direction: column; align-items: stretch; } .filter-actions .button { width: 100%; } }
    </style>
</head>
<body>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand"><span class="brand-mark">W</span> WarisanMakan</div>
            <p class="nav-label">{{ __('Home') }}</p>
            <nav class="nav" aria-label="User home navigation">
                @auth
                    <a class="nav-item" href="{{ route('home') }}">{{ __('Dashboard') }}</a>
                    <a class="nav-item" href="{{ route('profile.show') }}">{{ __('Profile') }}</a>
                @else
                    <a class="nav-item" href="{{ route('home') }}">{{ __('Dashboard') }}</a>
                    <a class="nav-item" href="{{ route('login') }}">{{ __('Log in') }}</a>
                @endauth
            </nav>
            <p class="nav-label">{{ __('Modules') }}</p>
            <nav class="nav" aria-label="WarisanMakan modules">
                <a class="nav-item active" href="{{ route('heritage-shops.index') }}">{{ __('Heritage Shop Tracking') }}</a>
                @guest
                    <a class="nav-item" href="#" data-login-required="true">{{ __('Food Passport') }}</a>
                    <a class="nav-item" href="#" data-login-required="true">{{ __('Food Trail & Navigation') }}</a>
                @else
                    <a class="nav-item" href="{{ route('passport.index') }}">{{ __('Food Passport') }}</a>
                    <a class="nav-item" href="{{ url('/foodtrails') }}">{{ __('Food Trail & Navigation') }}</a>
                @endguest
            </nav>
            @auth
                <div class="sidebar-footer">
                    <p class="user-name">{{ auth()->user()->name }}</p>
                    <p class="user-role">WarisanMakan member</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="logout" type="submit">{{ __('Log out') }}</button>
                    </form>
                </div>
            @endauth
        </aside>

        <section class="main">
            <header class="topbar">
                <div>
                    <h2>{{ __('Heritage Shop Tracking') }}</h2>
                    <p>{{ __('Explore verified heritage food businesses and their cultural stories.') }}</p>
                </div>
                @auth
                    <a class="topbar-link" href="{{ route('home') }}">{{ __('Back to dashboard') }}</a>
                @else
                    <button class="guest-trigger" type="button" data-login-trigger>{{ __('Guest Mode') }}</button>
                @endauth
            </header>

            <main class="content">
                @if (isset($shop))
                    @php
                        $primaryImage = $shop->images->first();
                        $primaryImageUrl = $primaryImage ? $imageService->url($primaryImage) : null;
                    @endphp
                    <a class="back-link" href="{{ route('heritage-shops.index') }}">← {{ __('Back to Heritage Shop list') }}</a>
                    <article class="detail-panel">
                        <header class="detail-heading">
                            <div>
                                <p class="eyebrow" style="color:var(--wm-accent);">{{ __('Heritage profile') }}</p>
                                <h1>{{ $shop->shop_name }}</h1>
                                <p class="meta">{{ $shop->primary_food_category ?: 'Heritage food business' }} · {{ $shop->state ?: ($shop->city ?: 'Location not provided') }}</p>
                            </div>
                        </header>

                        <div class="detail-grid">
                            <div>
                                @if ($primaryImageUrl)
                                    <img class="detail-main-image" src="{{ $primaryImageUrl }}" alt="{{ $shop->shop_name }} heritage food shop" onerror="this.remove()">
                                @else
                                    <div class="detail-placeholder" role="img" aria-label="{{ __('No image available for :name', ['name' => $shop->shop_name]) }}">{{ __('No image available') }}</div>
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
                                    <h2>{{ __('Heritage information') }}</h2>
                                    <dl class="definition-list">
                                        @if ($shop->heritage_story)
                                            <div><dt>{{ __('Cultural significance') }}</dt><dd>{{ $shop->heritage_story }}</dd></div>
                                        @endif
                                        @if ($shop->founder_name || $shop->establishment_year)
                                            <div><dt>{{ __('Origins') }}</dt><dd>{{ $shop->founder_name ? 'Founder: '.$shop->founder_name : '' }}{{ $shop->founder_name && $shop->establishment_year ? ' · ' : '' }}{{ $shop->establishment_year ? 'Established: '.$shop->establishment_year : '' }}</dd></div>
                                        @endif
                                        @if ($shop->founder_background)
                                            <div><dt>{{ __('Founder background') }}</dt><dd>{{ $shop->founder_background }}</dd></div>
                                        @endif
                                        @if ($shop->current_owner_name || $shop->current_owner_details)
                                            <div><dt>{{ __('Current ownership') }}</dt><dd>{{ $shop->current_owner_name }}{{ $shop->current_owner_name && $shop->current_owner_details ? ' — ' : '' }}{{ $shop->current_owner_details }}</dd></div>
                                        @endif
                                    </dl>
                                </section>

                                <section class="info-section">
                                    <h2>{{ __('Visit information') }}</h2>
                                    <dl class="definition-list">
                                        @if ($shop->address || $shop->city || $shop->state || $shop->postal_code)
                                            <div><dt>{{ __('Address') }}</dt><dd>{{ collect([$shop->address, $shop->city, $shop->state, $shop->postal_code])->filter()->implode(', ') }}</dd></div>
                                        @endif
                                        @if ($shop->operating_hours)
                                            <div><dt>{{ __('Operating information') }}</dt><dd>{{ is_array($shop->operating_hours) ? implode('; ', $shop->operating_hours) : $shop->operating_hours }}</dd></div>
                                        @endif
                                        @if ($shop->contact_number)
                                            <div><dt>{{ __('Contact') }}</dt><dd>{{ $shop->contact_number }}</dd></div>
                                        @endif
                                    </dl>
                                </section>
                            </div>
                        </div>

                        @if (!empty($menuItems) && is_array($menuItems))
                            <section class="info-section" style="margin-top:22px;">
                                <h2>{{ __('Menu highlights') }}</h2>
                                <div class="menu-grid">
                                    @foreach ($menuItems as $item)
                                        <div class="menu-card">
                                            @if (!empty($item['price'])) <span class="menu-price">{{ $item['price'] }}</span> @endif
                                            <h3>{{ $item['name'] ?? __('House special') }}</h3>
                                            @if (!empty($item['desc'])) <p>{{ $item['desc'] }}</p> @endif
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if ($shop->source_url)
                            <section class="info-section" style="margin-top:12px;">
                                <h2>{{ __('Related information') }}</h2>
                                <p class="description">{{ __('The profile was prepared from the registered source information.') }}<a href="{{ $shop->source_url }}" target="_blank" rel="noopener noreferrer" style="color:var(--wm-accent);font-weight:800;">{{ __('View source') }}</a></p>
                            </section>
                        @endif

                        @auth
                            <div class="card-actions" style="margin-top:22px;">
                                <a class="button secondary" href="{{ route('heritage-shops.correction-requests.create', $shop) }}">{{ __('Report incorrect information') }}</a>
                            </div>
                        @endauth
                    </article>
                @else
                    <header class="page-header">
                        <div>
                            <p class="eyebrow">{{ __('Heritage food explorer') }}</p>
                            <h1>{{ __('Discover Malaysia\'s food heritage') }}</h1>
                            <p>{{ __('Browse verified heritage food shops, learn their cultural significance, and explore the stories preserved by the WarisanMakan community.') }}</p>
                        </div>
                        <span class="header-pill">{{ trans_choice(':count published record|:count published records', $shops->total(), ['count' => $shops->total()]) }}</span>
                    </header>

                    <section class="filter-panel" aria-labelledby="filter-heading">
                        <h2 id="filter-heading">{{ __('Find a heritage shop') }}</h2>
                        <form action="{{ route('heritage-shops.index') }}" method="GET">
                            <div class="filter-grid">
                                <div class="field">
                                    <label for="search">{{ __('Search') }}</label>
                                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="{{ __('Name, location, story') }}">
                                </div>
                                <div class="field">
                                    <label for="category">{{ __('Category') }}</label>
                                    <select id="category" name="category">
                                        <option value="">{{ __('All categories') }}</option>
                                        @foreach ($categories as $option)
                                            <option value="{{ $option }}" @selected(strtolower($category) === strtolower($option))>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="state">{{ __('State / region') }}</label>
                                    <select id="state" name="state">
                                        <option value="">{{ __('All states') }}</option>
                                        @foreach ($states as $option)
                                            <option value="{{ $option }}" @selected(strtolower($state) === strtolower($option))>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="sort">{{ __('Sort by') }}</label>
                                    <select id="sort" name="sort">
                                        <option value="name_asc">{{ __('Name A–Z') }}</option>
                                        <option value="name_desc">{{ __('Name Z–A') }}</option>
                                        <option value="newest">{{ __('Newest') }}</option>
                                        <option value="oldest">{{ __('Oldest') }}</option>
                                    </select>
                                </div>
                                <div class="filter-actions">
                                    <button class="button primary" type="submit">{{ __('Apply filters') }}</button>
                                    <a class="button secondary" href="{{ route('heritage-shops.index') }}">{{ __('Reset') }}</a>
                                </div>
                            </div>
                        </form>
                    </section>

                    @if ($shops->isEmpty())
                        <section class="empty-state" aria-live="polite">
                            <h2>{{ __('No heritage shops found') }}</h2>
                            <p>
                                {{ $search || $category || $state
                                ? __('No records match the selected search or filters. Try clearing a filter or using a broader keyword.')
                                : __('There are no published heritage shop records available yet.') }}
                            </p>
                            @if ($search || $category || $state)
                                <a class="button secondary" href="{{ route('heritage-shops.index') }}">{{ __('Clear search and filters') }}</a>
                            @endif
                        </section>
                    @else
                        <div class="result-summary">
                            <span>
                                {{ __('Showing :shown of :total published records.', [
                                    'shown' => $shops->count(),
                                    'total' => $shops->total(),
                                ]) }}
                            </span>

                            <span>
                                {{ __('Images are shown when a verified gallery is available.') }}
                        </span>
                        </div>
                        <section class="shop-grid" aria-label="{{ __('Heritage shop records') }}">
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
                                            <div class="image-placeholder" role="img" aria-label="{{ __('No image available for :name', ['name' => $shop->shop_name]) }}">
                                                {{ __('No image available') }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="shop-card-body">
                                        <p class="meta">
                                            {{ $shop->primary_food_category ?: __('Heritage food business') }}
                                            ·
                                            {{ $shop->state ?: ($shop->city ?: __('Location not provided')) }}
                                        </p>
                                        <h2>{{ $shop->shop_name }}</h2>
                                        <p class="description">{{ \Illuminate\Support\Str::limit($shop->heritage_story ?: __('Heritage information is being prepared.'), 150) }}</p>
                                        <div class="card-facts">
                                            <div>
                                                <strong>{{ __('Location') }}:</strong>
                                                {{ $shop->location ?: __('Not provided') }}
                                            </div>
                                        @if ($shop->operating_hours)
                                            <div>
                                                <strong>{{ __('Hours') }}:</strong>
                                                {{ is_array($shop->operating_hours)
                                                    ? implode('; ', $shop->operating_hours)
                                                    : $shop->operating_hours }}
                                            </div>
                                        @endif
                                        </div>
                                        <div class="card-actions">
                                            <a class="button primary" href="{{ auth()->check() ? route('heritage-shops.show', ['id' => $shop->id]) : '#' }}" @guest data-login-required="true" @endguest>{{ __('View details') }}</a>
                                        </div>
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
    @guest
        @include('partials.login-required-modal')
    @endguest
</body>
</html>
