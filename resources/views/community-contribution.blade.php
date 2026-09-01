@extends('community-contributions.layout')

@section('title', $contribution ? __('Edit Heritage Shop') : __('Submit Heritage Shop'))

@section('content')
    @php
        $isWithdrawnResubmission = $contribution?->status === \App\Models\HeritageShopContribution::STATUS_DRAFT
            && $contribution?->withdrawn_at !== null;
    @endphp

    <header class="page-header">
        <div>
            <p class="eyebrow">{{ __('Community Contribution') }}</p>
            <h1>{{ $isWithdrawnResubmission ? __('Edit & Resubmit Heritage Shop') : ($contribution ? __('Edit Heritage Shop') : __('Submit Heritage Shop')) }}</h1>
            <p>
                @if ($contribution?->status === \App\Models\HeritageShopContribution::STATUS_REVISION_REQUIRED)
                    {{ __('Read the administrator feedback, make the requested changes, and resubmit.') }}
                @elseif ($isWithdrawnResubmission)
                    {{ __('Update the withdrawn contribution, then save it as a draft or resubmit it for review.') }}
                @else
                    {{ __('Document a Malaysian heritage food business for administrator review.') }}
                @endif
            </p>
        </div>

        <div class="actions">
            @if ($contribution)
                <span class="badge badge-{{ $contribution->status }}">
                    {{ __($contribution->statusLabel()) }}
                </span>

                <a
                    class="button secondary small"
                    href="{{ $contribution->status === \App\Models\HeritageShopContribution::STATUS_DRAFT
                        ? route('community-contribution.drafts')
                        : route('community-contribution.contributions.show', $contribution) }}"
                >
                    {{ $contribution->status === \App\Models\HeritageShopContribution::STATUS_DRAFT
                        ? __('Back to Drafts')
                        : __('Back to Details') }}
                </a>
            @else
                <a
                    class="button secondary small"
                    href="{{ route('community-contribution.contributions') }}"
                >
                    {{ __('Back to My Contributions') }}
                </a>
            @endif
        </div>
    </header>

    @if ($contribution?->status === \App\Models\HeritageShopContribution::STATUS_REVISION_REQUIRED
        && $contribution->admin_feedback)
        <section class="status-banner error">
            <strong>{{ __('Administrator feedback') }}</strong>  

            {{ $contribution->admin_feedback }}
        </section>
    @elseif ($isWithdrawnResubmission)
        <section class="status-banner neutral">
            <strong>{{ __('Withdrawn contribution') }}</strong>

            {{ __('This contribution was withdrawn on :date. Its previous withdrawal record will stay in the activity history.', ['date' => $contribution->formatDateTime($contribution->withdrawn_at)]) }}
        </section>
    @endif

    @include('community-contributions.partials.form', [
        'contribution' => $contribution,
    ])
@endsection
