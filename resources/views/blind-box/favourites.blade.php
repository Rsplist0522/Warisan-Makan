@extends('layouts.user')

@section('title', __('Blind Box Favourites'))
@section('user-topbar-title', __('Blind Box Favourites'))
@section('user-topbar-subtitle', __('Your curated heritage food discoveries from the Blind Box.'))

@section('user-topbar-actions')
    <a class="user-topbar-link" href="{{ route('heritage-shops.index') }}">{{ __('Heritage Shops') }}</a>
    <a class="user-topbar-link" href="{{ route('foodtrails.index') }}">{{ __('Food Trails') }}</a>
    <a class="user-topbar-link" href="{{ route('blind-box.index') }}" style="background: var(--red, #8c1f1f); color: #fff; font-weight: 700; border-radius: 999px; padding: 6px 14px;">
        <i class="fa-solid fa-gift me-1"></i> {{ __('Open Blind Box') }}
    </a>
@endsection

@push('head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@endpush

@push('styles')
<style>
    :root {
        --bb-red: #8c1f1f;
        --bb-red-dark: #691616;
        --bb-gold: #c98b16;
        --bb-gold-light: #f7c948;
        --bb-cream-bg: #fdfaf6;
        --bb-panel: #ffffff;
        --bb-ink: #2b1d16;
        --bb-muted: #736357;
        --bb-border: rgba(140, 31, 31, 0.12);
        --bb-border-gold: rgba(201, 139, 22, 0.28);
        --bb-shadow: 0 10px 30px rgba(69, 34, 18, 0.07);
        --bb-shadow-hover: 0 18px 40px rgba(140, 31, 31, 0.14);
    }

    .fav-page {
        display: grid;
        gap: 24px;
        max-width: 1200px;
        margin: 0 auto;
        padding-bottom: 48px;
    }

    /* --- HERO HEADER --- */
    .fav-hero {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        background: linear-gradient(135deg, #7c1a1a 0%, #4f1111 60%, #300a0a 100%);
        color: #fff;
        padding: 36px 32px;
        box-shadow: 0 16px 36px rgba(48, 10, 10, 0.28);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
    }

    .fav-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(247, 201, 72, 0.18), transparent 60%),
                    radial-gradient(circle at bottom left, rgba(201, 139, 22, 0.15), transparent 50%);
        pointer-events: none;
    }

    .fav-hero-content {
        position: relative;
        z-index: 2;
        max-width: 620px;
    }

    .fav-badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(247, 201, 72, 0.2);
        border: 1px solid rgba(247, 201, 72, 0.35);
        color: #f7c948;
        padding: 5px 12px;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 12px;
    }

    .fav-hero h1 {
        margin: 0 0 10px;
        font-family: Georgia, "Times New Roman", serif;
        font-size: clamp(1.8rem, 3.2vw, 2.4rem);
        color: #fff;
        line-height: 1.2;
    }

    .fav-hero p {
        margin: 0;
        color: rgba(255, 245, 235, 0.88);
        font-size: 1rem;
        line-height: 1.6;
    }

    .fav-hero-stats {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .fav-stat-box {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.18);
        padding: 14px 22px;
        border-radius: 16px;
        text-align: center;
        min-width: 100px;
    }

    .fav-stat-num {
        font-size: 1.8rem;
        font-weight: 800;
        color: #f7c948;
        line-height: 1;
        font-family: Georgia, "Times New Roman", serif;
    }

    .fav-stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255, 255, 255, 0.8);
        margin-top: 4px;
    }

    .fav-hero-cta {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #f7c948, #c98b16);
        color: #3b1b0b;
        font-weight: 800;
        padding: 12px 22px;
        border-radius: 999px;
        text-decoration: none;
        box-shadow: 0 8px 20px rgba(201, 139, 22, 0.4);
        transition: all 0.25s ease;
    }

    .fav-hero-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(201, 139, 22, 0.55);
        color: #261106;
    }

    /* --- TOOLBAR: SEARCH & FILTERS --- */
    .fav-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        background: #ffffff;
        padding: 16px 20px;
        border-radius: 18px;
        border: 1px solid var(--bb-border);
        box-shadow: var(--bb-shadow);
    }

    .fav-search-wrapper {
        position: relative;
        flex: 1;
        min-width: 240px;
    }

    .fav-search-wrapper i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--bb-muted);
        font-size: 0.95rem;
    }

    .fav-search-input {
        width: 100%;
        padding: 10px 16px 10px 40px;
        border: 1px solid rgba(140, 31, 31, 0.15);
        border-radius: 999px;
        font-size: 0.92rem;
        background: #fdfaf7;
        color: var(--bb-ink);
        outline: none;
        transition: all 0.2s ease;
    }

    .fav-search-input:focus {
        border-color: var(--bb-gold);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(201, 139, 22, 0.15);
    }

    .fav-filter-pills {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .fav-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: 999px;
        font-size: 0.85rem;
        font-weight: 600;
        border: 1px solid rgba(140, 31, 31, 0.15);
        background: #fff;
        color: var(--bb-muted);
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }

    .fav-pill:hover {
        border-color: var(--bb-red);
        color: var(--bb-red);
    }

    .fav-pill.active {
        background: var(--bb-red);
        border-color: var(--bb-red);
        color: #fff;
    }

    /* --- CARDS GRID --- */
    .fav-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 22px;
    }

    .fav-card {
        display: flex;
        flex-direction: column;
        background: var(--bb-panel);
        border: 1px solid var(--bb-border);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--bb-shadow);
        transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.3s ease, border-color 0.3s ease;
        position: relative;
    }

    .fav-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--bb-shadow-hover);
        border-color: var(--bb-border-gold);
    }

    .fav-card-image-wrap {
        position: relative;
        width: 100%;
        height: 200px;
        background: #f5ece1;
        overflow: hidden;
    }

    .fav-card-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
    }

    .fav-card:hover .fav-card-image {
        transform: scale(1.06);
    }

    .fav-image-fallback {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--bb-muted);
        background: linear-gradient(135deg, #fceddb 0%, #ecd7c2 100%);
        font-size: 0.88rem;
        gap: 8px;
    }

    .fav-image-fallback i {
        font-size: 2.2rem;
        color: #c98b16;
        opacity: 0.6;
    }

    .fav-card-badges {
        position: absolute;
        top: 12px;
        left: 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        z-index: 2;
    }

    .fav-chip {
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        backdrop-filter: blur(8px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }

    .fav-chip-state {
        background: rgba(255, 255, 255, 0.92);
        color: var(--bb-red);
    }

    .fav-chip-category {
        background: rgba(140, 31, 31, 0.85);
        color: #fff;
    }

    .fav-chip-year {
        background: rgba(201, 139, 22, 0.9);
        color: #fff;
    }

    .fav-heart-indicator {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.92);
        color: #e53935;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 2;
    }

    .fav-card-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        flex: 1;
        gap: 12px;
    }

    .fav-shop-title {
        margin: 0;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 1.28rem;
        color: var(--bb-red-dark);
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .fav-shop-address {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        color: var(--bb-muted);
        font-size: 0.84rem;
        line-height: 1.4;
        margin: 0;
    }

    .fav-shop-address i {
        color: var(--bb-red);
        margin-top: 3px;
        flex-shrink: 0;
    }

    .fav-shop-desc {
        color: #554439;
        font-size: 0.88rem;
        line-height: 1.55;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .fav-saved-time {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.76rem;
        color: #968375;
        margin-top: auto;
        padding-top: 8px;
        border-top: 1px dashed rgba(140, 31, 31, 0.1);
    }

    .fav-card-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        padding-top: 14px;
        border-top: 1px solid rgba(140, 31, 31, 0.08);
    }

    .fav-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 9px 14px;
        border-radius: 10px;
        font-size: 0.86rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .fav-action-btn.primary {
        background: linear-gradient(135deg, var(--bb-red), var(--bb-red-dark));
        color: #fff;
        flex: 1;
        box-shadow: 0 4px 12px rgba(140, 31, 31, 0.2);
    }

    .fav-action-btn.primary:hover {
        background: linear-gradient(135deg, var(--bb-red-dark), #4a0f0f);
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(140, 31, 31, 0.3);
    }

    .fav-action-btn.secondary {
        background: #fff;
        border-color: rgba(140, 31, 31, 0.18);
        color: var(--bb-red);
    }

    .fav-action-btn.secondary:hover {
        background: #fdf5f3;
        border-color: var(--bb-red);
    }

    .fav-action-btn.delete {
        background: #fff;
        border-color: rgba(229, 57, 53, 0.2);
        color: #d32f2f;
        padding: 9px 12px;
    }

    .fav-action-btn.delete:hover {
        background: #ffebee;
        border-color: #d32f2f;
        color: #b71c1c;
    }

    /* --- EMPTY STATES --- */
    .fav-empty-state {
        background: #ffffff;
        border: 2px dashed rgba(201, 139, 22, 0.35);
        border-radius: 24px;
        padding: 56px 28px;
        text-align: center;
        box-shadow: var(--bb-shadow);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 16px;
    }

    .fav-empty-icon {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        background: linear-gradient(135deg, #fff3d6 0%, #fee6ad 100%);
        color: var(--bb-gold);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        box-shadow: 0 10px 24px rgba(201, 139, 22, 0.2);
    }

    .fav-empty-state h2 {
        font-family: Georgia, "Times New Roman", serif;
        font-size: 1.6rem;
        color: var(--bb-red-dark);
        margin: 0;
    }

    .fav-empty-state p {
        color: var(--bb-muted);
        max-width: 480px;
        margin: 0 auto;
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .fav-empty-cta {
        margin-top: 10px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: linear-gradient(135deg, var(--bb-red), var(--bb-red-dark));
        color: #fff;
        padding: 13px 26px;
        border-radius: 999px;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 10px 22px rgba(140, 31, 31, 0.25);
        transition: all 0.25s ease;
    }

    .fav-empty-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(140, 31, 31, 0.35);
        color: #fff;
    }

    .fav-no-results {
        display: none;
        grid-column: 1 / -1;
        padding: 48px 20px;
        text-align: center;
        background: #fff;
        border-radius: 18px;
        border: 1px dashed rgba(140, 31, 31, 0.15);
        color: var(--bb-muted);
    }

    @media (max-width: 768px) {
        .fav-hero {
            padding: 26px 20px;
            flex-direction: column;
            align-items: stretch;
        }

        .fav-hero-stats {
            justify-content: flex-start;
        }

        .fav-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .fav-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="fav-page animate__animated animate__fadeIn">

    <!-- HERO HEADER -->
    <div class="fav-hero">
        <div class="fav-hero-content">
            <div class="fav-badge-pill">
                <i class="fa-solid fa-heart"></i> {{ __('Saved Discoveries') }}
            </div>
            <h1>{{ __('My Blind Box Favourites') }}</h1>
            <p>{{ __('Relive and explore your saved heritage culinary gems discovered through your mystery blind box draws.') }}</p>
        </div>

        <div class="fav-hero-stats">
            <div class="fav-stat-box">
                <div class="fav-stat-num">{{ $favourites->count() }}</div>
                <div class="fav-stat-label">{{ __('Treasures') }}</div>
            </div>
            <a class="fav-hero-cta" href="{{ route('blind-box.index') }}">
                <i class="fa-solid fa-gift"></i>
                <span>{{ __('Open New Box') }}</span>
            </a>
        </div>
    </div>

    @if($favourites->isEmpty())
        <!-- EMPTY STATE -->
        <div class="fav-empty-state animate__animated animate__fadeInUp">
            <div class="fav-empty-icon">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <h2>{{ __('No Saved Favourites Yet') }}</h2>
            <p>{{ __('Whenever you uncover an exciting heritage food place from your daily Blind Box draw, tap the heart button to save it here for easy access and trail planning.') }}</p>
            <a class="fav-empty-cta" href="{{ route('blind-box.index') }}">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span>{{ __('Draw Blind Box Now') }}</span>
            </a>
        </div>
    @else
        <!-- TOOLBAR -->
        <div class="fav-toolbar">
            <div class="fav-search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input 
                    type="text" 
                    id="favSearchInput" 
                    class="fav-search-input" 
                    placeholder="{{ __('       Search by shop name, category, or state...') }}" 
                    autocomplete="off"
                >
            </div>

            <div class="fav-filter-pills" id="favFilterPills">
                <button type="button" class="fav-pill active" data-filter="all">
                    {{ __('All') }} ({{ $favourites->count() }})
                </button>
                @php
                    $categories = $favourites->pluck('category')->filter()->unique()->values();
                @endphp
                @foreach($categories as $category)
                    <button type="button" class="fav-pill" data-filter="{{ Str::slug($category) }}">
                        {{ $category }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- CARDS GRID -->
        <div class="fav-grid" id="favCardsGrid">
            @foreach($favourites as $favourite)
                @php
                    $categorySlug = $favourite->category ? Str::slug($favourite->category) : 'heritage';
                    $searchData = strtolower(($favourite->shop_name ?? '') . ' ' . ($favourite->category ?? '') . ' ' . ($favourite->state ?? '') . ' ' . ($favourite->address ?? ''));
                @endphp
                <article 
                    class="fav-card animate__animated animate__fadeIn" 
                    data-category="{{ $categorySlug }}" 
                    data-search="{{ $searchData }}"
                >
                    <div class="fav-card-image-wrap">
                        @if($favourite->image)
                            <img class="fav-card-image" src="{{ $favourite->image }}" alt="{{ $favourite->shop_name }}" loading="lazy">
                        @else
                            <div class="fav-image-fallback">
                                <i class="fa-solid fa-utensils"></i>
                                <span>{{ __('Heritage Flavours') }}</span>
                            </div>
                        @endif

                        <div class="fav-card-badges">
                            @if($favourite->state)
                                <span class="fav-chip fav-chip-state">
                                    <i class="fa-solid fa-location-dot me-1"></i>{{ $favourite->state }}
                                </span>
                            @endif
                            @if($favourite->category)
                                <span class="fav-chip fav-chip-category">
                                    {{ $favourite->category }}
                                </span>
                            @endif
                            @if($favourite->year)
                                <span class="fav-chip fav-chip-year">
                                    <i class="fa-solid fa-clock-rotate-left me-1"></i>{{ $favourite->year }}
                                </span>
                            @endif
                        </div>

                        <div class="fav-heart-indicator" title="{{ __('Saved in Favourites') }}">
                            <i class="fa-solid fa-heart"></i>
                        </div>
                    </div>

                    <div class="fav-card-body">
                        <h2 class="fav-shop-title">{{ $favourite->shop_name }}</h2>

                        @if($favourite->address)
                            <p class="fav-shop-address">
                                <i class="fa-solid fa-map-pin"></i>
                                <span>{{ $favourite->address }}</span>
                            </p>
                        @endif

                        <p class="fav-shop-desc">
                            {{ $favourite->description ?: __('A timeless Malaysian culinary destination saved from your Blind Box draw.') }}
                        </p>

                        <div class="fav-saved-time">
                            <i class="fa-regular fa-clock"></i>
                            <span>{{ __('Saved') }} {{ $favourite->created_at ? $favourite->created_at->diffForHumans() : __('recently') }}</span>
                        </div>

                        <div class="fav-card-actions">
                            <a 
                                class="fav-action-btn primary" 
                                href="{{ $favourite->shop_source_id ? route('heritage-shops.show', $favourite->shop_source_id) : route('heritage-shops.index') }}"
                            >
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                <span>{{ __('View Details') }}</span>
                            </a>

                            <a class="fav-action-btn secondary" href="{{ route('foodtrails.index') }}" title="{{ __('Find in Food Trails') }}">
                                <i class="fa-solid fa-route"></i>
                                <span>{{ __('Explore Trails') }}</span>
                            </a>

                            <form 
                                class="m-0" 
                                method="POST" 
                                action="{{ route('blind-box.favourites.destroy', $favourite) }}"
                                onsubmit="return confirm('{{ __('Are you sure you want to remove this shop from your favourites?') }}');"
                            >
                                @csrf
                                @method('DELETE')
                                <button class="fav-action-btn delete" type="submit" title="{{ __('Remove from favourites') }}">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach

            <!-- NO SEARCH RESULTS FOUND -->
            <div class="fav-no-results" id="favNoResults">
                <i class="fa-solid fa-magnifying-glass mb-2" style="font-size: 2rem; color: #c98b16;"></i>
                <h3 style="margin: 8px 0; color: var(--bb-red-dark);">{{ __('No matching favourites found') }}</h3>
                <p style="margin: 0; font-size: 0.9rem;">{{ __('Try adjusting your search keyword or clearing the category filter.') }}</p>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('favSearchInput');
    const filterPills = document.querySelectorAll('#favFilterPills .fav-pill');
    const cards = document.querySelectorAll('#favCardsGrid .fav-card');
    const noResults = document.getElementById('favNoResults');

    if (!cards.length) return;

    let activeFilter = 'all';
    let currentQuery = '';

    function filterCards() {
        let visibleCount = 0;
        const query = currentQuery.trim().toLowerCase();

        cards.forEach(card => {
            const cardCategory = card.getAttribute('data-category') || '';
            const cardSearch = (card.getAttribute('data-search') || '').toLowerCase();

            const matchesCategory = (activeFilter === 'all' || cardCategory === activeFilter);
            const matchesSearch = !query || cardSearch.includes(query);

            if (matchesCategory && matchesSearch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (noResults) {
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            currentQuery = e.target.value;
            filterCards();
        });
    }

    filterPills.forEach(pill => {
        pill.addEventListener('click', function () {
            filterPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            activeFilter = this.getAttribute('data-filter');
            filterCards();
        });
    });
});
</script>
@endpush

