@extends('layouts.user')

@section('title', $shop->shop_name.' - Menu & Stories')
@section('user-topbar-title', __('Heritage Discovery'))
@section('user-topbar-subtitle', __('Explore verified dishes and living food heritage stories.'))

@section('user-topbar-actions')
<a class="user-topbar-link" href="{{ route('heritage-shops.show', ['id' => $shop->id]) }}">{{ __('View details') }}</a>
<a class="user-topbar-link" href="{{ route('heritage-shops.index') }}">{{ __('All heritage shops') }}</a>
@endsection

@push('styles')
<style>
        :root {
            color-scheme: light;
            --bg:#f7f1ea;
            --panel:#fffdf9;
            --ink:#2e2420;
            --muted:#7b6a60;
            --line:rgba(66,43,32,.12);
            --accent:#a33a2d;
            --gold:#c89432;
            --green:#3d6f55;
            --deep:#3b1b18;
        }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; overflow-x:hidden; color:var(--ink); background:linear-gradient(135deg,rgba(163,58,45,.06),transparent 35%),linear-gradient(315deg,rgba(61,111,85,.07),transparent 38%),var(--bg); font-family:'Instrument Sans',ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; }
        a { color:inherit; }
        h1,h2,h3,p { overflow-wrap:break-word; word-break:normal; }
        h1,h2,h3 { font-family:Georgia,'Times New Roman',serif; }
        .topbar { min-height:76px; display:flex; align-items:center; justify-content:space-between; gap:18px; padding:16px clamp(18px,5vw,68px); border-bottom:1px solid var(--line); background:rgba(255,253,249,.92); }
        .brand { display:flex; align-items:center; gap:10px; min-width:0; }
        .brand-mark { width:36px; height:36px; display:grid; place-items:center; flex:0 0 auto; border-radius:11px; color:#3b1b16; background:var(--gold); font-family:Georgia,serif; font-weight:900; }
        .brand strong { font-family:Georgia,serif; font-size:1.05rem; }
        .topbar-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px; }
        .button { min-height:40px; display:inline-flex; align-items:center; justify-content:center; padding:0 13px; border:1px solid var(--line); border-radius:10px; background:#fff; color:var(--ink); font-size:.82rem; font-weight:850; text-decoration:none; }
        .button.primary { border-color:transparent; color:#3f2a0d; background:var(--gold); }
        .button:hover { border-color:rgba(163,58,45,.35); }
        main { width:min(1180px,100%); margin:0 auto; padding:32px clamp(16px,4vw,34px) 56px; }
        .back-link { display:inline-flex; margin-bottom:16px; color:var(--accent); font-size:.84rem; font-weight:850; text-decoration:none; }
        .hero { position:relative; overflow:hidden; display:grid; grid-template-columns:minmax(0,1.6fr) minmax(230px,.8fr); gap:22px; align-items:end; margin-bottom:20px; padding:30px; border-radius:18px; color:#fffaf4; background:linear-gradient(125deg,#96352c,#54201b); box-shadow:0 20px 50px rgba(91,29,29,.18); }
        .hero > * { position:relative; z-index:1; }
        .eyebrow { margin:0 0 8px; color:#e7bf74; font-size:.72rem; font-weight:900; letter-spacing:.13em; text-transform:uppercase; }
        .hero h1 { margin:0 0 9px; font-size:clamp(2rem,5vw,3.4rem); line-height:1; }
        .hero p { max-width:720px; margin:0; color:rgba(255,250,244,.78); line-height:1.6; }
        .hero-meta { display:grid; gap:9px; justify-items:end; color:rgba(255,250,244,.82); font-size:.82rem; text-align:right; }
        .hero-meta span { display:inline-flex; align-items:center; min-height:32px; padding:0 10px; border:1px solid rgba(255,255,255,.18); border-radius:999px; background:rgba(255,255,255,.08); }
        .panel { border:1px solid var(--line); border-radius:15px; background:var(--panel); box-shadow:0 12px 32px rgba(77,48,34,.07); }
        .section-heading { display:flex; align-items:start; justify-content:space-between; gap:18px; padding:22px 22px 0; }
        .section-heading h2 { margin:0 0 6px; color:var(--accent); font-size:1.45rem; }
        .section-heading p { margin:0; color:var(--muted); line-height:1.55; }
        .count { flex:0 0 auto; min-height:30px; display:inline-flex; align-items:center; padding:0 10px; border-radius:999px; color:var(--green); background:rgba(61,111,85,.1); font-size:.72rem; font-weight:900; white-space:nowrap; }
        .menu-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:14px; padding:22px; }
        .menu-card { overflow:hidden; display:flex; flex-direction:column; min-width:0; border:1px solid var(--line); border-radius:14px; background:#fff; transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease; }
        .menu-card:hover { transform:translateY(-3px); border-color:rgba(163,58,45,.25); box-shadow:0 16px 30px rgba(77,48,34,.1); }
        .menu-card-image { width:100%; height:160px; object-fit:cover; background:#f1e5d7; }
        .menu-card-content { display:flex; flex:1; flex-direction:column; padding:17px; }
        .menu-card-top { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; margin-bottom:10px; }
        .category { color:var(--gold); font-size:.68rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; }
        .price { display:inline-flex; padding:5px 9px; border-radius:999px; color:#3f2a0d; background:rgba(200,148,50,.2); font-size:.74rem; font-weight:900; }
        .menu-card h3 { margin:0 0 7px; color:var(--accent); font-size:1.2rem; }
        .menu-card h3 a { text-decoration:none; }
        .menu-card h3 a:hover,.menu-card h3 a:focus-visible { text-decoration:underline; outline:2px solid rgba(163,58,45,.22); outline-offset:3px; }
        .description { margin:0; color:var(--muted); font-size:.88rem; line-height:1.55; }
        .significance { margin-top:15px; padding-top:12px; border-top:1px solid var(--line); color:var(--muted); font-size:.82rem; line-height:1.5; }
        .significance summary { color:var(--accent); cursor:pointer; font-weight:850; }
        .significance p { margin:8px 0 0; }
        .availability { display:inline-flex; width:fit-content; margin-top:auto; padding:5px 9px; border-radius:999px; color:var(--green); background:rgba(61,111,85,.09); font-size:.72rem; font-weight:850; }
        .empty { margin:22px; padding:44px 22px; border:1px dashed rgba(163,58,45,.25); border-radius:13px; color:var(--muted); background:linear-gradient(135deg,rgba(239,224,207,.42),rgba(255,253,249,.74)); text-align:center; }
        .empty strong { display:block; margin-bottom:6px; color:var(--accent); font-family:Georgia,serif; font-size:1.2rem; }
        .footer-actions { display:flex; flex-wrap:wrap; justify-content:center; gap:9px; margin-top:20px; }
        .source { margin-top:20px; padding:16px 18px; color:var(--muted); font-size:.8rem; line-height:1.5; }
        .source a { color:var(--accent); font-weight:850; }
        @media (max-width:760px) { .topbar { align-items:flex-start; flex-direction:column; } .topbar-actions { width:100%; justify-content:flex-start; } .hero { grid-template-columns:1fr; padding:24px; } .hero-meta { justify-items:start; text-align:left; } .section-heading { display:grid; } .menu-grid { grid-template-columns:1fr; padding:16px; } }
        @media (max-width:430px) { .button { flex:1 1 100%; width:100%; } main { padding-top:22px; } .hero h1 { font-size:2.25rem; } }
        @media (prefers-reduced-motion:reduce) { .menu-card { transition:none; } }
    </style>
@endpush

@section('content')
<main>
        <a class="back-link" href="{{ route('heritage-shops.show', ['id' => $shop->id]) }}">← {{ __('Back to') }} {{ $shop->shop_name }} {{ __('profile') }}</a>
        <section class="hero">
            <div>
                <p class="eyebrow">{{ __('Verified menu & living food heritage') }}</p>
                <h1>{{ $shop->shop_name }}</h1>
                <p>{{ __('Explore the recorded dishes, the stories behind them, and the details that help visitors understand this shop’s food heritage.') }}</p>
            </div>
            <div class="hero-meta">
                @if ($shop->primary_food_category)<span>{{ $shop->primary_food_category }}</span>@endif
                @if ($shop->state || $shop->city)<span>{{ $shop->state ?: $shop->city }}</span>@endif
                        <span>
                            {{ count($menuItems) }}
                            {{ count($menuItems) === 1 ? __('recorded dish') : __('recorded dishes') }}
                        </span>
            </div>
        </section>

        <section class="panel" aria-labelledby="menu-heading">
            <div class="section-heading">
                <div>
                    <p class="eyebrow" style="color:var(--gold);">{{ __('Food stories') }}</p>
                    <h2 id="menu-heading">{{ __('Menu & heritage stories') }}</h2>
                    <p>{{ __('These are the verified dishes currently recorded for this HeritageShop profile.') }}</p>
                </div>
                <span class="count">{{ count($menuItems) }} item{{ count($menuItems) === 1 ? '' : 's' }}</span>
            </div>
            @if ($menuItems !== [])
                <div class="menu-grid">
                    @foreach ($menuItems as $item)
                        <article class="menu-card">
                            @if (!empty($item['image_url']))
                                <x-enlargeable-image
                                    :src="$item['image_url']"
                                    :alt="($item['name'] ?? __('Heritage food item')).' at '.$shop->shop_name"
                                    image-class="menu-card-image"
                                />
                            @endif
                            <div class="menu-card-content">
                                <div class="menu-card-top">
                                    @if (!empty($item['category']))<span class="category">{{ $item['category'] }}</span>@endif
                                    @if (!empty($item['price']))<span class="price">{{ $item['price'] }}</span>@endif
                                </div>
                                <h3>
                                    @if (!empty($item['id']))
                                        <a href="{{ route('heritage-shops.food-items.show', [$shop, $item['id']]) }}">{{ $item['name'] ?? 'Heritage food item' }}</a>
                                    @else
                                        {{ $item['name'] ?? 'Heritage food item' }}
                                    @endif
                                </h3>
                                @if (!empty($item['desc']) || !empty($item['description']))<p class="description">{{ $item['desc'] ?? $item['description'] }}</p>@endif
                                @if (!empty($item['heritage_significance']))
                                    <details class="significance"><summary>Why this dish matters</summary><p>{{ $item['heritage_significance'] }}</p></details>
                                @endif
                                @if (!empty($item['availability']))<span class="availability">{{ $item['availability'] }}</span>@endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="empty"><strong>{{ __('The menu is still being documented') }}</strong><span>{{ __('Return to the profile to explore the shop’s verified heritage information. New dishes will appear here after an administrator records them.') }}</span></div>
            @endif
        </section>

        @if ($safeSourceUrl = $shop->safeSourceUrl())
            <div class="panel source">{{ __('This menu page is based on the registered HeritageShop source.') }}<a href="{{ $safeSourceUrl }}" target="_blank" rel="noopener noreferrer">{{ __('View source') }} ↗</a></div>
        @endif
        <div class="footer-actions">
            <a class="button" href="{{ route('heritage-shops.show', ['id' => $shop->id]) }}">{{ __('View full profile') }}</a>
            <a class="button primary" href="{{ route('heritage-shops.index') }}">{{ __('Discover more heritage shops') }}</a>
        </div>
        <x-image-lightbox />
    </main>
@endsection
