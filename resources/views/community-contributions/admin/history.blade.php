@extends('admin.layout')

@section('title', 'Admin History')
@section('page-title', 'Community Contribution')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Administrator function 2</p>
            <h1>Admin History</h1>
            <p>Search processed Community Contribution submissions and review their recorded moderation activity.</p>
        </div>
        <a class="button secondary" href="{{ route('admin.community-contributions.submissions') }}">Review queue</a>
    </header>

    <form class="filters four" method="GET" action="{{ route('admin.community-contributions.history') }}">
        <div class="field">
            <label for="search">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Title, shop, or contributor">
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All outcomes</option>
                @foreach ($historyStatuses as $status)
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
            <a class="button secondary" href="{{ route('admin.community-contributions.history') }}">Clear</a>
        </div>
    </form>

    @if ($history->isEmpty())
        <section class="panel empty-state">
            <h2>No history available</h2>
            <p>No processed submission matches the selected filters.</p>
        </section>
    @else
        <div class="record-list">
            @foreach ($history as $record)
                @php $latestActivity = $record->moderationActivities->first(); @endphp
                <article class="record-card">
                    <div>
                        <span class="badge badge-{{ $record->status }}">{{ $record->statusLabel() }}</span>
                        <h2>{{ $record->contribution_title ?: $record->shop_name }}</h2>
                        <p>{{ str($record->heritage_story)->limit(180) }}</p>
                        <div class="record-meta">
                            <span>Contributor: {{ $record->user?->name ?? 'Deleted user' }}</span>
                            <span>Shop: {{ $record->shop_name }}</span>
                            <span>Location: {{ collect([$record->city, $record->state])->filter()->join(', ') ?: $record->address }}</span>
                            <span>Established: {{ $record->establishment_year ?: 'Unknown' }}</span>
                            <span>Food type: {{ $record->primary_food_category ?: 'Not provided' }}</span>
                            <span>Last action: {{ $latestActivity ? str($latestActivity->action)->replace('_', ' ')->title() : 'No audit action' }}</span>
                            <span>Updated: {{ $record->updated_at->format('d M Y, g:i A') }}</span>
                        </div>
                    </div>
                    <div class="record-actions">
                        <a class="button secondary small" href="{{ route('admin.community-contributions.show', $record) }}">View audit details</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="pagination">{{ $history->links() }}</div>
    @endif
@endsection
