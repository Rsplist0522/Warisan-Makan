@extends('community-contributions.layout')

@section('title', 'Contribution Details')

@section('content')
    @php
        $currentRevisionFeedback = $contribution->currentRevisionFeedback();
        $previousRevisionFeedback = $contribution->previousRevisionFeedback();
        $rejectionFeedback = $contribution->rejection_reason ?: $contribution->admin_feedback;
    @endphp

    <header class="page-header">
        <div>
            <p class="eyebrow">Contribution details</p>
            <h1>{{ $contribution->contribution_title ?: $contribution->shop_name }}</h1>
            <p>
                @if ($contribution->wasResubmitted())
                    Resubmitted {{ $contribution->formatDateTime($contribution->latestSubmissionOccurredAt()) }}
                    <span class="submission-secondary">Originally submitted {{ $contribution->formatDateTime($contribution->submitted_at, 'date not available') }}</span>
                @else
                    Submitted {{ $contribution->formatDateTime($contribution->submitted_at, 'as a draft') }}
                @endif
            </p>
        </div>
        <div class="actions">
            <span class="badge badge-{{ $contribution->status }}">{{ $contribution->statusLabel() }}</span>
            <a class="button secondary small" href="{{ route('community-contribution.contributions') }}">Back</a>
        </div>
    </header>

    @if ($contribution->status === \App\Models\HeritageShopContribution::STATUS_DELETED)
        <section class="status-banner error">
            <strong>Deleted by administrator</strong><br>
            {{ $contribution->admin_feedback ?: 'No deletion reason was provided.' }}
        </section>
    @else
        @if ($contribution->status === \App\Models\HeritageShopContribution::STATUS_WITHDRAWN)
            <section class="status-banner neutral">
                <strong>Withdrawn by contributor</strong><br>
                This contribution was withdrawn on {{ $contribution->formatDateTime($contribution->withdrawn_at) }}. You can edit and resubmit it when ready.
            </section>
        @endif

        @if ($contribution->status === \App\Models\HeritageShopContribution::STATUS_REJECTED && $rejectionFeedback)
            <section class="status-banner error">
                <strong>Rejection reason</strong><br>
                {{ $rejectionFeedback }}
            </section>
        @elseif ($contribution->status === \App\Models\HeritageShopContribution::STATUS_APPROVED && $contribution->admin_feedback)
            <section class="status-banner success">
                <strong>Administrator feedback</strong><br>
                {{ $contribution->admin_feedback }}
            </section>
        @endif

        @if ($currentRevisionFeedback)
            <section class="status-banner error">
                <strong>Administrator feedback</strong><br>
                {{ $currentRevisionFeedback }}
            </section>
        @elseif ($previousRevisionFeedback)
            <section class="status-banner neutral">
                <strong>Previous administrator feedback</strong><br>
                {{ $previousRevisionFeedback }}
                <p class="status-note">Addressed in the latest resubmission.</p>
            </section>
        @endif
    @endif
    <div class="detail-grid">
        <div class="detail-stack">
            <section class="panel">
                <h2>Shop information</h2>
                <dl class="definition-grid" style="margin-top:16px">
                    <div><dt>Shop name</dt><dd>{{ $contribution->shop_name }}</dd></div>
                    <div><dt>Category</dt><dd>{{ $contribution->primary_food_category }}</dd></div>
                    <div><dt>Established</dt><dd>{{ $contribution->establishment_year }}</dd></div>
                    <div><dt>Contact</dt><dd>{{ $contribution->contact_number ?: 'Not provided' }}</dd></div>
                    <div><dt>Founder</dt><dd>{{ $contribution->founder_name }}</dd></div>
                    <div><dt>Current owner</dt><dd>{{ $contribution->current_owner_name }}</dd></div>
                    <div class="full"><dt>Founder background</dt><dd>{{ $contribution->founder_background }}</dd></div>
                    <div class="full"><dt>Current owner details</dt><dd>{{ $contribution->current_owner_details ?: 'Not provided' }}</dd></div>
                    <div class="full"><dt>Heritage story</dt><dd>{{ $contribution->heritage_story }}</dd></div>
                    <div class="full"><dt>Address</dt><dd>{{ collect([$contribution->address, $contribution->city, $contribution->state, $contribution->postal_code])->filter()->join(', ') }}</dd></div>
                </dl>
            </section>

            @include('community-contributions.partials.food-items-hours', ['contribution' => $contribution])

            <section class="panel">
                <h2>Supporting media</h2>
                @if ($contribution->media->isNotEmpty())
                    <div class="media-grid" style="margin-top:14px">
                        @foreach ($contribution->media as $media)
                            <a class="media-card" href="{{ $media->url }}" target="_blank" rel="noopener">
                                @if ($media->media_type === 'video')
                                    <video controls preload="metadata"><source src="{{ $media->url }}"></video>
                                @else
                                    <img src="{{ $media->url }}" alt="Supporting evidence">
                                @endif
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="muted">No media uploaded.</p>
                @endif
            </section>
        </div>

        <aside class="detail-stack">
            <section class="panel">
                <h2>Available action</h2>
                <div class="actions" style="margin-top:14px">
                    @if ($contribution->canBeWithdrawnBy(auth()->user()))
                        <form method="POST" action="{{ route('community-contribution.contributions.withdraw', $contribution) }}" onsubmit="return confirm(@json(__('Withdraw this contribution? It will be removed from the review queue, but you can edit and resubmit it later.')))">
                            @csrf
                            <button class="button danger" type="submit">Withdraw submission</button>
                        </form>
                    @elseif ($contribution->status === \App\Models\HeritageShopContribution::STATUS_WITHDRAWN)
                        <form method="POST" action="{{ route('community-contribution.contributions.edit-resubmit', $contribution) }}">
                            @csrf
                            <button class="button info" type="submit">Edit & Resubmit</button>
                        </form>
                    @elseif ($contribution->status === \App\Models\HeritageShopContribution::STATUS_REVISION_REQUIRED)
                        <a class="button info" href="{{ route('community-contribution.edit', $contribution) }}">Revise and resubmit</a>
                    @else
                        <p class="muted">No action is currently required.</p>
                    @endif
                </div>
            </section>

            <section class="panel">
                <h2>Version history</h2>
                <div class="timeline">
                    @forelse ($contribution->versions as $version)
                        <div class="timeline-item">
                            <strong>Version {{ $version->version_number }} · {{ str($version->reason)->replace('_', ' ')->title() }}</strong>
                            <p>{{ $contribution->formatDateTime($version->created_at) }} by {{ $version->user?->name ?? 'Unknown user' }}</p>
                        </div>
                    @empty
                        <p class="muted">No version records are available.</p>
                    @endforelse
                </div>
            </section>

            @if ($contribution->moderationActivities->isNotEmpty())
                <section class="panel">
                    <h2>Status activity</h2>
                    <div class="timeline">
                        @foreach ($contribution->moderationActivities as $activity)
                            <div class="timeline-item">
                                <strong>{{ str($activity->action)->replace('_', ' ')->title() }}</strong>
                                <p>{{ $contribution->formatDateTime($activity->created_at) }}</p>
                                @if ($activity->comment)<p>{{ $activity->comment }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </div>
@endsection
