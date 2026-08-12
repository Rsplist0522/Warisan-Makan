@extends('community-contributions.layout')

@section('title', $contribution ? 'Edit Heritage Shop' : 'Submit Heritage Shop')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Community contribution</p>
            <h1>{{ $contribution ? 'Edit Heritage Shop' : 'Submit Heritage Shop' }}</h1>
            <p>
                {{ $contribution?->status === \App\Models\HeritageShopContribution::STATUS_REVISION_REQUIRED
                    ? 'Read the administrator feedback, make the requested changes, and resubmit.'
                    : 'Document a Malaysian heritage food business for administrator review.' }}
            </p>
        </div>
        @if ($contribution)
            <span class="badge badge-{{ $contribution->status }}">{{ $contribution->statusLabel() }}</span>
        @endif
    </header>

    @if ($contribution?->status === \App\Models\HeritageShopContribution::STATUS_REVISION_REQUIRED && $contribution->admin_feedback)
        <section class="status-banner error">
            <strong>Administrator feedback</strong><br>
            {{ $contribution->admin_feedback }}
        </section>
    @endif

    @include('community-contributions.partials.form', ['contribution' => $contribution])
@endsection
