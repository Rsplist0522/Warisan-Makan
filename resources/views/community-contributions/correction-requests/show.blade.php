@extends('community-contributions.layout')

@section('title', 'Correction Request Details')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Correction request details</p>
            <h1>{{ $correctionRequest->heritageShop?->shop_name ?? 'Deleted heritage shop' }}</h1>
            <p>Submitted {{ $correctionRequest->formatDateTime($correctionRequest->created_at) }}</p>
        </div>
        <div class="actions">
            <span class="badge badge-{{ $correctionRequest->status }}">{{ $correctionRequest->statusLabel() }}</span>
            <a class="button secondary small" href="{{ route('community-contribution.correction-requests') }}">Back</a>
        </div>
    </header>

    @if ($correctionRequest->admin_comment)
        <section class="status-banner {{ $correctionRequest->status === \App\Models\CorrectionRequest::STATUS_APPROVED ? 'success' : 'error' }}">
            <strong>Administrator comment</strong><br>
            {{ $correctionRequest->admin_comment }}
        </section>
    @endif

    <div class="detail-grid">
        <div class="detail-stack">
            <section class="panel">
                <h2>Requested correction</h2>
                <dl class="definition-grid" style="margin-top:16px">
                    <div><dt>Heritage shop</dt><dd>{{ $correctionRequest->heritageShop?->shop_name ?? 'Deleted heritage shop' }}</dd></div>
                    <div><dt>Incorrect field</dt><dd>{{ $correctionRequest->fieldLabel() }}</dd></div>
                    <div class="full"><dt>Current information</dt><dd>{!! nl2br(e($correctionRequest->current_value)) !!}</dd></div>
                    <div class="full"><dt>Suggested corrected information</dt><dd>{!! nl2br(e($correctionRequest->suggestedValueDisplay())) !!}</dd></div>
                    <div class="full"><dt>Reason</dt><dd>{{ $correctionRequest->reason }}</dd></div>
                    @if ($correctionRequest->additional_information)
                        <div class="full"><dt>Additional information provided</dt><dd>{{ $correctionRequest->additional_information }}</dd></div>
                    @endif
                </dl>
            </section>

            <section class="panel">
                <h2>Supporting evidence</h2>
                @if ($correctionRequest->media->isNotEmpty())
                    <div class="media-grid" style="margin-top:14px">
                        @foreach ($correctionRequest->media as $media)
                            <a class="media-card" href="{{ $media->url }}" target="_blank" rel="noopener">
                                @if ($media->media_type === 'image')
                                    <img src="{{ $media->url }}" alt="Correction evidence">
                                @else
                                    <video controls preload="metadata"><source src="{{ $media->url }}"></video>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="muted">No evidence files were uploaded.</p>
                @endif
            </section>
        </div>

        <aside class="detail-stack">
            <section class="panel">
                <h2>Available action</h2>
                @if ($correctionRequest->canReceiveAdditionalInformationFrom(auth()->user()))
                    <form class="form-grid" method="POST" action="{{ route('community-contribution.correction-requests.additional-information', $correctionRequest) }}" enctype="multipart/form-data" style="margin-top:14px">
                        @csrf
                        <div class="field">
                            <label class="required" for="additional_information">Additional information</label>
                            <textarea id="additional_information" name="additional_information" required>{{ old('additional_information') }}</textarea>
                        </div>
                        <div class="field">
                            <label for="additional_evidence">Additional evidence</label>
                            <input id="additional_evidence" type="file" name="additional_evidence[]" multiple accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.avi">
                        </div>
                        <button class="button primary" type="submit">Send additional information</button>
                    </form>
                @else
                    <p class="muted">No action is currently required.</p>
                @endif
            </section>

            <section class="panel">
                <h2>Status activity</h2>
                <div class="timeline">
                    @forelse ($correctionRequest->moderationActivities as $activity)
                        <div class="timeline-item">
                            <strong>{{ str($activity->action)->replace('_', ' ')->title() }}</strong>
                            <p>{{ $correctionRequest->formatDateTime($activity->created_at) }} by {{ $activity->actor?->name ?? 'Unknown user' }}</p>
                            @if ($activity->comment)<p>{{ $activity->comment }}</p>@endif
                        </div>
                    @empty
                        <p class="muted">No activity recorded yet.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
@endsection
