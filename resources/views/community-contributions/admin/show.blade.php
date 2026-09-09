@extends('admin.layout')

@section('title', 'Review Submission')
@section('page-title', 'Community Contribution')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Review submission</p>
            <h1>{{ $contribution->contribution_title ?: $contribution->shop_name }}</h1>
            <p>Submitted by {{ $contribution->user?->name ?? 'Deleted user' }} on {{ $contribution->formatDateTime($contribution->submitted_at, 'Unknown date') }}</p>
        </div>
        <div class="actions">
            <span class="badge badge-{{ $contribution->status }}">{{ $contribution->statusLabel() }}</span>
            <a class="button secondary small" href="{{ route('admin.community-contributions.submissions') }}">Review queue</a>
        </div>
    </header>

    <div class="admin-scroll">
        <div class="detail-grid">
            <div class="detail-stack">
                <section class="panel">
                    <h2>Submitted heritage shop information</h2>
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
                    <h2>Supporting images</h2>
                    @if ($contribution->media->isNotEmpty())
                        @if ($contribution->status === \App\Models\HeritageShopContribution::STATUS_UNDER_REVIEW)
                            <p class="muted">Select at least one suitable image to publish. The Heritage Shop gallery allows up to {{ config('heritage_shop.max_gallery_images', 10) }} JPG, JPEG, PNG, or WebP images, each no larger than {{ round(config('heritage_shop.max_image_kb', 2048) / 1024, 1) }} MB.</p>
                        @endif
                        <div class="media-grid" style="margin-top:14px">
                            @foreach ($contribution->media as $media)
                                @php
                                    $isSupportedGalleryImage = $media->media_type === 'image' && in_array(strtolower((string) $media->mime_type), ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'], true);
                                    $isWithinGallerySize = ! $media->file_size_bytes || $media->file_size_bytes <= config('heritage_shop.max_image_bytes', 2 * 1024 * 1024);
                                    $isPublishableImage = $isSupportedGalleryImage && $isWithinGallerySize;
                                @endphp
                                <article class="media-card review-media-card">
                                    <a class="review-media-preview" href="{{ $media->url }}" target="_blank" rel="noopener">
                                        <img src="{{ $media->url }}" alt="Supporting evidence">
                                    </a>
                                    @if ($contribution->status === \App\Models\HeritageShopContribution::STATUS_UNDER_REVIEW && $isPublishableImage)
                                        <label class="publish-media-option">
                                            <input type="checkbox" name="publish_media_ids[]" value="{{ $media->id }}" form="moderation-form" @checked(in_array((string) $media->id, old('publish_media_ids', []), true))>
                                            <span>Publish to Heritage Shop</span>
                                        </label>
                                    @else
                                        <p class="review-only-label">
                                            {{ $isSupportedGalleryImage && ! $isWithinGallerySize ? 'Evidence only — exceeds the gallery image limit' : 'Review evidence only' }}
                                        </p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="muted">No supporting images were uploaded.</p>
                    @endif
                </section>
            </div>

            <aside class="detail-stack">
                <section class="panel">
                    <h2>Moderation action</h2>
                    @if ($contribution->status === \App\Models\HeritageShopContribution::STATUS_PENDING_REVIEW)
                        <p class="muted">Starting review locks withdrawal for the contributor and records the review time.</p>
                        <form method="POST" action="{{ route('admin.community-contributions.start-review', $contribution) }}">
                            @csrf
                            <button class="button info" type="submit">Start review</button>
                        </form>
                    @elseif ($contribution->status === \App\Models\HeritageShopContribution::STATUS_UNDER_REVIEW)
                        <form id="moderation-form" class="form-grid" method="POST" action="{{ route('admin.community-contributions.moderate', $contribution) }}" style="margin-top:14px">
                            @csrf
                            <div class="field">
                                <label for="feedback">Administrator feedback</label>
                                <textarea id="feedback" name="feedback" placeholder="Required when rejecting or requesting revision">{{ old('feedback') }}</textarea>
                            </div>
                            <div class="actions">
                                <button class="button primary small" type="submit" name="moderation_action" value="approve">Approve</button>
                                <button class="button info small" type="submit" name="moderation_action" value="request_revision">Request revision</button>
                                <button class="button danger small" type="submit" name="moderation_action" value="reject">Reject</button>
                            </div>
                        </form>
                    @else
                        <p class="muted">This review has been processed. Its audit record appears below and in Admin History.</p>
                        @if ($contribution->admin_feedback)
                            <div class="status-banner" style="margin:12px 0 0">{{ $contribution->admin_feedback }}</div>
                        @endif
                    @endif
                </section>

                @if (in_array($contribution->status, [\App\Models\HeritageShopContribution::STATUS_PENDING_REVIEW, \App\Models\HeritageShopContribution::STATUS_UNDER_REVIEW], true))
                    <section class="panel">
                        <h2>Delete submission</h2>
                        <p class="muted">Use soft delete for inappropriate, spam, or duplicate submissions. The audit record remains in the database.</p>
                        <form class="form-grid" method="POST" action="{{ route('admin.community-contributions.moderate', $contribution) }}" style="margin-top:14px" onsubmit="return confirm('Soft-delete this contribution? It will be removed from active admin lists but retained for audit.')">
                            @csrf
                            <input type="hidden" name="moderation_action" value="delete">
                            <div class="field">
                                <label class="required" for="deletion_reason">Deletion reason</label>
                                <select id="deletion_reason" name="deletion_reason" required>
                                    <option value="">Choose a reason</option>
                                    <option value="Duplicate Submission">Duplicate Submission</option>
                                    <option value="Inappropriate Content">Inappropriate Content</option>
                                    <option value="Spam">Spam</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="deletion_comment">Deletion comment</label>
                                <textarea id="deletion_comment" name="deletion_comment" placeholder="Required when reason is Other">{{ old('deletion_comment') }}</textarea>
                            </div>
                            <button class="button danger" type="submit">Delete submission</button>
                        </form>
                    </section>
                @endif

                <section class="panel">
                    <h2>Moderation history</h2>
                    <div class="timeline">
                        @forelse ($contribution->moderationActivities as $activity)
                            <div class="timeline-item">
                                <strong>{{ str($activity->action)->replace('_', ' ')->title() }}</strong>
                                <p>{{ $contribution->formatDateTime($activity->created_at) }} by {{ $activity->actor?->name ?? 'Deleted user' }}</p>
                                @if ($activity->comment)<p>{{ $activity->comment }}</p>@endif
                            </div>
                        @empty
                            <p class="muted">No moderation action recorded yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="panel">
                    <h2>Edit/version history</h2>
                    <div class="timeline">
                        @forelse ($contribution->versions as $version)
                            <div class="timeline-item">
                                <strong>Version {{ $version->version_number }} - {{ str($version->reason)->replace('_', ' ')->title() }}</strong>
                                <p>{{ $contribution->formatDateTime($version->created_at) }} by {{ $version->user?->name ?? 'Deleted user' }}</p>
                            </div>
                        @empty
                            <p class="muted">No version record found.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .review-media-card { display: grid; align-content: start; }
        .review-media-preview { display: block; color: inherit; text-decoration: none; }
        .publish-media-option {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 44px;
            padding: 10px;
            border-top: 1px solid var(--line);
            background: rgba(196, 147, 60, .08);
            color: var(--ink);
            font-size: .78rem;
            font-weight: 850;
            cursor: pointer;
        }
        .publish-media-option input { width: 16px; height: 16px; accent-color: var(--accent); }
        .publish-media-option:has(input:checked) { background: rgba(41, 100, 71, .14); color: #296447; }
        .review-only-label {
            margin: 0;
            min-height: 40px;
            padding: 10px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            background: rgba(119, 102, 92, .08);
            font-size: .76rem;
            font-weight: 800;
        }
    </style>
@endpush
