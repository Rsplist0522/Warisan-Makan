@extends('community-contributions.layout')

@section('title', __('My Correction Requests'))

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">{{ __('Published shop corrections') }}</p>
            <h1>{{ __('My Correction Requests') }}</h1>
            <p>{{ __('Track corrections you submitted for already published Heritage Shop profiles.') }}</p>
        </div>

        <a
            class="button secondary"
            href="{{ route('heritage-shops.index') }}"
        >
            {{ __('Browse heritage shops') }}
        </a>
    </header>

    @if ($notifications->isNotEmpty())
        <section class="panel" style="margin-bottom:18px">
            <h2>{{ __('Recent correction notifications') }}</h2>

            <div style="margin-top:12px">
                @foreach ($notifications as $notification)
                    @php
                        $notificationCorrectionRequest = $notificationCorrectionRequests->get(
                            $notification->data['correction_request_id'] ?? null
                        );
                    @endphp

                    @if ($notificationCorrectionRequest)
                        <a
                            class="notification {{ $notification->read_at ? '' : 'unread' }}"
                            style="display:block;text-decoration:none"
                            href="{{ route('community-contribution.correction-requests.show', $notificationCorrectionRequest) }}"
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
        action="{{ route('community-contribution.correction-requests') }}"
    >
        <div class="field">
            <label for="search">{{ __('Search') }}</label>
            <input
                id="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="{{ __('Shop, field, or suggested value') }}"
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
                href="{{ route('community-contribution.correction-requests') }}"
            >
                {{ __('Clear') }}
            </a>
        </div>
    </form>

    @if ($correctionRequests->isEmpty())
        <section class="panel empty-state">
            <h2>{{ __('No correction requests found') }}</h2>
            <p>{{ __('Use the report button on a published Heritage Shop profile when you notice outdated or incorrect information.') }}</p>

            <a
                class="button primary"
                href="{{ route('heritage-shops.index') }}"
            >
                {{ __('Find a shop') }}
            </a>
        </section>
    @else
        <div class="record-list">
            @foreach ($correctionRequests as $correctionRequest)
                <article class="record-card">
                    <div>
                        <span class="badge badge-{{ $correctionRequest->status }}">
                            {{ __($correctionRequest->statusLabel()) }}
                        </span>

                        <h2>
                            {{ $correctionRequest->heritageShop?->shop_name
                                ?? __('Deleted heritage shop') }}
                        </h2>

                        <p>
                            {{ __($correctionRequest->fieldLabel()) }}:
                            {{ str($correctionRequest->suggestedValueDisplay())->limit(140) }}
                        </p>

                        <div class="record-meta">
                            <span>
                                {{ __('Submitted') }}:
                                {{ $correctionRequest->formatDateTime($correctionRequest->created_at) }}
                            </span>
                            <span>
                                {{ __('Updated') }}:
                                {{ $correctionRequest->formatDateTime($correctionRequest->updated_at) }}
                            </span>
                        </div>
                    </div>

                    <div class="record-actions">
                        <a
                            class="button secondary small"
                            href="{{ route('community-contribution.correction-requests.show', $correctionRequest) }}"
                        >
                            {{ __('View details') }}
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pagination">
            {{ $correctionRequests->links() }}
        </div>
    @endif
@endsection
