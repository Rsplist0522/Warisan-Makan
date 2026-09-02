@extends('admin.layout')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
    <style>
        .welcome { position: relative; overflow: hidden; padding: 30px; border-radius: 18px; color: #fffaf4; background: linear-gradient(125deg, #96352c, #54201b); box-shadow: 0 20px 50px rgba(91, 29, 29, .18); }
        .welcome::after { content: ''; position: absolute; width: 240px; height: 240px; top: -100px; right: -68px; border: 1px solid rgba(255,255,255,.16); border-radius: 50%; box-shadow: 0 0 0 22px rgba(255,255,255,.04), 0 0 0 46px rgba(255,255,255,.025); pointer-events: none; }
        .welcome > * { position: relative; z-index: 1; }
        .welcome .eyebrow { margin: 0 0 8px; color: #e4bd72; font-size: .72rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
        .welcome h2 { margin: 0; font-family: Georgia, serif; font-size: clamp(1.8rem, 4vw, 2.7rem); }
        .welcome p { max-width: 700px; margin: 11px 0 0; color: rgba(255,250,244,.72); line-height: 1.6; }
        .module-grid { margin-top: 22px; }
        .module-card { min-height: 202px; padding: 22px; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease; }
        .module-card:hover { border-color: rgba(163, 58, 45, .36); background: #fffaf2; box-shadow: 0 18px 42px rgba(77, 48, 34, .12); transform: translateY(-3px); }
        .module-icon { width: 44px; height: 44px; display: grid; place-items: center; flex: 0 0 auto; margin-bottom: 20px; border-radius: 12px; color: #3f2a0d; background: rgba(200, 148, 50, .24); }
        .module-icon svg { width: 22px; height: 22px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .module-stats { display: flex; flex-wrap: wrap; gap: 8px; margin-top: auto; padding-top: 16px; color: var(--muted); font-size: .74rem; font-weight: 800; }
        .module-stats span { padding: 5px 8px; border-radius: 8px; background: rgba(255,255,255,.72); }
        .module-status { margin-top: auto; padding-top: 18px; color: var(--accent); font-size: .78rem; font-weight: 850; }
        .module-stats + .module-status { margin-top: 0; }
    </style>
@endpush

@section('content')
    @php
        $heritageShopCount = \App\Models\HeritageShop::query()->count();
        $heritagePublishedCount = \App\Models\HeritageShop::query()->published()->count();
        $heritageFoodCount = \App\Models\HeritageFoodItem::query()->where('is_active', true)->count();
        $badgeCount = \App\Models\Badge::query()->where('is_active', true)->count();
    @endphp
    <section class="welcome">
        <p class="eyebrow">Administrator portal</p>
        <h2>Welcome, {{ auth()->user()->name }}.</h2>
        <p>Choose an active workspace to manage heritage records, member submissions, Passport achievements, recommendations, and access.</p>
    </section>

    <section class="module-grid" aria-label="Admin modules">
        <a class="module-card" href="{{ route('admin.heritage-shops.index') }}">
            <span class="module-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'shop'])</span>
            <h3>Heritage Shops</h3>
            <p>Curate verified food businesses, preserve their stories, manage gallery media, and publish the dishes visitors come to discover.</p>
            <div class="module-stats"><span>{{ $heritageShopCount }} shops</span><span>{{ $heritagePublishedCount }} published</span><span>{{ $heritageFoodCount }} active foods</span></div>
            <strong class="module-status">Active HeritageShop records &rarr;</strong>
        </a>
        <a class="module-card" href="{{ route('admin.community-contributions.submissions') }}">
            <span class="module-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'community'])</span>
            <h3>Community Contribution</h3>
            <p>Review heritage eatery submissions, move items through moderation, and inspect the admin activity history.</p>
            <strong class="module-status">Open module</strong>
        </a>
        <a class="module-card" href="{{ route('admin.badges.index') }}">
            <span class="module-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'passport'])</span>
            <h3>Food Passport</h3>
            <p>Manage achievement badges users can earn as they check in to heritage food experiences.</p>
            <div class="module-stats"><span>{{ $badgeCount }} active badges</span></div>
            <strong class="module-status">Manage Passport badges &rarr;</strong>
        </a>
        <a class="module-card" href="{{ route('admin.food-trails.index') }}">
            <span class="module-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'map'])</span>
            <h3>Food Trails</h3>
            <p>Create and publish food trail suggestions that users can browse and follow.</p>
            <strong class="module-status">Open module</strong>
        </a>
        <a class="module-card" href="{{ route('admin.blind-box-items.index') }}">
            <span class="module-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'box'])</span>
            <h3>Blind Box</h3>
            <p>Manage the recommendation pool, categories, and shops available for user reveals.</p>
            <strong class="module-status">Open module</strong>
        </a>
        <a class="module-card" href="{{ route('admin.users.index') }}">
            <span class="module-icon" aria-hidden="true">@include('partials.module-icon', ['icon' => 'users'])</span>
            <h3>Users &amp; Roles</h3>
            <p>Review member accounts, activation status, and access controls for regular users.</p>
            <strong class="module-status">Open module</strong>
        </a>
    </section>
@endsection
