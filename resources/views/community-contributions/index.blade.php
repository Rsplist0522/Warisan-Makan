@extends('community-contributions.layout')

@section('title', __('Manage Contributions'))

@section('content')
    <style>
        .record-food-items {
            display: grid;
            gap: 8px;
            margin-top: 12px;
        }

        .record-food-item {
            display: grid;
            grid-template-columns: 54px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            color: var(--wm-muted);
            font-size: .82rem;
        }

        .record-food-item.no-image {
            grid-template-columns: 1fr;
        }

        .record-food-item img {
            width: 54px;
            height: 46px;
            border: 1px solid var(--wm-border);
            border-radius: 8px;
            object-fit: cover;
        }

        .record-food-item p {
            margin: 2px 0 0;
        }
    </style>

    <header class="page-header">
        <div>
            <p class="eyebrow">{{ __('Contribution history') }}</p>
            <h1>{{ __('My Contributions') }}</h1>
            <p>{{ __('View submission status, administrator feedback, versions, and eligible actions.') }}</p>
        </div>

        <a class="button primary" href="{{ route('community-contribution.create') }}">
            + {{ __('Submit heritage shop') }}
        </a>
    </header>

    @if ($notifications->isNotEmpty())
        <section class="panel" style="margin-bottom: 18px">
            <h2>{{ __('Unread notifications') }}</h2>

            <div style="margin-top: 12px">
                @foreach ($notifications as $notification)
                    @php
                        $notificationContribution = $notificationContributions->get(
                            $notification->data['contribution_id'] ?? null
                        );
                    @endphp

                    @if ($notificationContribution)
                        <a
                            class="notification {{ $notification->read_at ? '' : 'unread' }}"
                            style="display:block;text-decoration:none"
                            href="{{ route('community-contribution.contributions.show', $notificationContribution) }}"
                        >
                            <strong>{{ $notification->data['title'] }}</strong>
                            <p>{{ $notification->data['message'] }}</p>
                        </a>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    <form
        class="filters"
        method="GET"
        action="{{ route('community-contribution.contributions') }}"
    >
        <div class="field">
            <label for="search">{{ __('Search') }}</label>
            <input
                id="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="{{ __('Contribution title or shop name') }}"
            >
        </div>

        <div class="field">
            <label for="status">{{ __('Status') }}</label>
            <select id="status" name="status">
                <option value="">{{ __('All statuses') }}</option>

                @foreach ($allowedStatuses as $status)
                    @php
                        $statusText = str($status)
                            ->replace('_', ' ')
                            ->title()
                            ->toString();
                    @endphp

                    <option
                        value="{{ $status }}"
                        @selected(request('status') === $status)
                    >
                        {{ __($statusText) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="actions filter-action">
            <button class="button secondary" type="submit">
                {{ __('Filter') }}
            </button>

            <a
                class="button secondary"
                href="{{ route('community-contribution.contributions') }}"
            >
                {{ __('Clear') }}
            </a>
        </div>
    </form>

    @if ($contributions->isEmpty())
        <section class="panel empty-state">
            <h2>{{ __('No contribution history found') }}</h2>
            <p>{{ __('Your submitted, approved, rejected, and withdrawn contributions will appear here.') }}</p>

            <a
                class="button primary"
                href="{{ route('community-contribution.create') }}"
            >
                {{ __('Submit your first shop') }}
            </a>
        </section>
    @else
        <div class="record-list">
            @foreach ($contributions as $contribution)
                <article class="record-card">
                    <div>
                        <span class="badge badge-{{ $contribution->status }}">
                            {{ __($contribution->statusLabel()) }}
                        </span>

                        <h2>
                            {{ $contribution->contribution_title ?: $contribution->shop_name }}
                        </h2>

                        <div class="record-meta">
                            <span>
                                {{ __('Shop') }}:
                                {{ $contribution->shop_name }}
                            </span>

                            <span>Submitted: {{ $contribution->formatDateTime($contribution->submitted_at) }}</span>

                            <span>
                                {{ __('Updated') }}:
                                {{ $contribution->formatDateTime($contribution->updated_at) }}
                            </span>

                            @if (
                                $contribution->status === \App\Models\HeritageShopContribution::STATUS_DELETED
                                && $contribution->admin_feedback
                            )
                                <span>Deleted by admin: {{ $contribution->admin_feedback }}</span>
                            @endif
                        </div>

                        @if (! empty($contribution->food_items))
                            <div class="record-food-items">
                                @foreach ($contribution->food_items as $item)
                                    @php($foodItemImageUrl = $contribution->foodItemImageUrl($item['image_path'] ?? null))
                                    <article class="record-food-item {{ $foodItemImageUrl ? '' : 'no-image' }}">
                                        @if ($foodItemImageUrl)
                                            <img src="{{ $foodItemImageUrl }}" alt="{{ $item['name'] ?? 'Food item image' }}">
                                        @endif
                                        <div>
                                            <strong>{{ $item['name'] ?: __('Unnamed item') }}</strong>
                                            @if (filled($item['desc'] ?? null))
                                                <p>{{ $item['desc'] }}</p>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="record-actions">
                        @if ($contribution->status === \App\Models\HeritageShopContribution::STATUS_REVISION_REQUIRED)
                            <a
                                class="button info small"
                                href="{{ route('community-contribution.edit', $contribution) }}"
                            >
                                {{ __('Revise') }}
                            </a>
                        @endif

                        <a
                            class="button secondary small"
                            href="{{ route('community-contribution.contributions.show', $contribution) }}"
                        >
                            {{ __('View details') }}
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pagination">
            {{ $contributions->links() }}
        </div>
    @endif
@endsection
