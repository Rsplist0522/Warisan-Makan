@extends('admin.layout')

@section('title', 'Review Correction Request')
@section('page-title', 'Community Contribution')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Review correction request</p>
            <h1>{{ $correctionRequest->heritageShop?->shop_name ?? 'Deleted heritage shop' }}</h1>
            <p>Submitted by {{ $correctionRequest->user?->name ?? 'Deleted user' }} on {{ $correctionRequest->created_at->format('d M Y, g:i A') }}</p>
        </div>
        <div class="actions">
            <span class="badge badge-{{ $correctionRequest->status }}">{{ $correctionRequest->statusLabel() }}</span>
            <a class="button secondary small" href="{{ route('admin.community-contributions.correction-requests') }}">Correction queue</a>
        </div>
    </header>

    <div class="admin-scroll">
        <div class="detail-grid">
            <div class="detail-stack">
                <section class="panel">
                    <h2>Correction request information</h2>
                    <dl class="definition-grid" style="margin-top:16px">
                        <div><dt>Contributor</dt><dd>{{ $correctionRequest->user?->name ?? 'Deleted user' }}<br>{{ $correctionRequest->user?->email }}</dd></div>
                        <div><dt>Heritage shop</dt><dd>{{ $correctionRequest->heritageShop?->shop_name ?? 'Deleted heritage shop' }}</dd></div>
                        <div><dt>Incorrect field</dt><dd>{{ $correctionRequest->fieldLabel() }}</dd></div>
                        <div><dt>Submitted</dt><dd>{{ $correctionRequest->created_at->format('d M Y, g:i A') }}</dd></div>
                        <div class="full"><dt>Current information</dt><dd>{{ $correctionRequest->current_value }}</dd></div>
                        <div class="full"><dt>Suggested corrected information</dt><dd>{{ $correctionRequest->suggested_value }}</dd></div>
                        <div class="full"><dt>Reason</dt><dd>{{ $correctionRequest->reason }}</dd></div>
                        @if ($correctionRequest->additional_information)
                            <div class="full"><dt>Additional information from user</dt><dd>{{ $correctionRequest->additional_information }}</dd></div>
                        @endif
                    </dl>
                </section>

                <section class="panel">
                    <h2>Supporting evidence</h2>
                    @if ($correctionRequest->evidence_paths)
                        <div class="media-grid" style="margin-top:14px">
                            @foreach ($correctionRequest->evidence_paths as $path)
                                @php $isImage = in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp']); @endphp
                                <a class="media-card" href="{{ asset('storage/'.$path) }}" target="_blank" rel="noopener">
                                    @if ($isImage)
                                        <img src="{{ asset('storage/'.$path) }}" alt="Correction evidence">
                                    @else
                                        <div style="height:150px;display:grid;place-items:center;padding:10px;color:var(--muted);text-align:center">View evidence file</div>
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
                    <h2>Review action</h2>
                    @if ($correctionRequest->status === \App\Models\CorrectionRequest::STATUS_PENDING)
                        <p class="muted">Start review before selecting an outcome.</p>
                        <form method="POST" action="{{ route('admin.community-contributions.correction-requests.start-review', $correctionRequest) }}">
                            @csrf
                            <button class="button info" type="submit">Start review</button>
                        </form>
                    @elseif ($correctionRequest->status === \App\Models\CorrectionRequest::STATUS_UNDER_REVIEW)
                        <form class="form-grid" method="POST" action="{{ route('admin.community-contributions.correction-requests.moderate', $correctionRequest) }}" style="margin-top:14px">
                            @csrf
                            <div class="field">
                                <label for="admin_comment">Administrator comment</label>
                                <textarea id="admin_comment" name="admin_comment" placeholder="Required when rejecting or requesting more information">{{ old('admin_comment') }}</textarea>
                            </div>
                            <div class="actions">
                                <button class="button primary small" type="submit" name="moderation_action" value="approve" onclick="return confirm('Approve this correction and update the allowed Heritage Shop field?')">Approve</button>
                                <button class="button info small" type="submit" name="moderation_action" value="needs_information">Request additional information</button>
                                <button class="button danger small" type="submit" name="moderation_action" value="reject">Reject</button>
                            </div>
                        </form>
                    @else
                        <p class="muted">This correction request has been processed.</p>
                        @if ($correctionRequest->admin_comment)
                            <div class="status-banner" style="margin:12px 0 0">{{ $correctionRequest->admin_comment }}</div>
                        @endif
                    @endif
                </section>

                <section class="panel">
                    <h2>Moderation history</h2>
                    <div class="timeline">
                        @forelse ($correctionRequest->moderationActivities as $activity)
                            <div class="timeline-item">
                                <strong>{{ str($activity->action)->replace('_', ' ')->title() }}</strong>
                                <p>{{ $activity->created_at->format('d M Y, g:i A') }} by {{ $activity->actor?->name ?? 'Deleted user' }}</p>
                                @if ($activity->comment)<p>{{ $activity->comment }}</p>@endif
                            </div>
                        @empty
                            <p class="muted">No moderation action recorded yet.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection
