<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use App\Models\HeritageShop;
use App\Models\HeritageShopContribution;
use App\Models\Media;
use App\Models\ModerationActivity;
use App\Models\User;
use App\Notifications\ContributionStatusChanged;
use App\Notifications\CorrectionRequestStatusChanged;
use App\Services\HeritageAuditLogger;
use App\Services\HeritageShopImageService;
use App\Services\HeritageShopIntegrityService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AdminCommunityContributionController extends Controller
{
    public function __construct(
        private HeritageShopImageService $imageService,
        private HeritageShopIntegrityService $integrityService,
        private HeritageAuditLogger $auditLogger,
    ) {}

    public function submissions(Request $request): View
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'contributor_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $activeStatuses = [
            HeritageShopContribution::STATUS_PENDING_REVIEW,
            HeritageShopContribution::STATUS_UNDER_REVIEW,
        ];
        $query = HeritageShopContribution::query()
            ->with('user')
            ->whereIn('status', $activeStatuses)
            ->oldest('submitted_at');

        if ($request->filled('status') && in_array($request->string('status')->toString(), $activeStatuses, true)) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim()->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('contribution_title', 'like', $search)
                    ->orWhere('shop_name', 'like', $search)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search));
            });
        }

        if ($request->filled('contributor_id')) {
            $query->where('user_id', $request->integer('contributor_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->date('date_to'));
        }

        $submissions = $query->paginate(15)->withQueryString();
        $contributors = User::query()
            ->whereHas('heritageShopContributions')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('community-contributions.admin.submissions', compact('submissions', 'activeStatuses', 'contributors'));
    }

    public function show(HeritageShopContribution $contribution): View
    {
        $contribution->load(['media', 'user', 'reviewedBy', 'versions.user', 'moderationActivities.actor']);

        return view('community-contributions.admin.show', compact('contribution'));
    }

    public function startReview(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        if ($contribution->status !== HeritageShopContribution::STATUS_PENDING_REVIEW) {
            return back()->withErrors(['review' => 'Only a pending contribution can enter review.']);
        }

        DB::transaction(function () use ($request, $contribution): void {
            $fromStatus = $contribution->status;
            $contribution->forceFill([
                'status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
                'reviewed_by_user_id' => $request->user()->id,
                'review_started_at' => now(),
            ])->save();
            $this->recordActivity(
                $contribution,
                $request->user()->id,
                'review_started',
                $fromStatus,
                HeritageShopContribution::STATUS_UNDER_REVIEW
            );
        });

        return back()->with('status', 'Review started. The contributor can no longer withdraw this submission.');
    }

    public function moderate(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        $validated = $request->validate([
            'moderation_action' => ['required', Rule::in(['approve', 'reject', 'request_revision', 'delete'])],
            'feedback' => [
                Rule::requiredIf(fn () => in_array($request->input('moderation_action'), ['reject', 'request_revision'], true)),
                'nullable',
                'string',
                'max:5000',
            ],
            'deletion_reason' => [
                Rule::requiredIf(fn () => $request->input('moderation_action') === 'delete'),
                'nullable',
                Rule::in(['Duplicate Submission', 'Inappropriate Content', 'Spam', 'Other']),
            ],
            'deletion_comment' => [
                Rule::requiredIf(fn () => $request->input('moderation_action') === 'delete' && $request->input('deletion_reason') === 'Other'),
                'nullable',
                'string',
                'max:1000',
            ],
            'publish_media_ids' => ['nullable', 'array'],
            'publish_media_ids.*' => ['integer'],
        ]);

        if ($validated['moderation_action'] !== 'delete' && $contribution->status !== HeritageShopContribution::STATUS_UNDER_REVIEW) {
            return back()->withErrors(['moderation' => 'Start the review before selecting a moderation outcome.']);
        }

        if ($validated['moderation_action'] === 'delete'
            && ! in_array($contribution->status, [
                HeritageShopContribution::STATUS_PENDING_REVIEW,
                HeritageShopContribution::STATUS_UNDER_REVIEW,
            ], true)) {
            return back()->withErrors(['moderation' => 'Only active submissions can be deleted from the review queue.']);
        }

        $admin = $request->user();
        $fromStatus = $contribution->status;
        $feedback = filled($validated['feedback'] ?? null)
            ? trim(strip_tags($validated['feedback']))
            : null;
        $publishableMedia = $validated['moderation_action'] === 'approve'
            ? $this->validatePublishableMediaSelection($contribution, $validated['publish_media_ids'] ?? [])
            : new EloquentCollection;
        $copiedShopImagePaths = [];

        try {
            DB::transaction(function () use (
                $validated,
                $contribution,
                $admin,
                $fromStatus,
                $feedback,
                $publishableMedia,
                &$copiedShopImagePaths
            ): void {
                if ($validated['moderation_action'] === 'approve') {
                    $shop = HeritageShop::query()
                        ->where('source_contribution_id', $contribution->getKey())
                        ->lockForUpdate()
                        ->first();
                    $newImageCount = $this->newCommunityGalleryImageCount($contribution, $shop, $publishableMedia);
                    $finalImageCount = $this->integrityService->finalGalleryCount($shop, [], $newImageCount);
                    $candidate = $contribution->heritageShopPayload();
                    $shopValues = $this->integrityService->validateFinalState(
                        $candidate,
                        $shop,
                        $finalImageCount,
                        $finalImageCount,
                    );
                    $oldValues = $shop?->getAttributes() ?? [];

                    if ($shop) {
                        $shop->fill([...$shopValues, 'publish_status' => HeritageShop::STATUS_DRAFT])->save();
                    } else {
                        $shop = HeritageShop::query()->create([
                            ...$shopValues,
                            'source_contribution_id' => $contribution->getKey(),
                            'publish_status' => HeritageShop::STATUS_DRAFT,
                        ]);
                    }

                    $this->publishSelectedMediaAsShopImages($contribution, $shop, $publishableMedia, $copiedShopImagePaths);
                    $this->integrityService->ensurePrimaryImage($shop);
                    $persistedImageCount = $this->integrityService->finalGalleryCount($shop);
                    $persistedValidImageCount = $this->integrityService->finalGalleryCount($shop, [], 0, [], true);
                    $this->integrityService->validateFinalState(
                        [...$shopValues, 'publish_status' => HeritageShop::STATUS_PUBLISHED],
                        $shop,
                        $persistedImageCount,
                        $persistedValidImageCount,
                    );
                    $shop->forceFill(['publish_status' => HeritageShop::STATUS_PUBLISHED])->save();
                    $contribution->forceFill(['admin_feedback' => $feedback])->save();
                    $contribution->approve($shop, $admin);
                    $this->auditLogger->record(
                        $admin,
                        $shop,
                        $oldValues === [] ? 'heritage_shop.created' : 'heritage_shop.updated',
                        $oldValues,
                        $shop->getAttributes(),
                    );
                    $this->auditLogger->record($admin, $shop, 'heritage_shop.published', $oldValues, $shop->getAttributes());
                    $toStatus = HeritageShopContribution::STATUS_APPROVED;
                } elseif ($validated['moderation_action'] === 'reject') {
                    $toStatus = HeritageShopContribution::STATUS_REJECTED;
                    $contribution->forceFill([
                        'status' => $toStatus,
                        'admin_feedback' => $feedback,
                        'rejection_reason' => $feedback,
                        'approved_by_user_id' => null,
                        'approved_at' => null,
                    ])->save();
                } elseif ($validated['moderation_action'] === 'request_revision') {
                    $toStatus = HeritageShopContribution::STATUS_REVISION_REQUIRED;
                    $contribution->forceFill([
                        'status' => $toStatus,
                        'admin_feedback' => $feedback,
                        'rejection_reason' => null,
                    ])->save();
                } else {
                    $reason = $validated['deletion_reason'];
                    $comment = $reason === 'Other'
                        ? trim(strip_tags($validated['deletion_comment'] ?? ''))
                        : $reason;

                    $contribution->forceFill([
                        'status' => HeritageShopContribution::STATUS_DELETED,
                        'admin_feedback' => $comment,
                        'reviewed_by_user_id' => $admin->id,
                        'review_started_at' => $contribution->review_started_at ?? now(),
                    ])->save();

                    $this->recordActivity(
                        $contribution,
                        $admin->id,
                        'deleted',
                        $fromStatus,
                        HeritageShopContribution::STATUS_DELETED,
                        $comment,
                        ['deletion_reason' => $reason]
                    );

                    $contribution->delete();

                    return;
                }

                $this->recordActivity(
                    $contribution,
                    $admin->id,
                    $validated['moderation_action'],
                    $fromStatus,
                    $toStatus,
                    $feedback
                );
            });
        } catch (Throwable $exception) {
            foreach ($copiedShopImagePaths as $path) {
                $this->imageService->delete($path);
            }

            if ($exception instanceof ValidationException) {
                return back()->withInput()->withErrors($exception->errors());
            }

            if ($exception instanceof RuntimeException) {
                return back()
                    ->withInput()
                    ->withErrors(['publish_media_ids' => $exception->getMessage()]);
            }

            throw $exception;
        }

        if ($validated['moderation_action'] === 'delete') {
            $this->notifyContributorOfStatusChange($contribution);

            return redirect()->route('admin.community-contributions.submissions')
                ->with('status', 'Submission deleted and retained in the database for audit.');
        }

        $contribution->refresh();
        $this->notifyContributorOfStatusChange($contribution);

        return redirect()->route('admin.community-contributions.show', $contribution)
            ->with('status', 'Moderation outcome saved and the contributor was notified.');
    }

    public function correctionRequests(Request $request): View
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(CorrectionRequest::activeStatuses())],
        ]);

        $allowedStatuses = CorrectionRequest::activeStatuses();
        $query = CorrectionRequest::query()
            ->with(['user', 'heritageShop'])
            ->whereIn('status', $allowedStatuses)
            ->latest('created_at');

        if ($request->filled('status') && in_array($request->string('status')->toString(), $allowedStatuses, true)) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim()->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('field_name', 'like', $search)
                    ->orWhere('suggested_value', 'like', $search)
                    ->orWhereHas('heritageShop', fn ($shopQuery) => $shopQuery
                        ->where('shop_name', 'like', $search))
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $correctionRequests = $query->paginate(15)->withQueryString();

        return view('community-contributions.admin.correction-requests.index', compact('allowedStatuses', 'correctionRequests'));
    }

    public function showCorrectionRequest(CorrectionRequest $correctionRequest): View
    {
        $correctionRequest->load(['media', 'user', 'heritageShop', 'reviewedBy', 'moderationActivities.actor']);

        return view('community-contributions.admin.correction-requests.show', compact('correctionRequest'));
    }

    public function startCorrectionReview(Request $request, CorrectionRequest $correctionRequest): RedirectResponse
    {
        if ($correctionRequest->status !== CorrectionRequest::STATUS_PENDING) {
            return back()->withErrors(['review' => 'Only a pending correction request can enter review.']);
        }

        DB::transaction(function () use ($request, $correctionRequest): void {
            $fromStatus = $correctionRequest->status;
            $correctionRequest->forceFill([
                'status' => CorrectionRequest::STATUS_UNDER_REVIEW,
                'reviewed_by_user_id' => $request->user()->id,
                'review_started_at' => now(),
            ])->save();

            $this->recordCorrectionActivity(
                $correctionRequest,
                $request->user()->id,
                'correction_review_started',
                $fromStatus,
                CorrectionRequest::STATUS_UNDER_REVIEW
            );
        });

        return back()->with('status', 'Correction request review started.');
    }

    public function moderateCorrectionRequest(Request $request, CorrectionRequest $correctionRequest): RedirectResponse
    {
        $validated = $request->validate([
            'moderation_action' => ['required', Rule::in(['approve', 'reject', 'needs_information'])],
            'admin_comment' => [
                Rule::requiredIf(fn () => in_array($request->input('moderation_action'), ['reject', 'needs_information'], true)),
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        if ($correctionRequest->status !== CorrectionRequest::STATUS_UNDER_REVIEW) {
            return back()->withErrors(['moderation' => 'Start the correction request review before choosing an outcome.']);
        }

        $admin = $request->user();
        $fromStatus = $correctionRequest->status;
        $comment = filled($validated['admin_comment'] ?? null)
            ? trim(strip_tags($validated['admin_comment']))
            : null;

        try {
            DB::transaction(function () use ($validated, $correctionRequest, $admin, $fromStatus, $comment): void {
                if ($validated['moderation_action'] === 'approve') {
                    $shop = HeritageShop::query()->lockForUpdate()->findOrFail($correctionRequest->heritage_shop_id);
                    $payload = $correctionRequest->heritageShopUpdatePayload();

                    if ($payload !== []) {
                        $candidate = [...$shop->only($shop->getFillable()), ...$payload];
                        $finalCount = $this->integrityService->finalGalleryCount($shop);
                        $finalValidCount = $this->integrityService->finalGalleryCount($shop, [], 0, [], true);
                        $normalized = $this->integrityService->validateFinalState($candidate, $shop, $finalCount, $finalValidCount);
                        $oldValues = $shop->getAttributes();
                        $shop->fill(collect($normalized)->only(array_keys($payload))->all())->save();
                        $this->integrityService->ensurePrimaryImage($shop);
                        $this->auditLogger->record($admin, $shop, 'heritage_shop.updated', $oldValues, $shop->getAttributes());
                    }

                    $toStatus = CorrectionRequest::STATUS_APPROVED;
                    $action = 'correction_approved';
                } elseif ($validated['moderation_action'] === 'reject') {
                    $toStatus = CorrectionRequest::STATUS_REJECTED;
                    $action = 'correction_rejected';
                } else {
                    $toStatus = CorrectionRequest::STATUS_NEEDS_INFORMATION;
                    $action = 'additional_information_requested';
                }

                $correctionRequest->forceFill([
                    'status' => $toStatus,
                    'admin_comment' => $comment,
                    'reviewed_by_user_id' => $admin->id,
                    'reviewed_at' => now(),
                ])->save();

                $this->recordCorrectionActivity(
                    $correctionRequest,
                    $admin->id,
                    $action,
                    $fromStatus,
                    $toStatus,
                    $comment,
                    ['field_name' => $correctionRequest->field_name]
                );
            });
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        $correctionRequest->refresh();
        $correctionRequest->user?->notify(new CorrectionRequestStatusChanged($correctionRequest));

        return redirect()->route('admin.community-contributions.correction-requests.show', $correctionRequest)
            ->with('status', 'Correction request outcome saved and the contributor was notified.');
    }

    public function history(Request $request): View
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'record_type' => ['nullable', Rule::in(['shop_submission', 'correction_request'])],
        ]);

        $historyStatuses = array_values(array_unique([
            HeritageShopContribution::STATUS_REVISION_REQUIRED,
            HeritageShopContribution::STATUS_APPROVED,
            HeritageShopContribution::STATUS_REJECTED,
            HeritageShopContribution::STATUS_WITHDRAWN,
            HeritageShopContribution::STATUS_DELETED,
            CorrectionRequest::STATUS_APPROVED,
            CorrectionRequest::STATUS_REJECTED,
        ]));
        $recordTypes = [
            'shop_submission' => 'Shop Submission',
            'correction_request' => 'Correction Request',
        ];

        $shopHistoryQuery = HeritageShopContribution::withTrashed()
            ->with(['user', 'reviewedBy', 'moderationActivities.actor'])
            ->whereIn('status', $historyStatuses);

        $correctionHistoryQuery = CorrectionRequest::query()
            ->with(['user', 'heritageShop', 'moderationActivities.actor'])
            ->whereIn('status', CorrectionRequest::processedStatuses());

        if ($request->filled('status') && in_array($request->string('status')->toString(), $historyStatuses, true)) {
            $shopHistoryQuery->where('status', $request->string('status')->toString());
        }

        if ($request->filled('status') && in_array($request->string('status')->toString(), CorrectionRequest::processedStatuses(), true)) {
            $correctionHistoryQuery->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim()->toString().'%';
            $shopHistoryQuery->where(function ($builder) use ($search): void {
                $builder->where('contribution_title', 'like', $search)
                    ->orWhere('shop_name', 'like', $search)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search));
            });
            $correctionHistoryQuery->where(function ($builder) use ($search): void {
                $builder->where('field_name', 'like', $search)
                    ->orWhere('suggested_value', 'like', $search)
                    ->orWhere('reason', 'like', $search)
                    ->orWhereHas('heritageShop', fn ($shopQuery) => $shopQuery
                        ->where('shop_name', 'like', $search))
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search));
            });
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->date('date_from');
            $shopHistoryQuery->whereDate('updated_at', '>=', $dateFrom);
            $correctionHistoryQuery->where(function ($builder) use ($dateFrom): void {
                $builder->whereDate('reviewed_at', '>=', $dateFrom)
                    ->orWhereDate('updated_at', '>=', $dateFrom)
                    ->orWhereDate('created_at', '>=', $dateFrom);
            });
        }

        if ($request->filled('date_to')) {
            $dateTo = $request->date('date_to');
            $shopHistoryQuery->whereDate('updated_at', '<=', $dateTo);
            $correctionHistoryQuery->where(function ($builder) use ($dateTo): void {
                $builder->whereDate('reviewed_at', '<=', $dateTo)
                    ->orWhereDate('updated_at', '<=', $dateTo)
                    ->orWhereDate('created_at', '<=', $dateTo);
            });
        }

        $history = collect()
            ->merge($shopHistoryQuery->get()->map(fn ($record): object => (object) [
                'record_type' => 'shop_submission',
                'record' => $record,
                'processed_at' => $record->approved_at ?? $record->updated_at ?? $record->submitted_at,
            ]))
            ->merge($correctionHistoryQuery->get()->map(fn ($record): object => (object) [
                'record_type' => 'correction_request',
                'record' => $record,
                'processed_at' => $record->reviewed_at ?? $record->updated_at ?? $record->created_at,
            ]))
            ->filter(fn (object $entry): bool => $request->filled('record_type')
                ? $entry->record_type === $request->string('record_type')->toString()
                : true)
            ->sortByDesc(fn (object $entry): int => $entry->processed_at instanceof Carbon
                ? $entry->processed_at->getTimestamp()
                : ($entry->processed_at ? Carbon::instance($entry->processed_at)->getTimestamp() : 0))
            ->values();

        $page = (int) $request->query('page', 1);
        $perPage = 15;
        $paginatedHistory = new LengthAwarePaginator(
            $history->forPage($page, $perPage),
            $history->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('community-contributions.admin.history', compact('paginatedHistory', 'historyStatuses', 'recordTypes'));
    }

    private function recordActivity(
        HeritageShopContribution $contribution,
        int $actorUserId,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $comment = null,
        ?array $metadata = null
    ): ModerationActivity {
        return $contribution->moderationActivities()->create([
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
            'metadata' => $metadata,
        ]);
    }

    private function notifyContributorOfStatusChange(HeritageShopContribution $contribution): void
    {
        if ($contribution->user === null) {
            return;
        }

        try {
            $contribution->user->notify(new ContributionStatusChanged($contribution));
        } catch (Throwable $exception) {
            Log::warning('Contribution status notification could not be delivered.', [
                'contribution_public_id' => $contribution->public_id,
                'contribution_status' => $contribution->status,
                'notifiable_user_id' => $contribution->user_id,
                'exception' => $exception::class,
            ]);
        }
    }

    /**
     * @param  array<int, mixed>  $mediaIds
     * @return EloquentCollection<int, Media>
     */
    private function validatePublishableMediaSelection(HeritageShopContribution $contribution, array $mediaIds): EloquentCollection
    {
        $ids = collect($mediaIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return new EloquentCollection;
        }

        $media = $contribution->media()
            ->whereKey($ids->all())
            ->get()
            ->keyBy('id');

        if ($media->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'publish_media_ids' => 'Only media attached to this contribution can be published.',
            ]);
        }

        $mediaDisk = config('filesystems.media_disk');
        if (! is_string($mediaDisk) || $mediaDisk === '') {
            throw ValidationException::withMessages([
                'publish_media_ids' => 'Contribution media storage is not configured.',
            ]);
        }

        foreach ($ids as $id) {
            $item = $media->get($id);
            if (! $item || ! $this->isPublishableImageMedia($item)) {
                throw ValidationException::withMessages([
                    'publish_media_ids' => 'Only supported image evidence can be published to the Heritage Shop gallery.',
                ]);
            }

            if (! $this->isValidStoragePath($item->r2_object_key) || ! Storage::disk($mediaDisk)->exists($item->r2_object_key)) {
                throw ValidationException::withMessages([
                    'publish_media_ids' => 'A selected supporting image is missing from contribution media storage.',
                ]);
            }

            $maximumBytes = (int) config('heritage_shop.max_image_bytes', 2048 * 1024);
            $actualBytes = Storage::disk($mediaDisk)->size($item->r2_object_key);
            if (max((int) $item->file_size_bytes, $actualBytes) > $maximumBytes) {
                throw ValidationException::withMessages([
                    'publish_media_ids' => ($item->original_name ?: 'This file').' can remain as contribution evidence, but Heritage Shop gallery images must not exceed '.number_format($maximumBytes / 1024 / 1024, 0).' MB.',
                ]);
            }
        }

        return new EloquentCollection($ids->map(fn (int $id): Media => $media->get($id))->all());
    }

    private function newCommunityGalleryImageCount(
        HeritageShopContribution $contribution,
        ?HeritageShop $shop,
        EloquentCollection $media,
    ): int {
        if (! $shop) {
            return $media->count();
        }

        return $media->reject(function (Media $item) use ($contribution, $shop): bool {
            $targetPath = $this->shopImagePathForContributionMedia($contribution, $shop, $item);

            return $shop->images()->withTrashed()->where('path', $targetPath)->exists();
        })->count();
    }

    /**
     * @param  EloquentCollection<int, Media>  $media
     * @param  array<int, string>  $copiedShopImagePaths
     */
    private function publishSelectedMediaAsShopImages(
        HeritageShopContribution $contribution,
        HeritageShop $shop,
        EloquentCollection $media,
        array &$copiedShopImagePaths
    ): void {
        if ($media->isEmpty()) {
            return;
        }

        $sourceDisk = config('filesystems.media_disk');
        if (! is_string($sourceDisk) || $sourceDisk === '') {
            throw new RuntimeException('Contribution media storage is not configured.');
        }

        $shopAlreadyHasImages = $shop->images()->exists();
        $primaryAssigned = false;

        foreach ($media as $item) {
            $targetPath = $this->shopImagePathForContributionMedia($contribution, $shop, $item);

            if ($shop->images()->withTrashed()->where('path', $targetPath)->exists()) {
                continue;
            }

            $targetExistsBeforeCopy = Storage::disk($this->imageService->diskName())->exists($targetPath);
            $this->imageService->copyFromDisk($sourceDisk, $item->r2_object_key, $targetPath, $item->mime_type);
            if (! $targetExistsBeforeCopy) {
                $copiedShopImagePaths[] = $targetPath;
            }

            $shop->images()->create([
                'path' => $targetPath,
                'is_primary' => ! $shopAlreadyHasImages && ! $primaryAssigned,
            ]);

            $primaryAssigned = true;
        }
    }

    private function shopImagePathForContributionMedia(HeritageShopContribution $contribution, HeritageShop $shop, Media $media): string
    {
        $extension = $this->extensionForMedia($media);

        if ($extension === null) {
            throw new RuntimeException('The selected supporting image type is unsupported.');
        }

        return $this->imageService->directory()
            .'/community-contributions/'
            .$shop->getKey()
            .'/'
            .$contribution->getKey()
            .'/media-'
            .$media->getKey()
            .'.'
            .$extension;
    }

    private function isPublishableImageMedia(Media $media): bool
    {
        return $media->media_type === 'image'
            && $this->extensionForMedia($media) !== null
            && $this->isValidStoragePath($media->r2_object_key);
    }

    private function extensionForMedia(Media $media): ?string
    {
        $mimeExtension = match (strtolower((string) $media->mime_type)) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };

        if ($mimeExtension !== null) {
            return $mimeExtension;
        }

        $extension = strtolower(pathinfo((string) ($media->original_name ?: $media->r2_object_key), PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'jpg',
            'png' => 'png',
            'webp' => 'webp',
            default => null,
        };
    }

    private function isValidStoragePath(?string $path): bool
    {
        return is_string($path)
            && $path !== ''
            && ! str_starts_with($path, '/')
            && ! str_starts_with($path, '\\')
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\');
    }

    private function recordCorrectionActivity(
        CorrectionRequest $correctionRequest,
        int $actorUserId,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $comment = null,
        ?array $metadata = null
    ): ModerationActivity {
        return $correctionRequest->moderationActivities()->create([
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
            'metadata' => $metadata,
        ]);
    }
}
