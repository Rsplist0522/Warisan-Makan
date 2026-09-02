@extends('admin.layout')

@section('title', 'Correction Requests')
@section('page-title', 'Community Contribution')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Administrator function</p>
            <h1>Correction Requests</h1>
            <p>Review reports for already published Heritage Shop profiles.</p>
        </div>
        <a class="button secondary" href="{{ route('admin.community-contributions.submissions') }}">Contribution submissions</a>
    </header>

    <form class="filters four" method="GET" action="{{ route('admin.community-contributions.correction-requests') }}">
        <div class="field">
            <label for="search">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Shop, contributor, field, or value">
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                @foreach ($allowedStatuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
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
            <a class="button secondary" href="{{ route('admin.community-contributions.correction-requests') }}">Clear</a>
        </div>
    </form>

    @if ($correctionRequests->isEmpty())
        <section class="panel empty-state">
            <h2>No correction requests found</h2>
            <p>No published shop correction request matches the selected filters.</p>
        </section>
    @else
        <div class="admin-scroll">
            <div class="record-list">
                @foreach ($correctionRequests as $correctionRequest)
                    <article class="record-card">
                        <div>
                            <span class="badge badge-{{ $correctionRequest->status }}">{{ $correctionRequest->statusLabel() }}</span>
                            <h2>{{ $correctionRequest->heritageShop?->shop_name ?? 'Deleted heritage shop' }}</h2>
                            <p>{{ $correctionRequest->fieldLabel() }}: {{ str($correctionRequest->suggestedValueDisplay())->limit(160) }}</p>
                            <div class="record-meta">
                                <span>Contributor: {{ $correctionRequest->user?->name ?? 'Deleted user' }}</span>
                                <span>Field: {{ $correctionRequest->fieldLabel() }}</span>
                                <span>Submitted: {{ $correctionRequest->formatDateTime($correctionRequest->created_at) }}</span>
                            </div>
                        </div>
                        <div class="record-actions">
                            <a class="button info small" href="{{ route('admin.community-contributions.correction-requests.show', $correctionRequest) }}">Review details</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
        <div class="admin-scroll pagination">{{ $correctionRequests->links() }}</div>
    @endif
@endsection
