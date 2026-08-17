@extends('community-contributions.layout')

@section('title', 'My Correction Requests')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Published shop corrections</p>
            <h1>My Correction Requests</h1>
            <p>Track corrections you submitted for already published Heritage Shop profiles.</p>
        </div>
        <a class="button secondary" href="{{ route('heritage-shops.index') }}">Browse heritage shops</a>
    </header>

    @if ($notifications->isNotEmpty())
        <section class="panel" style="margin-bottom:18px">
            <h2>Recent correction notifications</h2>
            <div style="margin-top:12px">
                @foreach ($notifications as $notification)
                    <a class="notification {{ $notification->read_at ? '' : 'unread' }}" style="display:block;text-decoration:none" href="{{ route('community-contribution.correction-requests.show', $notification->data['correction_request_id']) }}">
                        <strong>{{ $notification->data['title'] }}</strong>
                        <p>{{ $notification->data['message'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <form class="filters" method="GET" action="{{ route('community-contribution.correction-requests') }}">
        <div class="field">
            <label for="search">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Shop, field, or suggested value">
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
        <div class="actions filter-action">
            <button class="button secondary" type="submit">Filter</button>
            <a class="button secondary" href="{{ route('community-contribution.correction-requests') }}">Clear</a>
        </div>
    </form>

    @if ($correctionRequests->isEmpty())
        <section class="panel empty-state">
            <h2>No correction requests found</h2>
            <p>Use the report button on a published Heritage Shop profile when you notice outdated or incorrect information.</p>
            <a class="button primary" href="{{ route('heritage-shops.index') }}">Find a shop</a>
        </section>
    @else
        <div class="record-list">
            @foreach ($correctionRequests as $correctionRequest)
                <article class="record-card">
                    <div>
                        <span class="badge badge-{{ $correctionRequest->status }}">{{ $correctionRequest->statusLabel() }}</span>
                        <h2>{{ $correctionRequest->heritageShop?->shop_name ?? 'Deleted heritage shop' }}</h2>
                        <p>{{ $correctionRequest->fieldLabel() }}: {{ str($correctionRequest->suggested_value)->limit(140) }}</p>
                        <div class="record-meta">
                            <span>Submitted: {{ $correctionRequest->created_at->format('d M Y, g:i A') }}</span>
                            <span>Updated: {{ $correctionRequest->updated_at->format('d M Y, g:i A') }}</span>
                        </div>
                    </div>
                    <div class="record-actions">
                        <a class="button secondary small" href="{{ route('community-contribution.correction-requests.show', $correctionRequest) }}">View details</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="pagination">{{ $correctionRequests->links() }}</div>
    @endif
@endsection
