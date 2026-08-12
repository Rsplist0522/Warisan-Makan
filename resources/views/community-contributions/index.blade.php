@extends('community-contributions.layout')

@section('title', 'Manage Contributions')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">User function 3</p>
            <h1>My Contributions</h1>
            <p>View submission status, administrator feedback, versions, and eligible actions.</p>
        </div>
        <a class="button primary" href="{{ route('community-contribution.create') }}">+ Submit heritage shop</a>
    </header>

    @if ($notifications->isNotEmpty())
        <section class="panel" style="margin-bottom: 18px">
            <h2>Recent notifications</h2>
            <div style="margin-top: 12px">
                @foreach ($notifications as $notification)
                    <a class="notification {{ $notification->read_at ? '' : 'unread' }}" style="display:block;text-decoration:none" href="{{ route('community-contribution.contributions.show', $notification->data['contribution_id']) }}">
                        <strong>{{ $notification->data['title'] }}</strong>
                        <p>{{ $notification->data['message'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <form class="filters" method="GET" action="{{ route('community-contribution.contributions') }}">
        <div class="field">
            <label for="search">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Contribution title or shop name">
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
            <a class="button secondary" href="{{ route('community-contribution.contributions') }}">Clear</a>
        </div>
    </form>

    @if ($contributions->isEmpty())
        <section class="panel empty-state">
            <h2>No contribution history found</h2>
            <p>Your submitted, approved, rejected, and withdrawn contributions will appear here.</p>
            <a class="button primary" href="{{ route('community-contribution.create') }}">Submit your first shop</a>
        </section>
    @else
        <div class="record-list">
            @foreach ($contributions as $contribution)
                <article class="record-card">
                    <div>
                        <span class="badge badge-{{ $contribution->status }}">{{ $contribution->statusLabel() }}</span>
                        <h2>{{ $contribution->contribution_title ?: $contribution->shop_name }}</h2>
                        <div class="record-meta">
                            <span>Shop: {{ $contribution->shop_name }}</span>
                            <span>Submitted: {{ optional($contribution->submitted_at)->format('d M Y, g:i A') ?: 'Not available' }}</span>
                            <span>Updated: {{ $contribution->updated_at->format('d M Y, g:i A') }}</span>
                        </div>
                    </div>
                    <div class="record-actions">
                        @if ($contribution->status === \App\Models\HeritageShopContribution::STATUS_REVISION_REQUIRED)
                            <a class="button info small" href="{{ route('community-contribution.edit', $contribution) }}">Revise</a>
                        @endif
                        <a class="button secondary small" href="{{ route('community-contribution.contributions.show', $contribution) }}">View details</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="pagination">{{ $contributions->links() }}</div>
    @endif
@endsection
