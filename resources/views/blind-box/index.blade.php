@extends('layouts.user')
@section('title', __('Blind Box'))
@section('user-topbar-title', __('Blind Box Recommendation'))
@section('user-topbar-subtitle', __('Reveal surprise heritage food suggestions matched to your taste.'))

@section('user-topbar-actions')
<a class="user-topbar-link" href="{{ route('heritage-shops.index') }}">{{ __('Heritage Shops') }}</a>
<a class="user-topbar-link" href="{{ route('foodtrails.index') }}">{{ __('Food Trail') }}</a>
<a class="user-topbar-link" href="{{ route('blind-box.favourites') }}">&#9825; {{ __('My Favourites') }}</a>
@endsection

@push('head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@endpush

@php
    $shops = $shops ?? [];
    $categories = $categories ?? ['Main Dishes', 'Desserts', 'Drinks'];
    $activeFilters = $activeFilters ?? ['category' => ''];
    $period = $period ?? 'night';
    $periodInfo = $periodInfo ?? ['key' => 'night', 'label' => 'Night', 'tag' => 'Dinner time', 'icon' => '🌙'];
    $alreadyDrew = $alreadyDrew ?? false;
    $currentDraw = $currentDraw ?? null;
    $totalInCatalog = $totalInCatalog ?? 0;
    $mysteryShops = $mysteryShops ?? [];
    $discoveryMode = $discoveryMode ?? 'shops';
    $discoveryItems = $discoveryItems ?? $shops;
    $discoveryShop = $discoveryShop ?? null;
@endphp

@push('styles')
<style>
        :root{--red:#8c1f1f;--red-dark:#691616;--cream:#f7efe4;--cream-2:#efe0c9;--text:#2f241d;--muted:#6f5845;--gold:#c98b16;--gold-light:#f7c948;--bg-start:#fcf7ef;--bg-end:#f7efe4}
        *{box-sizing:border-box}
        body{margin:0;font-family:"Segoe UI",Arial,sans-serif;background:linear-gradient(135deg,var(--bg-start ),var(--bg-end));color:var(--text);line-height:1.6}
        a{color:inherit;text-decoration:none}
        .page{max-width:1200px;margin:0 auto;padding:24px 18px 48px}
        .hero,.section{background:rgba(255,255,255,.92);border:1px solid rgba(140,31,31,.08);border-radius:24px;box-shadow:0 14px 40px rgba(69,34,18,.08);backdrop-filter:blur(10px)}
        .hero{padding:24px;display:grid;gap:20px}
        .hero-grid{display:grid;gap:20px;grid-template-columns:1.3fr .9fr;align-items:center}
        .eyebrow{display:inline-block;padding:6px 12px;background:rgba(201,139,22,.16);color:var(--gold);border-radius:999px;font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
        h1,h2,h3{font-family:Georgia,"Times New Roman",serif;margin:0 0 8px}
        h1{font-size:clamp(2rem,4vw,3.2rem);line-height:1.1}
        .lead{font-size:1rem;color:var(--muted);max-width:680px}
        .button-row{display:flex;gap:12px;flex-wrap:wrap;margin-top:16px}
        .btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:999px;font-weight:700;border:none;cursor:pointer}
        .btn-primary{background:var(--red);color:white}
        .btn-secondary{background:white;color:var(--red-dark);border:1px solid rgba(140,31,31,.18)}
        .illustration{min-height:260px;border-radius:20px;background:linear-gradient(135deg,var(--red-dark),var(--red));position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;color:white;padding:24px}
        .illustration-card{position:relative;z-index:1;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.22);padding:20px;border-radius:18px;width:100%}
        .section{padding:28px;margin-top:24px}

        /* --- SPARKLE ANIMATIONS --- */
        .sparkle-particle {
            position: absolute;
            pointer-events: none;
            font-size: 0.8rem;
            opacity: 0.3;
            animation: float-sparkle 6s infinite ease-in-out;
        }
        .sparkle-particle:nth-child(2) { animation-delay: 2s; font-size: 1.2rem; opacity: 0.2; }
        .sparkle-particle:nth-child(3) { animation-delay: 4s; font-size: 0.6rem; opacity: 0.4; }
        .sparkle-particle:nth-child(4) { animation-delay: 1s; font-size: 0.9rem; opacity: 0.25; }
        .sparkle-particle:nth-child(5) { animation-delay: 3s; font-size: 1rem; opacity: 0.2; }

        @keyframes float-sparkle {
            0% { transform: translateY(0) scale(0.5); opacity: 0; }
            30% { opacity: 0.4; }
            70% { opacity: 0.4; }
            100% { transform: translateY(-100px) scale(0); opacity: 0; }
        }

        /* --- CAROUSEL TRANSITIONS --- */
        .carousel-content {
            transition: opacity 0.6s ease;
        }
        .carousel-content.fade-out {
            opacity: 0;
        }

        /* --- BLIND BOX WOW FACTOR UPGRADE --- */
        .mystery-guide {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin: 25px 0;
            padding: 20px;
            background: linear-gradient(90deg, rgba(201,139,22,0.1), transparent, rgba(201,139,22,0.1));
            border-top: 1px solid rgba(201,139,22,0.2);
            border-bottom: 1px solid rgba(201,139,22,0.2);
            border-radius: 12px;
        }
        .guide-step { display: flex; align-items: center; gap: 10px; font-weight: 700; color: var(--red-dark); font-size: 1.1rem; }
        .guide-step span { width: 28px; height: 28px; background: var(--gold); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; box-shadow: 0 4px 10px rgba(201,139,22,0.3); }

        .blind-box-card {
            margin-top: 12px;
            padding: 50px 20px;
            border-radius: 35px;
            background: radial-gradient(circle at center, #fffaf2 0%, #f7efe4 70%, #efe0c9 100%);
            border: 2px solid rgba(201,139,22,0.25);
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 0 60px rgba(201,139,22,0.15), 0 20px 50px rgba(69,34,18,0.1);
        }

        .box-stage {
            position: relative;
            margin: 20px auto;
            width: 320px;
            height: 340px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mystery-glow {
            position: absolute;
            width: 220px;
            height: 220px;
            background: var(--gold);
            filter: blur(90px);
            opacity: 0.35;
            border-radius: 50%;
            animation: glow-pulse 3s infinite ease-in-out;
            z-index: 1;
        }
        @keyframes glow-pulse { 0%, 100% { transform: scale(1); opacity: 0.35; } 50% { transform: scale(1.4); opacity: 0.6; } }

        .ribbon-ring { position: absolute; inset: -40px; pointer-events: none; z-index: 2; }
        .ribbon-ring span { position: absolute; border-radius: 50%; border: 2px dashed rgba(140,31,31,0.2); animation: ribbon-spin 22s linear infinite; }
        .ribbon-ring span:nth-child(1) { inset: 0; animation-duration: 26s; }
        .ribbon-ring span:nth-child(2) { inset: 20px; animation-duration: 18s; animation-direction: reverse; border-color: rgba(201,139,22,0.3); }
        .ribbon-ring span:nth-child(3) { inset: 40px; animation-duration: 12s; border-style: solid; border-width: 1px; border-color: rgba(140,31,31,0.1); }
        @keyframes ribbon-spin { to { transform: rotate(360deg); } }

        .box {
            position: relative;
            z-index: 10;
            width: 230px;
            height: 230px;
            cursor: pointer;
            user-select: none;
            transition: transform 0.3s ease;
        }
        .box:hover { transform: scale(1.05); }

        .box-lid {
            position: absolute;
            top: -8px;
            left: -12px;
            right: -12px;
            height: 55px;
            background: linear-gradient(180deg, #b52f2f, #8c1f1f);
            border-radius: 18px 18px 6px 6px;
            box-shadow: 0 8px 15px rgba(0,0,0,0.25);
            z-index: 12;
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        .box-body {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 190px;
            background: linear-gradient(135deg, #8c1f1f, #691616);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            z-index: 11;
            box-shadow: 0 20px 45px rgba(105,22,22,0.45);
            border: 2px solid rgba(255,255,255,0.1);
        }
        .box-body::after {
            content: "";
            position: absolute;
            inset: 10px;
            border: 2px dashed rgba(255,255,255,0.2);
            border-radius: 8px;
            pointer-events: none;
        }
        
        .box.opening .box-lid { transform: translateY(-180px) rotateX(70deg) rotateZ(20deg) scale(0.8); opacity: 0; }
        .box.opening .box-body { animation: box-rumble 0.15s infinite; }
        @keyframes box-rumble {
            0% { transform: translate(0,0); }
            25% { transform: translate(-3px, 2px); }
            50% { transform: translate(3px, -2px); }
            75% { transform: translate(-2px, -3px); }
            100% { transform: translate(2px, 3px); }
        }

        .light-beam {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, white 0%, var(--gold-light) 40%, transparent 70%);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            z-index: 5;
            opacity: 0;
            pointer-events: none;
        }
        .light-beam.active { animation: beam-burst 1.8s ease-out forwards; }
        @keyframes beam-burst {
            0% { width: 0; height: 0; opacity: 1; }
            60% { width: 900px; height: 900px; opacity: 0.9; }
            100% { width: 1200px; height: 1200px; opacity: 0; }
        }

        .result {
            display: none;
            margin-top: 30px;
            padding: 35px;
            background: white;
            border-radius: 30px;
            border: 3px solid var(--gold);
            box-shadow: 0 30px 60px rgba(69,34,18,0.2);
            position: relative;
            z-index: 20;
        }
        .result.show { display: block; animation: result-pop 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes result-pop { from { transform: scale(0.7) translateY(50px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }

        .renewal-tag {
            display: inline-block;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 900;
            font-size: 1.1rem;
            letter-spacing: 0.1em;
            color: white;
            background: linear-gradient(90deg, var(--red-dark), var(--gold), var(--red-dark));
            background-size: 200% auto;
            animation: shimmer-gold 2s linear infinite;
            box-shadow: 0 5px 15px rgba(140,31,31,0.3);
        }
        @keyframes shimmer-gold { to { background-position: 200% center; } }

        /* Discovery Grid Styling */
        .shop-grid{display:grid;gap:25px;margin-top:25px;grid-template-columns:repeat(2,minmax(0,1fr))}
        .item-card{background:#ffffff;border:1px solid rgba(140,31,31,0.08);border-radius:24px;overflow:hidden;box-shadow:0 12px 30px rgba(0,0,0,0.06);transition:all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1)}
        .item-card:hover{transform:translateY(-12px);box-shadow:0 25px 50px rgba(140,31,31,0.15)}
        .item-media{position:relative;width:100%;height:240px;overflow:hidden}
        .item-media img{width:100%;height:100%;object-fit:cover;transition:transform 0.5s ease}
        .item-card:hover .item-media img { transform: scale(1.1); }
        .item-body{padding:24px}
        .discovery-description {
            display: -webkit-box;
            min-height: 4.5em;
            margin: 0 0 20px;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
            line-height: 1.5;
        }
        .food-card .item-media { height: 210px; }
        
        .pagination-container { 
            margin-top: 45px; 
            display: flex; 
            justify-content: center; 
        }
        
        .pagination-container nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
        }

        .pagination-container .pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .pagination-container .pagination li {
            display: inline-block;
        }

        .pagination-container .pagination a,
        .pagination-container .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            height: 38px;
            padding: 0 12px;
            border: 1px solid rgba(140,31,31,0.15);
            border-radius: 9px;
            color: var(--text);
            background: #fff;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .pagination-container .pagination a:hover {
            border-color: var(--gold);
            background: #fdf6ea;
            transform: translateY(-1px);
        }

        .pagination-container .pagination span[aria-current="page"] {
            color: #fff;
            background: var(--red-dark);
            border-color: var(--red-dark);
        }

        .pagination-container .pagination .disabled span {
            opacity: 0.4;
            cursor: not-allowed;
        }

        @media (max-width: 600px) {
            .pagination-container .pagination {
                gap: 4px;
            }
            .pagination-container .pagination a,
            .pagination-container .pagination span {
                min-width: 32px;
                height: 32px;
                padding: 0 8px;
                font-size: 0.8rem;
            }
        }
        
        .filter-row select {
            border: 2px solid var(--gold);
            border-radius: 18px;
            padding: 14px 28px;
            background: white;
            color: var(--text);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(201,139,22,0.1);
            transition: all 0.3s ease;
        }
        .filter-row select:hover { border-color: var(--red); transform: translateY(-2px); }

        .filter-reset {
            background: none;
            border: none;
            color: var(--red);
            font-weight: 700;
            cursor: pointer;
            font-size: 1rem;
            padding: 8px 16px;
            margin-left: 15px;
            transition: color 0.2s;
        }
        .filter-reset:hover { color: var(--red-dark); text-decoration: underline; }

        .image-placeholder {
            width: 100%;
            height: 240px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #efe0cf, #fff8ef);
            color: var(--muted);
            font-weight: 600;
            font-size: 0.9rem;
            text-align: center;
            padding: 20px;
        }

        .meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #fdf6ea;
            border: 1px solid rgba(201,139,22,0.3);
            border-radius: 999px;
            font-weight: 700;
            color: var(--red-dark);
        }

        /* --- ENHANCED ERROR DESIGN --- */
        .draw-error-box {
            margin: 20px auto;
            max-width: 500px;
            padding: 30px;
            background: #fff5f5;
            border: 2px dashed #feb2b2;
            border-radius: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(245, 101, 101, 0.1);
            animation: error-in 0.4s ease-out;
        }
        @keyframes error-in { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .error-icon { font-size: 3.5rem; margin-bottom: 15px; display: block; }
        .error-title { font-size: 1.4rem; font-weight: 800; color: #c53030; margin-bottom: 10px; display: block; }
        .error-text { color: #9b2c2c; font-size: 1rem; margin-bottom: 20px; display: block; line-height: 1.5; }
        .error-action {
            display: inline-block;
            padding: 12px 25px;
            background: #c53030;
            color: white;
            border-radius: 50px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .error-action:hover { background: #9b2c2c; transform: scale(1.05); }

        @media(max-width:800px){
            .page {
                width: 100%;
                padding: 16px 12px 32px;
            }
            .hero,
            .section {
                border-radius: 18px;
            }
            .hero-grid {
                grid-template-columns: 1fr;
            }
            .hero {
                padding: 16px;
            }
            .hero .illustration {
                min-height: 180px;
            }
            .shop-grid{grid-template-columns:1fr}
            .mystery-guide { flex-direction: column; align-items: flex-start; gap: 12px; }
            .box-stage { width: 260px; height: 280px; }
            .box { width: 190px; height: 190px; }
            .filter-row {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .filter-row select {
                width: 100%;
            }
            .filter-reset {
                margin-left: 0 !important;
                text-align: center;
            }
            .result .renewal-tag {
                font-size: 0.9rem;
                padding: 8px 16px;
            }
            .result > div {
                flex-direction: column !important;
                align-items: center !important;
            }
            .result > div > div:first-child {
                width: 200px !important;
                height: 200px !important;
            }
            .result > div > div:last-child {
                min-width: auto !important;
                width: 100%;
                text-align: center;
            }
            .result .cta-row,
            .result .favourite-action-row {
                width: 100%;
            }
            .result .cta-row a,
            .result .favourite-action-row button {
                width: 100%;
                min-width: 0;
                margin: 0;
            }
            .result .meta-chip {
                max-width: 100%;
                overflow-wrap: anywhere;
            }
            .period-banner {
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
            }
            .illustration-card .carousel-emoji {
                font-size: 2.5rem !important;
            }
            .illustration-card .carousel-title {
                font-size: 0.95rem !important;
            }
            .illustration-card .carousel-desc {
                font-size: 0.75rem !important;
            }
        }

        @media(max-width:420px){
            .hero,
            .section {
                padding: 18px 14px;
            }
            h1 { font-size: 1.85rem; }
            .hero .button-row,
            .hero .button-row .btn {
                width: 100%;
            }
            .hero .button-row .btn {
                min-height: 44px;
            }
            .mystery-guide {
                padding: 14px;
            }
            .blind-box-card {
                padding: 32px 10px;
                border-radius: 24px;
            }
            .box-stage {
                width: 220px;
                height: 240px;
            }
            .box {
                width: 170px;
                height: 170px;
            }
            .box-body {
                height: 140px;
            }
            .box-body > div:first-child {
                font-size: 4rem !important;
            }
            .box-body > div:last-child {
                padding: 5px 14px !important;
                font-size: .72rem !important;
            }
            .result {
                padding: 20px 12px;
                border-radius: 22px;
            }
            .result > div > div:first-child {
                width: min(100%, 200px) !important;
                height: auto !important;
                aspect-ratio: 1;
            }
            .result h3 {
                font-size: 1.7rem !important;
            }
            .result p {
                font-size: 1rem !important;
            }
        }
    </style>
@endpush

@section('content')
<div class="page">

    <section class="hero">
        <div class="hero-grid">
            <div>
                <span class="eyebrow">{{ __('WarisanMakan • Heritage Discovery') }}</span>
                <h1>{{ __("Preserve Malaysia's culinary heritage through every bite.") }}</h1>
                <p class="lead">{{ __('Discover forgotten food stories, celebrate traditional vendors, and let every visit feel like a cultural expedition.') }}</p>
                <div class="button-row">
                    <a class="btn btn-primary" href="#blind-box">✨ {{ __('Try Blind Box') }}</a>
                    <a class="btn btn-secondary" href="{{ route('blind-box.favourites') }}" title="{{ __('View Favourites') }}" aria-label="{{ __('View Favourites') }}">&#9825; {{ __('View Favourites') }}</a>
                </div>
            </div>
            <div class="illustration">
                <div class="illustration-card">
                    <span style="position: absolute; top: 12px; left: 12px; background: rgba(201,139,22,0.2); color: #f7c948; padding: 4px 12px; border-radius: 999px; font-size: 0.55rem; 
                    font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1); z-index: 5;">
                        ✦ WarisanMakan
                    </span> 

                    <span class="sparkle-particle">✦</span>
                    <span class="sparkle-particle">✦</span>
                    <span class="sparkle-particle">✦</span>
                    <span class="sparkle-particle">✦</span>
                    <span class="sparkle-particle">✦</span>

                    <!-- Carousel Content -->
                    <div style="text-align: center; position: relative; z-index: 2;">
                        <div id="carousel-emoji" class="carousel-content carousel-emoji" style="display: block; transition: opacity 0.5s;">
                            <i class="fas fa-bowl-rice" style="font-size: 3.5rem; color: #f7c948;"></i>
                        </div>
                        <div id="carousel-title" class="carousel-content carousel-title" style="font-weight: 800; font-size: 1.1rem; color: #f7c948; margin: 8px 0 4px; transition: opacity 0.5s;">
                            Nasi Lemak
                        </div>
                        <div id="carousel-desc" class="carousel-content carousel-desc" style="font-size: 0.85rem; opacity: 0.9; max-width: 260px; margin: 0 auto; transition: opacity 0.5s;">
                            Fragrant coconut rice with sambal, anchovies, and egg.
                        </div>
                        <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.15);">
                            <span id="mystery-preview" style="font-size: 0.75rem; opacity: 0.7; transition: opacity 0.5s;">
                                🎁 {{ __('Try the Blind Box to discover more!') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="blind-box" class="section">
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="font-size: 2.8rem; color: var(--red-dark); font-family: Georgia, serif;">🎁 {{ __('The Heritage Blind Box') }}</h2>
            <p class="lead" style="margin: 0 auto; font-size: 1.2rem;">{{ __('Feeling adventurous? Let fate decide your next heritage meal.') }}</p>
        </div>

        <div class="mystery-guide">
            <div class="guide-step"><span>1</span> {{ __('Pick Category') }}</div>
            <div class="guide-step"><span>2</span> {{ __('Tap the Box') }}</div>
            <div class="guide-step"><span>3</span> {{ __('Enjoy Surprise!') }}</div>
        </div>

        <div class="period-banner animate__animated animate__fadeIn">
            <span class="period-icon">{{ $periodInfo['icon'] }}</span>
            <span class="period-text">
                {{ __('It is currently') }}
                <strong>{{ __($periodInfo['label']) }}</strong>
                &nbsp;·&nbsp;
                <em>({{ __($periodInfo['tag']) }})</em>
            </span>
            <span class="period-rule">{{ __('One surprise draw per period') }}</span>
        </div>

        <!-- FILTER ROW – WITHOUT FORM (no page refresh) -->
        <div class="filter-row" style="justify-content: center; margin-bottom: 30px;">
            <select id="category-filter" name="category">
                <option value="">{{ __('All food categories') }}</option>
                @foreach($categories as $option)
                    <option value="{{ $option }}" @selected($activeFilters['category'] === $option)>
                        {{ __($option) }}
                    </option>
                @endforeach
            </select>

            @if($activeFilters['category'] !== '')
                <button id="reset-filter-btn" class="filter-reset">{{ __('Reset filter') }}</button>
            @endif
        </div>

        <div class="blind-box-card">
            <div class="mystery-glow"></div>
            <div id="light-beam" class="light-beam"></div>

            <!-- ENHANCED ERROR STATE -->
            <div id="draw-error" class="draw-error-box" style="display: none; position: relative; z-index: 30;">
                <span class="error-icon">🏮</span>
                <span class="error-title">{{ __('No Shops Found') }}</span>
                <span id="draw-error-text" class="error-text"> {{ __('No heritage shops match your current category in our curated pool.') }}</span>
                <button id="reset-error-btn" class="error-action">{{ __('Reset Filters & Try Again') }}</button>
            </div>

            <div id="box-container" class="box-stage">
                <div class="ribbon-ring"><span></span><span></span><span></span></div>
                <div class="sparkles"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>

                <div id="box" class="box animate__animated animate__pulse animate__infinite animate__slow"
                     data-category="{{ $activeFilters['category'] }}"
                     @if($alreadyDrew) data-disabled="1" @endif>
                    <div class="box-lid"></div>
                    <div class="box-body">
                        <div style="font-size: 5.5rem; font-weight: 800; text-shadow: 0 8px 20px rgba(0,0,0,0.35);">
                            {{ $alreadyDrew ? '✓' : '?' }}
                        </div>
                        <div style="letter-spacing: 0.35em; font-weight: 900; background: rgba(255,255,255,0.25); padding: 6px 25px; border-radius: 50px; font-size: 0.9rem;">
                            {{ $alreadyDrew ? __('OPENED') : __('TAP TO OPEN') }}
                        </div>
                    </div>
                </div>
            </div>

            <div id="result" class="result"></div>
        </div>
    </section>

    <section id="discover" class="section">
        <h2 style="font-family: Georgia, serif;">
            🏮 {{ $discoveryMode === 'foods' ? __('Recommended Food Discoveries') : __('Heritage Shop Discovery') }}
        </h2>
        <p class="lead">
            @if($discoveryMode === 'foods')
                {{ __('Recommended foods from your latest Blind Box discovery at :shop.', ['shop' => $discoveryShop->shop_name]) }}
            @else
                {{ __("Explore the full collection of Malaysia's culinary gems.") }}
            @endif
        </p>

        @if($discoveryItems->isEmpty())
            <div class="empty-message">
                <p><strong>{{ $discoveryMode === 'foods' ? __('No recommended foods found for this shop.') : __('Catalog is currently empty.') }}</strong></p>
                <p>{{ $discoveryMode === 'foods' ? __('Please check the shop details for more heritage information.') : __('We are gathering more heritage stories. Please check back later!') }}</p>
            </div>
        @else
            <div class="shop-grid">
                @foreach($discoveryItems as $item)
                    @if($discoveryMode === 'foods')
                        <article class="item-card food-card">
                            <div class="item-media">
                                @if($item->image_path)
                                    <img src="{{ route('heritage-shops.food-images.show', [$discoveryShop, $item]) }}" alt="{{ $item->name }}" loading="lazy">
                                @else
                                    <div class="image-placeholder">{{ __('No image available') }}</div>
                                @endif
                            </div>
                            <div class="item-body">
                                <span class="tag">{{ $item->category ?: __('Heritage Food') }}</span>
                                <h3 style="font-size: 1.4rem; color: var(--red-dark); margin-bottom: 12px;">{{ $item->name }}</h3>
                                <p class="muted discovery-description" style="font-size: 0.95rem;">{{ $item->description ?: __('A heritage food recommendation from your Blind Box discovery.') }}</p>
                                <div class="item-meta">
                                    @if($item->availability)
                                        <span class="state-chip">{{ $item->availability }}</span>
                                    @endif
                                    <a class="btn btn-secondary" href="{{ route('heritage-shops.food-items.show', [$discoveryShop, $item]) }}">{{ __('View Food Details') }}</a>
                                </div>
                            </div>
                        </article>
                    @else
                        @php($shop = $item)
                        <article class="item-card">
                        <div class="item-media">
                            @if(!empty($shop['image']))
                                <img src="{{ $shop['image'] }}" alt="{{ $shop['name'] ?? 'Heritage Shop' }}" loading="lazy">
                            @else
                                <div class="image-placeholder">{{ __('No image available') }}</div>
                            @endif
                            @if(!empty($shop['year']) && $shop['year'] !== 'Heritage')
                                <span class="year-badge" style="font-size: 0.9rem; padding: 8px 15px; position: absolute; bottom: 15px; right: 15px; background: var(--red-dark); color: white; border-radius: 50px;">Est. {{ $shop['year'] }}</span>
                            @endif
                        </div>
                        <div class="item-body">
                            <span class="tag">{{ $shop['category'] ?? __('Heritage') }}</span>
                            <h3 style="font-size: 1.4rem; color: var(--red-dark); margin-bottom: 12px;">{{ $shop['name'] ?? '' }}</h3>
                            <p class="muted discovery-description" style="font-size: 0.95rem;">{{ $shop['description'] ?? '' }}</p>
                            <div class="item-meta">
                                <span class="state-chip" style="background: var(--bg-start); border: 1px solid rgba(140,31,31,0.15);">📍 {{ $shop['state'] ?? __('Malaysia') }}</span>
                                <span style="font-weight: 600; color: var(--muted);">
                                    {{ __('Since') }} {{ $shop['year'] ?? __('Heritage') }}
                                </span>
                            </div>
                        </div>
                    </article>
                    @endif
                @endforeach
            </div>

            @if($discoveryItems->hasPages())
                <div class="pagination-container">
                    {{ $discoveryItems->onEachSide(2)->links('pagination::bootstrap-4') }}
                </div>
            @endif
        @endif
    </section>

</div>

<script>
    const BLIND_BOX_CONFIG = {
        drawUrl: '{{ route('blind-box.draw') }}',
        csrfToken: '{{ csrf_token() }}',
        heritageShopsUrl: '{{ route('heritage-shops.index') }}',
        // CHANGED: URL template for a specific shop's detail page. We can't call
        // route('heritage-shops.show', $id) with a real ID yet (we don't know
        // which shop will be revealed), so we generate it with a placeholder
        // and swap the placeholder for the real ID at render time in JS.
        heritageShopDetailUrlTemplate: '{{ route('heritage-shops.show', ['id' => '__SHOP_ID__']) }}',
        foodtrailUrl: '{{ url('/foodtrails') }}',
        initialDraw: @json($currentDraw),
        alreadyDrew: @json($alreadyDrew),
        periodKey: @json($periodInfo['key']),
        favouritesStoreUrl: '{{ route('blind-box.favourites.store') }}',
        foodDetailUrlTemplate: '{{ route('heritage-shops.food-items.show', ['heritageShop' => '__SHOP_ID__', 'foodItem' => '__FOOD_ID__']) }}',
    };

    const BLIND_BOX_TEXT = {
        surpriseDiscoveryUnlocked: @json(__('Surprise Discovery Unlocked')),
        heritageShop: @json(__('Heritage Shop')),
        heritage: @json(__('Heritage')),
        malaysia: @json(__('Malaysia')),
        estimated: @json(__('Est.')),
        noDescription: @json(__('No description available.')),
        category: @json(__('Category')),
        state: @json(__('State')),
        whatNext: @json(__('What would you like to do next?')),
        exploreFoodTrails: @json(__('Explore Food Trails')),
        browseHeritageShops: @json(__('Browse Heritage Shops')),
        openedDuring: @json(__('You opened this Blind Box during the :period period. Come back next period for another surprise!')),
        current: @json(__('current')),
        tryAgainLater: @json(__('Try Again Later')),
        tryLater: @json(__('TRY LATER')),
        somethingWentWrong: @json(__('Something went wrong. Please try again.')),
        oops: @json(__('Oops')),
        tryAgain: @json(__('TRY AGAIN')),
        opened: @json(__('OPENED')),
        surpriseRevealed: @json(__('SURPRISE REVEALED')),
        exploreTrails: @json(__('Explore Trails')),
        viewDetails: @json(__('View Details')),
        discoveredGem: @json(__('You discovered this gem during the :period period. Come back later for a new surprise!')),
        heritage: @json(__('Heritage')),
        discoverHeritageShop: @json(__('Discover a heritage shop today!')),
        tryBlindBoxDiscover: @json(__('Try the Blind Box to discover more!')),
        viewFavourites: @json(__('View Favourites')),
        saveFavourite: @json(__('Save to favourites')),
        savedFavourite: @json(__('Saved to favourites')),
        removeFavourite: @json(__('Remove from favourites')),
        recommendedFoodDiscoveries: @json(__('Recommended Food Discoveries')),
        recommendedFoodsAt: @json(__('Recommended foods from your latest Blind Box discovery at :shop.')),
        noRecommendedFoods: @json(__('No recommended foods found for this shop.')),
        heritageFood: @json(__('Heritage Food')),
        foodRecommendation: @json(__('A heritage food recommendation from your Blind Box discovery.')),
        viewFoodDetails: @json(__('View Food Details')),
        noImageAvailable: @json(__('No image available')),
    };

    const BLIND_BOX_PERIODS = {
        morning: @json(__('Morning')),
        afternoon: @json(__('Afternoon')),
        evening: @json(__('Evening')),
        night: @json(__('Night')),
    };

    document.addEventListener('DOMContentLoaded', function () {
        // --- CAROUSEL DATA (FontAwesome icons) ---
        const carouselData = [
            {
                icon: '<i class="fas fa-bowl-rice" style="font-size: 3.5rem; color: #f7c948;"></i>',
                title: @json(__('Nasi Lemak')),
                desc: @json(__('Fragrant coconut rice with sambal, anchovies, and egg.'))
            },
            {
                icon: '<i class="fas fa-drumstick-bite" style="font-size: 3.5rem; color: #f7c948;"></i>',
                title: @json(__('Rendang')),
                desc: @json(__('Slow-cooked beef in rich coconut milk and spices.'))
            },
            {
                icon: '<i class="fas fa-utensils" style="font-size: 3.5rem; color: #f7c948;"></i>',
                title: @json(__('Laksa')),
                desc: @json(__('Spicy noodle soup with a creamy coconut broth.'))
            },
            {
                icon: '<i class="fas fa-utensils" style="font-size: 3.5rem; color: #f7c948;"></i>',
                title: @json(__('Char Kuey Teow')),
                desc: @json(__('Wok-fried flat rice noodles with prawns and cockles.'))
            },
            {
                icon: '<i class="fas fa-mug-saucer" style="font-size: 3.5rem; color: #f7c948;"></i>',
                title: @json(__('Kopi & Roti Bakar')),
                desc: @json(__('Classic kopitiam breakfast – toast, butter, and kaya.'))
            },
                {
                    icon: '<i class="fas fa-utensils" style="font-size: 3.5rem; color: #f7c948;"></i>',
                title: @json(__('Satay')),
                desc: @json(__('Grilled skewered meat with rich peanut sauce.'))
            },
        ];

        // --- MYSTERY SHOPS (from the backend – real shops) ---
const mysteryShops = @json(array_map(function($shop) { 
    return ($shop['name'] ?? __('Heritage Shop')) 
        . ' · ' 
        . __('Est.') 
        . ' ' 
        . ($shop['year'] ?? ''); 
}, $mysteryShops ?? []));

        // Fallback if no shops exist
        if (mysteryShops.length === 0) {
            mysteryShops.push('✨ ' + BLIND_BOX_TEXT.discoverHeritageShop);
        }

        const emojiEl = document.getElementById('carousel-emoji');
        const titleEl = document.getElementById('carousel-title');
        const descEl = document.getElementById('carousel-desc');
        const mysteryEl = document.getElementById('mystery-preview');

        let idx = 0;
        let mysteryIdx = 0;
        let isFading = false;

        function updateCarousel() {
            if (isFading) return;
            const item = carouselData[idx];
            
            isFading = true;
            emojiEl.style.opacity = '0';
            titleEl.style.opacity = '0';
            descEl.style.opacity = '0';

            setTimeout(() => {
                // Update food
                emojiEl.innerHTML = item.icon;
                titleEl.textContent = item.title;
                descEl.textContent = item.desc;

                // Update mystery preview EVERY cycle
                if (mysteryShops.length > 0) {
                    mysteryEl.textContent = '🎁 ' + mysteryShops[mysteryIdx % mysteryShops.length];
                } else {
                    mysteryEl.textContent =  '🎁 ' + BLIND_BOX_TEXT.tryBlindBoxDiscover;
                }
                mysteryIdx++;

                // Fade in
                emojiEl.style.opacity = '1';
                titleEl.style.opacity = '1';
                descEl.style.opacity = '1';

                idx = (idx + 1) % carouselData.length;
                isFading = false;
            }, 500);
        }

        // Initial opacity
        emojiEl.style.opacity = '1';
        titleEl.style.opacity = '1';
        descEl.style.opacity = '1';

        // Start carousel
        setInterval(updateCarousel, 4000);

        // --- FILTER HANDLING (no page reload) ---
        const categoryFilter = document.getElementById('category-filter');
        const resetBtn = document.getElementById('reset-filter-btn');

        function updateUrlCategory(category) {
            const url = new URL(window.location.href);
            if (category) {
                url.searchParams.set('category', category);
            } else {
                url.searchParams.delete('category');
            }
            window.history.pushState({ category }, '', url.toString());
        }

        if (categoryFilter) {
            categoryFilter.addEventListener('change', function () {
                updateUrlCategory(this.value);
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function (e) {
                e.preventDefault();
                categoryFilter.value = '';
                updateUrlCategory('');
            });
        }

        // --- BLIND BOX DRAW LOGIC ---
        const box = document.getElementById('box');
        const boxContainer = document.getElementById('box-container');
        const result = document.getElementById('result');
        const beam = document.getElementById('light-beam');

        // --- RESET ERROR STATE ---
        const errorResetBtn = document.getElementById('reset-error-btn');
        if (errorResetBtn) {
            errorResetBtn.addEventListener('click', function () {
                categoryFilter.value = '';
                updateUrlCategory('');
                document.getElementById('draw-error').style.display = 'none';
                boxContainer.style.display = 'flex';
                box.dataset.disabled = '0';
                box.classList.remove('opening');
                box.classList.add('animate__pulse', 'animate__infinite');
                result.className = 'result';
                result.innerHTML = '';
                beam.classList.remove('active');
            });
        }

        if (!box || !result) return;

        function confettiBurst() {
            const colors = ['#8c1f1f', '#c98b16', '#f7c948', '#ffffff', '#ff6b6b'];
            for (let i = 0; i < 70; i++) {
                const p = document.createElement('div');
                const size = 6 + Math.random() * 10;
                Object.assign(p.style, {
                    position: 'fixed', left: '50%', top: '50%', width: size + 'px', height: size + 'px',
                    background: colors[Math.floor(Math.random() * colors.length)],
                    borderRadius: Math.random() > .5 ? '50%' : '2px', pointerEvents: 'none', zIndex: 9999
                });
                document.body.appendChild(p);
                const angle = Math.random() * Math.PI * 2;
                const velocity = 350 + Math.random() * 450;
                const dx = Math.cos(angle) * velocity;
                const dy = Math.sin(angle) * velocity - 250;
                p.animate([
                    { transform: 'translate(0,0) rotate(0deg) scale(1)', opacity: 1 },
                    { transform: 'translate(' + dx + 'px, ' + (dy + 600) + 'px) rotate(' + (Math.random() * 1080) + 'deg) scale(0)', opacity: 0 }
                ], { duration: 1800, easing: 'cubic-bezier(.1,.8,.3,1)' }).onfinish = () => p.remove();
            }
        }

        // --- RENDER RESULT WITH IMAGE FALLBACK (FIXED) ---
        function renderResult(shop, periodKey, { animateIn = false } = {}) {
            result.className = 'result show';
            const shopName = shop.name || shop.shop_name || 'Heritage Shop';
            const foodTrailUrl = `${BLIND_BOX_CONFIG.foodtrailUrl}?search=${encodeURIComponent(shopName)}`;

            // CHANGED: Resolve the actual revealed shop's detail page URL.
            // shop.shop_source_id comes from BlindBoxDraw::toDrawArray() (persisted
            // from $selectedShop['source_id'] at draw time), which is the real
            // HeritageShop DB id. If a draw has no linked DB shop (e.g. it came
            // from the static sample catalog with no source_id), fall back to
            // the general listing instead of a broken link — never a hardcoded
            // or default shop.
            const shopId = shop.id ?? null;
            const detailUrl = shopId
                ? BLIND_BOX_CONFIG.heritageShopDetailUrlTemplate.replace('__SHOP_ID__', shopId)
                : BLIND_BOX_CONFIG.heritageShopsUrl;

            result.innerHTML = `
                <div style="text-align: center; margin-bottom: 25px;">
                    <span class="renewal-tag">✨ ${BLIND_BOX_TEXT.surpriseRevealed} ✨</span>
                </div>
                <div style="display: flex; gap: 30px; align-items: start; flex-wrap: wrap; justify-content: center;">
                    <div style="position: relative; width: 300px; height: 300px; flex-shrink: 0;">
                        ${shop.image ? 
                            `<img src="${shop.image}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 25px; border: 5px solid var(--gold); box-shadow: 0 15px 35px rgba(0,0,0,0.2);">` :
                            `<div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f1e5d7; border-radius: 25px; border: 5px solid var(--gold); color: var(--muted); font-weight: 600; font-size: 1rem; text-align: center; padding: 20px;">No image available</div>`
                        }
                        <span class="year-badge" style="font-size: 0.9rem; padding: 8px 15px; position: absolute; bottom: 15px; right: 15px; background: var(--red-dark); color: white; border-radius: 50px;">Est. ${shop.year || 'Heritage'}</span>
                    </div>
                    <div style="flex: 1; min-width: 320px;">
                        <h3 style="font-size: 2.4rem; color: var(--red-dark); margin-bottom: 15px; font-family: Georgia, serif;">${shopName}</h3>
                        <p style="font-size: 1.15rem; color: var(--muted); margin-bottom: 25px; line-height: 1.6;">${shop.description || BLIND_BOX_TEXT.noDescription}</p>
                        <div style="display: flex; gap: 12px; margin-bottom: 30px;">
                            <span class="meta-chip">🍱 ${shop.category || BLIND_BOX_TEXT.heritage}</span>
                            <span class="meta-chip">📍 ${shop.state || BLIND_BOX_TEXT.malaysia}</span>
                        </div>
                        <div class="cta-row" style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <a class="cta-btn cta-primary" style="padding: 16px 30px; font-size: 1.1rem; border-radius: 15px; background: var(--red-dark); color: white; font-weight: 700;" href="${foodTrailUrl}">${BLIND_BOX_TEXT.exploreTrails}</a>
                            <a class="cta-btn cta-outline" style="padding: 16px 30px; font-size: 1.1rem; border-radius: 15px; border: 2px solid var(--red-dark); color: var(--red-dark); font-weight: 700;" href="${detailUrl}">${BLIND_BOX_TEXT.viewDetails}</a>
                        </div>
                        ${shop.draw_id ? `
                            <div class="favourite-action-row" style="margin-top: 14px;">
                                <button type="button" class="cta-btn cta-outline favourite-toggle" data-draw-id="${shop.draw_id}" data-favourited="${shop.is_favourited ? '1' : '0'}" style="padding: 12px 20px; font-size: 1rem; border-radius: 15px; border: 2px solid var(--red-dark); color: var(--red-dark); font-weight: 700; cursor: pointer;">
                                    ${shop.is_favourited ? '&#9829;' : '&#9825;'} <span class="favourite-label">${shop.is_favourited ? BLIND_BOX_TEXT.removeFavourite : BLIND_BOX_TEXT.saveFavourite}</span>
                                </button>
                            </div>
                        ` : ''}
                        </div>
                    </div>
                </div>
                <p style="margin-top: 30px; text-align: center; color: var(--muted); font-style: italic; font-size: 0.95rem;"> 
                    ${BLIND_BOX_TEXT.discoveredGem.replace(':period', `<strong>${periodKey}</strong>`)}
                </p>
            `;

            const favouriteButton = result.querySelector('.favourite-toggle');
            favouriteButton?.addEventListener('click', async () => {
                favouriteButton.disabled = true;
                const response = await fetch(BLIND_BOX_CONFIG.favouritesStoreUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': BLIND_BOX_CONFIG.csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ draw_id: favouriteButton.dataset.drawId }),
                });
                const data = await response.json();
                favouriteButton.dataset.favourited = data.is_favourited ? '1' : '0';
                favouriteButton.innerHTML = data.is_favourited
                    ? `&#9829; <span class="favourite-label">${BLIND_BOX_TEXT.removeFavourite}</span>`
                    : `&#9825; <span class="favourite-label">${BLIND_BOX_TEXT.saveFavourite}</span>`;
                favouriteButton.disabled = false;
            });
        }

        function escapeHtml(value) {
            const element = document.createElement('div');
            element.textContent = value ?? '';
            return element.innerHTML;
        }

        function updateDiscovery(discovery) {
            if (!discovery || discovery.mode !== 'foods' || !discovery.foods?.length) return;

            const section = document.getElementById('discover');
            if (!section) return;

            const shopName = escapeHtml(discovery.shop_name);
            const foods = discovery.foods.map((food) => {
                const name = escapeHtml(food.name);
                const category = escapeHtml(food.category || BLIND_BOX_TEXT.heritageFood);
                const description = escapeHtml(food.description || BLIND_BOX_TEXT.foodRecommendation);
                const availability = food.availability
                    ? `<span class="state-chip">${escapeHtml(food.availability)}</span>`
                    : '';
                const image = food.image_url
                    ? `<img src="${escapeHtml(food.image_url)}" alt="${name}" loading="lazy">`
                    : `<div class="image-placeholder">${BLIND_BOX_TEXT.noImageAvailable}</div>`;

                return `<article class="item-card food-card">
                    <div class="item-media">${image}</div>
                    <div class="item-body">
                        <span class="tag">${category}</span>
                        <h3 style="font-size: 1.4rem; color: var(--red-dark); margin-bottom: 12px;">${name}</h3>
                        <p class="muted discovery-description" style="font-size: 0.95rem;">${description}</p>
                        <div class="item-meta">${availability}
                            <a class="btn btn-secondary" href="${escapeHtml(food.detail_url)}">${BLIND_BOX_TEXT.viewFoodDetails}</a>
                        </div>
                    </div>
                </article>`;
            }).join('');

            section.innerHTML = `<h2 style="font-family: Georgia, serif;">🏮 ${BLIND_BOX_TEXT.recommendedFoodDiscoveries}</h2>
                <p class="lead">${BLIND_BOX_TEXT.recommendedFoodsAt.replace(':shop', shopName)}</p>
                <div class="shop-grid">${foods}</div>`;
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        if (BLIND_BOX_CONFIG.alreadyDrew && BLIND_BOX_CONFIG.initialDraw) {
            boxContainer.style.display = 'none';
            renderResult(BLIND_BOX_CONFIG.initialDraw, BLIND_BOX_CONFIG.periodKey);
        }

        box.addEventListener('click', async function () {
            if (box.dataset.disabled === '1') return;
            
            box.dataset.disabled = '1';
            box.classList.remove('animate__pulse', 'animate__infinite');
            box.classList.add('opening');

            const category = document.getElementById('category-filter').value;

            try {
                const response = await fetch(`${BLIND_BOX_CONFIG.drawUrl}?category=${category}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': BLIND_BOX_CONFIG.csrfToken, 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok) {
                    box.classList.remove('opening');
                    box.dataset.disabled = '0';
                    box.classList.add('animate__pulse', 'animate__infinite');
                    document.getElementById('draw-error-text').innerText = data.error;
                    document.getElementById('draw-error').style.display = 'block';
                    boxContainer.style.display = 'none';
                    return;
                }

                setTimeout(() => {
                    beam.classList.add('active');
                    confettiBurst();
                    
                    setTimeout(() => {
                        boxContainer.style.display = 'none';
                        renderResult(data.shop, data.period, { animateIn: true });
                        updateDiscovery(data.discovery);
                    }, 400);
                }, 900);

            } catch (error) {
                box.classList.remove('opening');
                box.dataset.disabled = '0';
                box.classList.add('animate__pulse', 'animate__infinite');
            }
        });
    });
</script>

@endsection
