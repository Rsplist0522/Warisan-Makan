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

    <form class="filters six" method="GET" action="{{ route('admin.community-contributions.history') }}">
        <div class="field">
            <label for="search">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Title, shop, contributor, or field">
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
            <label for="record_type">Record type</label>
            <select id="record_type" name="record_type">
                <option value="">All</option>
                @foreach ($recordTypes as $value => $label)
                    <option value="{{ $value }}" @selected(request('record_type') === $value)>{{ $label }}</option>
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

    @if ($paginatedHistory->isEmpty())
        <section class="panel empty-state">
            <h2>No history available</h2>
            <p>No processed submission matches the selected filters.</p>
        </section>
    @else
        <div class="record-list">
            @foreach ($paginatedHistory as $entry)
                @php
                    $record = $entry->record;
                    $recordType = $entry->record_type;
                    $latestActivity = $record->moderationActivities->first();
                    $linkRoute = $recordType === 'shop_submission' ? route('admin.community-contributions.show', $record) : route('admin.community-contributions.correction-requests.show', $record);
                    $title = $recordType === 'shop_submission' ? ($record->contribution_title ?: $record->shop_name) : ($record->heritageShop?->shop_name ?? 'Deleted heritage shop');
                    $recordTypeLabel = $recordType === 'shop_submission' ? 'SHOP SUBMISSION' : 'CORRECTION';
                    $detailLabel = strtoupper($record->statusLabel()).' · '.$recordTypeLabel;
                @endphp
                <article class="record-card">
                    <div>
                        <span class="badge badge-{{ $record->status }}">{{ $detailLabel }}</span>
                        <h2>{{ $title }}</h2>
                        @if ($recordType === 'shop_submission')
                            <p>{{ str($record->heritage_story)->limit(180) }}</p>
                            <div class="record-meta">
                                <span>Contributor: {{ $record->user?->name ?? 'Deleted user' }}</span>
                                <span>Shop: {{ $record->shop_name }}</span>
                                <span>Location: {{ collect([$record->city, $record->state])->filter()->join(', ') ?: $record->address }}</span>
                                <span>Established: {{ $record->establishment_year ?: 'Unknown' }}</span>
                                <span>Food type: {{ $record->primary_food_category ?: 'Not provided' }}</span>
                                <span>Last action: {{ $latestActivity ? str($latestActivity->action)->replace('_', ' ')->title() : 'No audit action' }}</span>
                                <span>Updated: {{ $record->formatDateTime($record->updated_at) }}</span>
                                @if ($record->status === \App\Models\HeritageShopContribution::STATUS_DELETED && $record->admin_feedback)
                                    <span>Delete reason: {{ $record->admin_feedback }}</span>
                                @endif
                            </div>
                        @else
                            <p>{{ $record->fieldLabel() }}: {{ str($record->suggestedValueDisplay())->limit(160) }}</p>
                            <div class="record-meta">
                                <span>Contributor: {{ $record->user?->name ?? 'Deleted user' }}</span>
                                <span>Field: {{ $record->fieldLabel() }}</span>
                                <span>Processed: {{ $record->formatDateTime($record->reviewed_at ?? $record->updated_at ?? $record->created_at) }}</span>
                                <span>Outcome: {{ $record->statusLabel() }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="record-actions">
                        <a class="button secondary small" href="{{ $linkRoute }}">View audit details</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="pagination">{{ $paginatedHistory->links() }}</div>
    @endif
@endsection
