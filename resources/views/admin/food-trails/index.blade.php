@extends('admin.layout')

@section('title', 'Food Trail Suggestions')
@section('page-title', 'Food Trails')

@section('content')
    <div class="back-nav">
        <a href="{{ route('admin.dashboard') }}" class="back-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Back to Dashboard
        </a>
    </div>

    <div class="page-header">
        <div><p class="eyebrow">User recommendations</p><h1>Food trail suggestions</h1><p>Create recommendations that users can try from the Food Trails page.</p></div>
        <a class="button primary" href="{{ route('admin.food-trails.create') }}">Add suggestion</a>
    </div>
    @if (session('status')) <div class="status-banner success">{{ session('status') }}</div> @endif
    <section class="record-list">
        @forelse ($suggestions as $suggestion)
            <article class="record-card">
                <div><span class="badge {{ $suggestion->is_published ? 'badge-approved' : 'badge-draft' }}">{{ $suggestion->is_published ? 'Published' : 'Hidden' }}</span><h2>{{ $suggestion->title }}</h2><p>{{ $suggestion->summary }}</p><div class="record-meta"><span>{{ $suggestion->restaurants_count }} stops</span></div></div>
                <div class="record-actions"><a class="button secondary small" href="{{ route('admin.food-trails.edit', $suggestion) }}">Edit</a><form method="POST" action="{{ route('admin.food-trails.destroy', $suggestion) }}">@csrf @method('DELETE')<button class="button danger small" onclick="return confirm('Remove this suggestion?')">Remove</button></form></div>
            </article>
        @empty
            <div class="panel empty-state"><h2>No suggestions yet</h2><p>Add a food trail recommendation for users to discover.</p><a class="button primary" href="{{ route('admin.food-trails.create') }}">Add suggestion</a></div>
        @endforelse
    </section>
@endsection
