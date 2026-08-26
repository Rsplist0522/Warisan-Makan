@extends('community-contributions.layout')

@section('title', $contribution ? __('Edit Heritage Shop') : __('Submit Heritage Shop'))

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">{{ __('Community Contribution') }}</p>
            <h1>{{ $contribution ? __('Edit Heritage Shop') : __('Submit Heritage Shop') }}</h1>
            <p>
                {{ $contribution?->status === \App\Models\HeritageShopContribution::STATUS_REVISION_REQUIRED
                    ? 'Read the administrator feedback, make the requested changes, and resubmit.'
                    : __('Document a Malaysian heritage food business for administrator review.') }}
            </p>
        </div>
        <div class="actions">
            @if ($contribution)
                <span class="badge badge-{{ $contribution->status }}">{{ $contribution->statusLabel() }}</span>
                <a class="button secondary small" href="{{ $contribution->status === \App\Models\HeritageShopContribution::STATUS_DRAFT ? route('community-contribution.drafts') : route('community-contribution.contributions.show', $contribution) }}">
                    {{ $contribution->status === \App\Models\HeritageShopContribution::STATUS_DRAFT ? 'Back to Drafts' : 'Back to Details' }}
                </a>
            @else
                <a class="button secondary small" href="{{ route('community-contribution.contributions') }}">Back to My Contributions</a>
            @endif
        </div>
    </header>

    @if ($contribution?->status === \App\Models\HeritageShopContribution::STATUS_REVISION_REQUIRED && $contribution->admin_feedback)
        <section class="status-banner error">
            <strong>Administrator feedback</strong><br>
            {{ $contribution->admin_feedback }}
        </section>
    @endif

    @include('community-contributions.partials.form', ['contribution' => $contribution])
@endsection
