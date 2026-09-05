@extends('layouts.user')

@section('title', __('Leaderboard'))
@section('user-topbar-title', __('Food Passport'))
@section('user-topbar-subtitle', __('Compare heritage passport progress with other food explorers.'))

@push('styles')
<style>
    .leaderboard-page { max-width: 1180px; margin: 0 auto; padding: 30px 24px 50px; }
    .leaderboard-page-header { display:grid; gap:18px; margin-bottom:24px; }
    .passport-title-panel { position:relative; overflow:hidden; padding:26px; border:1px solid rgba(86,59,48,.12); border-radius:20px; background:linear-gradient(120deg,rgba(140,31,31,.06),transparent 50%),rgba(255,255,255,.36); box-shadow:0 18px 38px rgba(47,37,31,.08); }
    .passport-actions-panel { display:grid; gap:12px; padding:18px 22px; border:1px solid rgba(86,59,48,.12); border-radius:20px; background:linear-gradient(120deg,rgba(140,31,31,.06),transparent 50%),rgba(255,255,255,.36); box-shadow:0 18px 38px rgba(47,37,31,.08); }
    .leaderboard-panel { padding:22px; border:1px solid rgba(86,59,48,.12); border-radius:20px; background:rgba(255,255,255,.46); box-shadow:0 12px 28px rgba(47,37,31,.08); }
    .leaderboard-page-header h1 { margin:0 0 8px; color:#8C1F1F; font-family:Georgia,serif; font-size:clamp(2rem,4vw,3.2rem); }
    .leaderboard-page-header p { margin:0; color:#675B54; }
    .leaderboard-page-links { display:flex; flex-wrap:wrap; justify-content:center; gap:12px; }
    .leaderboard-page-links a { min-width:190px; padding:12px 18px; border:1px solid rgba(212,160,23,.24); border-radius:12px; background:rgba(212,160,23,.09); color:#2F251F; font-weight:700; text-align:center; text-decoration:none; }
    .leaderboard-page-links a.active { background:linear-gradient(135deg,#8C1F1F,#6D1717); color:#fff; border-color:transparent; }
    .page-back-link { justify-self:center; display:inline-flex; min-height:38px; align-items:center; padding:0 14px; border:1px solid transparent; border-radius:10px; color:#3f2a0d; background:var(--wm-gold); font-size:.82rem; font-weight:800; text-decoration:none; }
    .page-back-link:hover { background:#d7a548; }
    .leaderboard-panel { padding:22px; }
    .leaderboard-intro { margin:0; color:#675B54; line-height:1.6; }
    .leaderboard-list { display:grid; gap:10px; margin-top:18px; }
    .leaderboard-row { display:grid; grid-template-columns:54px minmax(0,1fr) auto; gap:14px; align-items:center; padding:14px; border:1px solid rgba(86,59,48,.12); border-radius:14px; background:rgba(255,255,255,.54); }
    .leaderboard-rank { display:grid; width:42px; height:42px; place-items:center; border-radius:12px; background:rgba(212,160,23,.14); color:#8C1F1F; font-weight:800; }
    .leaderboard-user h2 { margin:0 0 4px; color:#2F251F; font-size:1.08rem; }
    .leaderboard-user p { margin:0; color:#675B54; font-size:.8rem; }
    .leaderboard-metrics { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px; }
    .leaderboard-metrics span { padding:7px 9px; border-radius:999px; background:rgba(140,31,31,.07); color:#8C1F1F; font-size:.76rem; font-weight:800; }
    .pagination { display:flex; justify-content:center; gap:8px; margin-top:22px; }.pagination a,.pagination span { padding:8px 12px; border:1px solid rgba(86,59,48,.12); border-radius:8px; color:#8C1F1F; text-decoration:none; }.pagination .active { background:#8C1F1F; color:#fff; }
    @media(max-width:760px){.leaderboard-page{padding:22px 16px 40px}.leaderboard-row{grid-template-columns:46px minmax(0,1fr)}.leaderboard-metrics{grid-column:2;justify-content:flex-start}}
</style>
@endpush

@section('content')
<div class="leaderboard-page">
    <div class="leaderboard-page-header">
        <div class="passport-title-panel"><h1>{{ __('Leaderboard') }}</h1><p>{{ __('Ranked by badges received, then total check-ins. Recent check-ins decide ties.') }}</p></div>
        <div class="passport-actions-panel"><nav class="leaderboard-page-links" aria-label="{{ __('Passport pages') }}"><a href="{{ route('passport.history') }}">{{ __('View Visit History') }}</a><a href="{{ route('passport.statistics') }}">{{ __('Passport Statistics & Achievements') }}</a><a class="active" href="{{ route('passport.leaderboard') }}">{{ __('Leaderboard') }}</a></nav><a class="page-back-link" href="{{ route('passport.index') }}">{{ __('Back to Food Passport') }}</a></div>
    </div>
    <section class="leaderboard-panel">
        @if ($leaderboard->isNotEmpty())
            <div class="leaderboard-list">
                @foreach ($leaderboard as $entry)
                    <article class="leaderboard-row">
                        <div class="leaderboard-rank">#{{ $entry->rank }}</div>
                        <div class="leaderboard-user">
                            <h2>{{ $entry->name ?: __('Heritage Explorer') }}</h2>
                            <p>{{ __('Last check-in: :date', ['date' => $entry->last_check_in_label]) }}</p>
                        </div>
                        <div class="leaderboard-metrics">
                            <span>{{ __(':count badges', ['count' => $entry->badges_received]) }}</span>
                            <span>{{ __(':count check-ins', ['count' => $entry->check_ins]) }}</span>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($leaderboard->lastPage() > 1)
                <nav class="pagination" aria-label="{{ __('Leaderboard pages') }}">
                    @if ($leaderboard->onFirstPage())
                        <span>{{ __('Previous') }}</span>
                    @else
                        <a href="{{ $leaderboard->previousPageUrl() }}">{{ __('Previous') }}</a>
                    @endif

                    @for ($page = 1; $page <= $leaderboard->lastPage(); $page++)
                        @if ($page === $leaderboard->currentPage())
                            <span class="active">{{ $page }}</span>
                        @else
                            <a href="{{ $leaderboard->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor

                    @if ($leaderboard->hasMorePages())
                        <a href="{{ $leaderboard->nextPageUrl() }}">{{ __('Next') }}</a>
                    @else
                        <span>{{ __('Next') }}</span>
                    @endif
                </nav>
            @endif
        @else
            <p class="leaderboard-intro">{{ __('The leaderboard will appear after users start checking in and earning badges.') }}</p>
        @endif
    </section>
</div>
@endsection
