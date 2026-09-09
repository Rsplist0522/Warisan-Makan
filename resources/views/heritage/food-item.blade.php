@extends('layouts.user')

@section('title', $foodItem->name.' - '.$shop->shop_name)
@section('user-topbar-title', __('Heritage Discovery'))
@section('user-topbar-subtitle', __('One dish, one story, one living food tradition.'))

@section('user-topbar-actions')
<a class="user-topbar-link" href="{{ route('heritage-shops.show', ['id' => $shop->id]) }}#food-menu">{{ __('Back to shop profile') }}</a>
<a class="user-topbar-link" href="{{ route('heritage-shops.menu', $shop) }}">{{ __('Full menu') }}</a>
@endsection

@push('head')
<meta name="description" content="Discover {{ $foodItem->name }} and its heritage story at {{ $shop->shop_name }}.">
@endpush

@push('styles')
<style>
        :root { --ink:#3c281f; --muted:#806f64; --accent:#7e2723; --gold:#c89232; --cream:#f7efe6; --line:#eadccd; --green:#3d6f55; }
        * { box-sizing:border-box; }
        body { margin:0; color:var(--ink); background:var(--cream); font:15px/1.55 Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color:inherit; }
        .shell { min-height:100vh; display:grid; grid-template-columns:220px minmax(0,1fr); }
        .sidebar { display:flex; flex-direction:column; padding:24px 18px; color:#fff8f1; background:#341713; }
        .brand { display:flex; align-items:center; gap:10px; font-family:Georgia,serif; font-weight:800; font-size:1.08rem; }
        .brand-mark { display:grid; place-items:center; width:35px; height:35px; border-radius:10px; color:#3f2a0d; background:var(--gold); font-family:Georgia,serif; font-weight:900; }
        .nav-label { margin:30px 10px 10px; color:#d4a766; font-size:.68rem; font-weight:900; letter-spacing:.12em; text-transform:uppercase; }
        .nav { display:grid; gap:5px; }
        .nav-item { padding:10px 11px; border-radius:9px; color:#e8d6ca; text-decoration:none; font-size:.82rem; }
        .nav-item:hover, .nav-item:focus-visible { color:#fff; background:rgba(255,255,255,.08); outline:2px solid rgba(200,146,50,.65); outline-offset:2px; }
        .sidebar-footer { margin-top:auto; padding-top:20px; border-top:1px solid rgba(255,255,255,.12); }
        .main { min-width:0; }
        .topbar { display:flex; justify-content:space-between; align-items:center; gap:18px; padding:18px clamp(20px,4vw,52px); border-bottom:1px solid rgba(92,57,40,.12); background:rgba(255,253,249,.75); }
        .topbar h2 { margin:0; color:var(--accent); font-family:Georgia,serif; font-size:1.02rem; }
        .topbar p { margin:2px 0 0; color:var(--muted); font-size:.76rem; }
        .topbar-link { color:var(--accent); font-size:.78rem; font-weight:850; text-decoration:none; }
        .content { width:min(1040px,100%); margin:0 auto; padding:clamp(24px,5vw,58px) clamp(20px,4vw,52px) 70px; }
        .back-link { display:inline-flex; margin-bottom:22px; color:var(--accent); font-size:.8rem; font-weight:850; text-decoration:none; }
        .back-link:hover, .back-link:focus-visible { text-decoration:underline; outline:2px solid rgba(163,58,45,.3); outline-offset:4px; }
        .story-card { display:grid; grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr); overflow:hidden; border:1px solid var(--line); border-radius:22px; background:#fffdf9; box-shadow:0 24px 60px rgba(76,43,25,.11); }
        .story-visual { min-height:460px; background:linear-gradient(145deg,#e9d9c6,#f8f1e8); }
        .story-visual .enlargeable-image { height:100%; min-height:460px; }
        .story-visual img { width:100%; height:100%; min-height:460px; object-fit:cover; display:block; }
        .visual-empty { display:grid; min-height:460px; place-items:center; padding:30px; color:var(--muted); text-align:center; }
        .visual-empty span { display:block; margin-bottom:8px; color:var(--gold); font-size:2.7rem; }
        .story-copy { display:flex; flex-direction:column; padding:clamp(25px,5vw,54px); }
        .eyebrow { margin:0 0 9px; color:var(--gold); font-size:.7rem; font-weight:900; letter-spacing:.13em; text-transform:uppercase; }
        h1 { margin:0; color:var(--accent); font-family:Georgia,serif; font-size:clamp(2.1rem,5vw,4.3rem); line-height:1.02; }
        .shop-link { display:inline-flex; width:fit-content; margin-top:13px; color:var(--green); font-size:.85rem; font-weight:850; text-decoration:none; }
        .shop-link:hover, .shop-link:focus-visible { text-decoration:underline; }
        .pill-row { display:flex; flex-wrap:wrap; gap:8px; margin:26px 0 0; }
        .pill { display:inline-flex; padding:6px 10px; border-radius:999px; color:var(--green); background:rgba(61,111,85,.09); font-size:.74rem; font-weight:850; }
        .pill.price { color:#3f2a0d; background:rgba(200,146,50,.2); }
        .description { margin:28px 0 0; color:var(--muted); font-size:1.02rem; line-height:1.75; }
        .significance { margin-top:25px; padding:18px; border-left:4px solid var(--gold); border-radius:0 12px 12px 0; background:#fff5e5; }
        .significance strong { display:block; margin-bottom:5px; color:var(--accent); font-family:Georgia,serif; font-size:1.05rem; }
        .significance p { margin:0; color:var(--muted); }
        .action-row { display:flex; flex-wrap:wrap; gap:9px; margin-top:auto; padding-top:29px; }
        .button { display:inline-flex; min-height:43px; align-items:center; justify-content:center; padding:0 15px; border:1px solid var(--line); border-radius:10px; color:var(--ink); background:#fff; font-size:.8rem; font-weight:900; text-decoration:none; }
        .button.primary { border-color:var(--accent); color:#fff8f1; background:var(--accent); }
        .button:hover, .button:focus-visible { transform:translateY(-1px); box-shadow:0 8px 18px rgba(76,43,25,.12); outline:2px solid rgba(163,58,45,.3); outline-offset:2px; }
        .context { margin-top:18px; padding:19px 21px; border:1px solid var(--line); border-radius:14px; background:rgba(255,253,249,.75); }
        .context p { margin:0; color:var(--muted); font-size:.82rem; }
        .context strong { color:var(--accent); }
        @media (max-width:760px) { .shell { grid-template-columns:1fr; } .sidebar { padding:16px 18px; } .nav-label, .sidebar-footer { display:none; } .nav { display:flex; flex-wrap:wrap; margin-top:14px; } .nav-item { padding:7px 9px; } .topbar { align-items:start; } .story-card { grid-template-columns:1fr; } .story-visual, .story-visual .enlargeable-image, .story-visual img, .visual-empty { min-height:280px; height:280px; } }
    </style>
@endpush

@section('content')
<div class="content">
<a class="back-link" href="{{ route('heritage-shops.show', ['id' => $shop->id]) }}#food-menu">← Back to {{ $shop->shop_name }}</a>
                <article class="story-card">
                    <div class="story-visual">
                        @if ($foodItem->image_path)
                            <x-enlargeable-image
                                :src="route('heritage-shops.food-images.show', [$shop, $foodItem])"
                                :alt="$foodItem->name.' at '.$shop->shop_name"
                                :loading="null"
                                fetchpriority="high"
                            />
                        @else
                            <div class="visual-empty"><div><span>✦</span><strong>Food photo coming soon</strong><br>We are preserving the story first, then the perfect picture.</div></div>
                        @endif
                    </div>
                    <div class="story-copy">
                        <p class="eyebrow">Heritage food item</p>
                        <h1>{{ $foodItem->name }}</h1>
                        <a class="shop-link" href="{{ route('heritage-shops.show', ['id' => $shop->id]) }}#food-menu">Served at {{ $shop->shop_name }} ↗</a>
                        <div class="pill-row">
                            @if ($foodItem->category) <span class="pill">{{ $foodItem->category }}</span> @endif
                            @if ($foodItem->price) <span class="pill price">{{ $foodItem->price }}</span> @endif
                            @if ($foodItem->availability) <span class="pill">{{ $foodItem->availability }}</span> @endif
                        </div>
                        @if ($foodItem->description)
                            <p class="description">{{ $foodItem->description }}</p>
                        @endif
                        @if ($foodItem->heritage_significance)
                            <div class="significance"><strong>Why this dish matters</strong><p>{{ $foodItem->heritage_significance }}</p></div>
                        @endif
                        <div class="action-row">
                            <a class="button primary" href="{{ route('heritage-shops.show', ['id' => $shop->id]) }}#food-menu">Explore full menu</a>
                            @if ($shop->contact_number) <a class="button" href="tel:{{ preg_replace('/[^0-9+]/', '', $shop->contact_number) }}">Call the shop</a> @endif
                        </div>
                    </div>
                </article>
                <div class="context"><p><strong>Preservation note.</strong> This record is shown from the published HeritageShop catalog. Food heritage includes the people, techniques, memories, and community practices connected to what is served—not only the product itself.</p></div>
                <x-image-lightbox />
</div>
@endsection
