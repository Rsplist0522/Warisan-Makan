@extends('admin.layout')

@section('title', 'Review Submission')
@section('page-title', 'Community Contribution')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Review submission</p>
            <h1>{{ $contribution->contribution_title ?: $contribution->shop_name }}</h1>
            <p>Submitted by {{ $contribution->user?->name ?? 'Deleted user' }} on {{ optional($contribution->submitted_at)->format('d M Y, g:i A') ?: 'Unknown date' }}</p>
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
                        <div class="full">
                            <dt>Food items</dt>
                            <dd>
                                @forelse ($contribution->food_items ?? [] as $item)
                                    <strong>{{ $item['name'] ?: 'Unnamed item' }}</strong>{{ filled($item['desc'] ?? null) ? ': '.$item['desc'] : '' }}<br>
                                @empty Not provided @endforelse
                            </dd>
                        </div>
                    </dl>
                </section>

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
                        <p class="muted">No supporting media was uploaded.</p>
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
                        <form class="form-grid" method="POST" action="{{ route('admin.community-contributions.moderate', $contribution) }}" style="margin-top:14px">
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
                                <p>{{ $activity->created_at->format('d M Y, g:i A') }} by {{ $activity->actor?->name ?? 'Deleted user' }}</p>
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
                                <p>{{ $version->created_at->format('d M Y, g:i A') }} by {{ $version->user?->name ?? 'Deleted user' }}</p>
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
