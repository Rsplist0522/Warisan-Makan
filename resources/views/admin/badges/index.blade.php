@extends('admin.layout')

@section('title', 'Achievement Badges')
@section('page-title', 'Achievement badges')

@section('content')
    <div class="back-nav">
        <a href="{{ route('admin.dashboard') }}" class="back-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Back to Dashboard
        </a>
    </div>

    <header class="page-header">
        <div><p class="eyebrow">Food Passport</p><h1>Achievement badges</h1><p>Manage badges available for future achievement awards while preserving user history.</p></div>
        <a class="button primary" href="{{ route('admin.badges.create') }}">Create badge</a>
    </header>
    <section class="panel">
        @if ($badges->isEmpty())
            <div class="empty-state"><h2>No badges yet</h2><p>Create the first achievement badge for the Food Passport.</p><a class="button primary" href="{{ route('admin.badges.create') }}">Create badge</a></div>
        @else
            <div class="record-list">
                @foreach ($badges as $badge)
                    <article class="record-card">
                        <div><div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;"><strong style="font-size:1.05rem;">{{ $badge->icon ?: '★' }} {{ $badge->badge_name }}</strong><span class="badge {{ $badge->is_active ? 'badge-approved' : 'badge-draft' }}">{{ $badge->is_active ? 'Active' : 'Inactive' }}</span></div><p>{{ $badge->description }}</p><div class="record-meta"><span>Criterion: {{ $badge->criteria_type }} {{ $badge->criteria_value }}</span><span>{{ $badge->user_badges_count }} awarded</span><span>Updated {{ optional($badge->updated_at)->format('d M Y') }}</span></div></div>
                        <div class="record-actions"><a class="button secondary small" href="{{ route('admin.badges.edit', $badge) }}">Edit</a><form method="POST" action="{{ route('admin.badges.toggle', $badge) }}">@csrf @method('PATCH')<button class="button {{ $badge->is_active ? 'danger' : 'info' }} small" type="submit">{{ $badge->is_active ? 'Deactivate' : 'Activate' }}</button></form></div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
