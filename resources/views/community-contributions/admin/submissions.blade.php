@extends('admin.layout')

@section('title', 'Review Queue')
@section('page-title', 'Community Contribution')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Administrator function 1</p>
            <h1>Review Queue</h1>
            <p>Open Community Contribution submissions, start review, and record a moderation outcome.</p>
        </div>
        <div class="actions">
            <a class="button secondary" href="{{ route('admin.community-contributions.correction-requests') }}">Correction requests</a>
            <a class="button secondary" href="{{ route('admin.community-contributions.history') }}">View history</a>
        </div>
    </header>

    <form class="filters six" method="GET" action="{{ route('admin.community-contributions.submissions') }}">
        <div class="field">
            <label for="search">Search submission or contributor</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Title, shop, or contributor name">
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All active submissions</option>
                @foreach ($activeStatuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="contributor_id">Contributor</label>
            <select id="contributor_id" name="contributor_id">
                <option value="">All contributors</option>
                @foreach ($contributors as $contributor)
                    <option value="{{ $contributor->id }}" @selected((string) request('contributor_id') === (string) $contributor->id)>
                        {{ $contributor->name }} ({{ $contributor->email }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="date_from">From</label>
            <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="field">
            <label for="date_to">To</label>
            <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}">
        </div>
        <div class="actions filter-action">
            <button class="button secondary" type="submit">Filter</button>
            <a class="button secondary" href="{{ route('admin.community-contributions.submissions') }}">Clear</a>
        </div>
    </form>

    @if ($submissions->isEmpty())
        <section class="panel empty-state">
            <h2>No pending submissions</h2>
            <p>The review queue is currently empty.</p>
        </section>
    @else
        <div class="record-list">
            @foreach ($submissions as $submission)
                <article class="record-card">
                    <div>
                        <span class="badge badge-{{ $submission->status }}">{{ $submission->statusLabel() }}</span>
                        <h2>{{ $submission->contribution_title ?: $submission->shop_name }}</h2>
                        <p>{{ str($submission->heritage_story)->limit(180) }}</p>
                        <div class="record-meta">
                            <span>Contributor: {{ $submission->user?->name ?? 'Deleted user' }}</span>
                            <span>Shop: {{ $submission->shop_name }}</span>
                            <span>Location: {{ collect([$submission->city, $submission->state])->filter()->join(', ') ?: $submission->address }}</span>
                            <span>Established: {{ $submission->establishment_year ?: 'Unknown' }}</span>
                            <span>Food type: {{ $submission->primary_food_category ?: 'Not provided' }}</span>
                            <span>Submitted: {{ optional($submission->submitted_at)->format('d M Y, g:i A') }}</span>
                        </div>
                    </div>
                    <div class="record-actions">
                        <a class="button info small" href="{{ route('admin.community-contributions.show', $submission) }}">Review details</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="pagination">{{ $submissions->links() }}</div>
    @endif
@endsection
