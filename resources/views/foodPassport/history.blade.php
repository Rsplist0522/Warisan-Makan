@extends('layouts.user')

@section('title', __('Visit History'))
@section('user-topbar-title', __('Food Passport'))
@section('user-topbar-subtitle', __('Review your heritage food journey.'))

@push('styles')
<style>
    .passport-page { max-width: 1180px; margin: 0 auto; padding: 30px 24px 50px; }
    .passport-page-header { display: grid; gap: 18px; margin-bottom: 24px; }
    .passport-title-panel { position: relative; overflow: hidden; padding: 26px; border: 1px solid rgba(86,59,48,.12); border-radius: 20px; background: linear-gradient(120deg,rgba(140,31,31,.06),transparent 50%),rgba(255,255,255,.36); box-shadow: 0 18px 38px rgba(47,37,31,.08); }
    .passport-actions-panel { display: grid; gap: 12px; padding: 18px 22px; border: 1px solid rgba(86,59,48,.12); border-radius: 20px; background: linear-gradient(120deg,rgba(140,31,31,.06),transparent 50%),rgba(255,255,255,.36); box-shadow: 0 18px 38px rgba(47,37,31,.08); }
    .passport-page-panel { margin-top: 18px; padding: 22px; border: 1px solid rgba(86,59,48,.12); border-radius: 20px; background: rgba(255,255,255,.46); box-shadow: 0 12px 28px rgba(47,37,31,.08); }
    .passport-page-header h1 { margin: 0 0 8px; color: #8C1F1F; font-family: Georgia, serif; font-size: clamp(2rem, 4vw, 3.2rem); }
    .passport-page-header p { margin: 0; color: #675B54; }
    .passport-page-links { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; }
    .passport-page-links a { min-width: 190px; padding: 12px 18px; border: 1px solid rgba(212,160,23,.24); border-radius: 12px; background: rgba(212,160,23,.09); color: #2F251F; font-weight: 700; text-align: center; text-decoration: none; }
    .passport-page-links a.active { background: linear-gradient(135deg,#8C1F1F,#6D1717); color: #fff; border-color: transparent; }
    .history-back { justify-self: center; display: inline-flex; min-height: 38px; align-items: center; padding: 0 14px; border: 1px solid transparent; border-radius: 10px; color: #3f2a0d; background: var(--wm-gold); font-size: .82rem; font-weight: 800; text-decoration: none; }
    .history-back:hover { background: #d7a548; }
    .passport-page-panel { margin-top: 18px; padding: 22px; }
    .history-filters { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(150px, .8fr) minmax(150px, .8fr) auto; gap: 12px; align-items: end; }
    .history-filters label { display: grid; gap: 7px; color: var(--wm-muted); font-size: .78rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
    .history-filters input, .history-filters select { width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid var(--wm-border); border-radius: 9px; background: #fff; color: var(--wm-text); }
    .history-filter-button { min-height: 38px; padding: 0 14px; border: 1px solid transparent; border-radius: 10px; background: var(--wm-gold); color: #3f2a0d; font-weight: 800; cursor: pointer; }
    .history-filter-button:hover { background: #d7a548; }
    .history-summary { margin: 22px 0 12px; color: var(--wm-muted); font-size: .9rem; }
    .visit-list { display: grid; gap: 10px; }
    .visit-row { display: grid; gap: 16px; align-items: center; padding: 16px; border: 1px solid var(--wm-border); border-radius: 12px; background: rgba(255, 255, 255, .68); }
    .visit-row h2 { margin: 0 0 5px; color: var(--wm-ink); font-size: 1.25rem; }
    .visit-row p { margin: 0; color: var(--wm-muted); }
    .visit-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 9px; color: var(--wm-muted); font-size: .82rem; }
    .visit-meta span { padding: 5px 8px; border-radius: 999px; background: var(--wm-accent-soft); }
    .history-empty { padding: 30px 10px; color: var(--wm-muted); text-align: center; }
    .history-pagination { display: flex; justify-content: center; gap: 8px; margin-top: 22px; }
    .history-pagination a, .history-pagination span { padding: 8px 12px; border: 1px solid var(--wm-border); border-radius: 8px; color: var(--wm-accent-strong); text-decoration: none; }
    .history-pagination .active { background: var(--wm-accent-strong); color: #fff; }
    @media (max-width: 760px) {
        .passport-page { padding: 22px 16px 40px; }
        .history-header { align-items: flex-start; flex-direction: column; }
        .history-filters { grid-template-columns: 1fr; }
        .visit-row { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="passport-page">
    <div class="passport-page-header">
        <div class="passport-title-panel">
            <h1>{{ __('Passport History') }}</h1>
            <p>{{ __('Search, filter, and revisit the heritage shops you have checked in to.') }}</p>
        </div>
        <div class="passport-actions-panel">
        <nav class="passport-page-links" aria-label="{{ __('Passport pages') }}">
            <a class="active" href="{{ route('passport.history') }}">{{ __('View Visit History') }}</a>
            <a href="{{ route('passport.statistics') }}">{{ __('Passport Statistics & Achievements') }}</a>
            <a href="{{ route('passport.leaderboard') }}">{{ __('Leaderboard') }}</a>
        </nav>
        <a class="history-back" href="{{ route('passport.index') }}">{{ __('Back to Food Passport') }}</a>
        </div>
    </div>

    <section class="passport-page-panel" aria-labelledby="history-list-title">
        <form class="history-filters" method="GET" action="{{ route('passport.history') }}">
            <label>
                {{ __('Search visited shops') }}
                <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Shop, city, or state') }}">
            </label>
            <label>
                {{ __('State / location') }}
                <select name="state">
                    <option value="">{{ __('All locations') }}</option>
                    @foreach ($states as $availableState)
                        <option value="{{ $availableState }}" @selected($state === $availableState)>{{ $availableState }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                {{ __('Sort by') }}
                <select name="sort">
                    <option value="newest" @selected($sort === 'newest')>{{ __('Newest first') }}</option>
                    <option value="oldest" @selected($sort === 'oldest')>{{ __('Oldest first') }}</option>
                </select>
            </label>
            <button class="history-filter-button" type="submit">{{ __('Apply filters') }}</button>
        </form>

        <p id="history-list-title" class="history-summary">
            {{ trans_choice(':count visit|:count visits', $visits->total(), ['count' => $visits->total()]) }}
        </p>

        @if ($visits->isNotEmpty())
            <div class="visit-list">
                @foreach ($visits as $visit)
                                <article class="visit-row" data-visit-record>
                        <div>
                            <h2>{{ $visit->shop_name ?: __('Heritage shop') }}</h2>
                            <p>{{ $visit->founder_name ?: __('Local founder') }}</p>
                            <div class="visit-meta">
                                <span>{{ $visit->stamp_datetime ? \Carbon\Carbon::parse($visit->stamp_datetime)->format('d M Y, H:i') : __('Date unavailable') }}</span>
                                @if ($visit->city || $visit->state)
                                    <span>{{ implode(', ', array_filter([$visit->city, $visit->state])) }}</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($visits->lastPage() > 1)
                <nav class="history-pagination" aria-label="{{ __('Visit history pages') }}">
                    @if ($visits->onFirstPage())
                        <span aria-disabled="true">{{ __('Previous') }}</span>
                    @else
                        <a href="{{ $visits->previousPageUrl() }}">{{ __('Previous') }}</a>
                    @endif
                    @for ($page = 1; $page <= $visits->lastPage(); $page++)
                        @if ($page === $visits->currentPage())
                            <span class="active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $visits->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor
                    @if ($visits->hasMorePages())
                        <a href="{{ $visits->nextPageUrl() }}">{{ __('Next') }}</a>
                    @else
                        <span aria-disabled="true">{{ __('Next') }}</span>
                    @endif
                </nav>
            @endif
        @else
            <p class="history-empty">{{ __('No passport visits match your filters yet.') }}</p>
        @endif
    </section>
</div>
@endsection
