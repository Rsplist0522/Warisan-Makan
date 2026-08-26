<?php

namespace App\Http\Controllers;

use App\Models\HeritageShopContribution;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CommunityContributionController extends Controller
{
    public function index(): View
    {
        return view('community-contribution', [
            'contribution' => null,
            'formToken' => (string) Str::uuid(),
        ]);
    }

    public function create(): View
    {
        return view('community-contribution', [
            'contribution' => null,
            'formToken' => (string) Str::uuid(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateContribution($request);
        $submissionToken = $validated['submission_token'] ?? (string) Str::uuid();
        $storedMedia = [];

        $existingContribution = $this->existingContributionForToken($request, $submissionToken);
        if ($existingContribution !== null) {
            return $this->duplicateSubmissionResponse($existingContribution);
        }

        try {
            $contribution = DB::transaction(function () use ($request, $validated, $submissionToken, &$storedMedia) {
                $action = $validated['submission_action'];
                $contribution = HeritageShopContribution::create([
                    ...$this->contributionData($request, $validated),
                    'user_id' => $request->user()->id,
                    'submission_token' => $submissionToken,
                    'status' => $action === 'draft'
                        ? HeritageShopContribution::STATUS_DRAFT
                        : HeritageShopContribution::STATUS_PENDING_REVIEW,
                    'submitted_at' => $action === 'submit' ? now() : null,
                ]);

                $storedMedia = $this->storeNewMedia(
                    $request,
                    "contributions/{$contribution->id}",
                    $request->user()->id
                );
                $this->createMediaRecords($contribution, $storedMedia);

                $contribution->recordVersion(
                    $request->user(),
                    $action === 'draft' ? 'draft_created' : 'submitted'
                );

                return $contribution;
            });
        } catch (Throwable $exception) {
            $this->deleteStoredMedia($storedMedia);

            if ($exception instanceof QueryException
                && $this->isDuplicateSubmissionTokenException($exception)) {
                $existingContribution = $this->existingContributionForToken($request, $submissionToken);

                if ($existingContribution !== null) {
                    return $this->duplicateSubmissionResponse($existingContribution);
                }
            }

            throw $exception;
        }

        if ($validated['submission_action'] === 'draft') {
            return redirect()->route('community-contribution.drafts')
                ->with('status', 'Heritage shop draft saved successfully.');
        }

        return redirect()->route('community-contribution.contributions.show', $contribution)
            ->with('status', 'Heritage shop information submitted for review.');
    }

    public function drafts(Request $request): View
    {
        $drafts = $request->user()->heritageShopContributions()
            ->where('status', HeritageShopContribution::STATUS_DRAFT)
            ->latest('updated_at')
            ->paginate(10);

        return view('community-contributions.drafts', compact('drafts'));
    }

    public function edit(Request $request, HeritageShopContribution $contribution): View
    {
        Gate::authorize('update', $contribution);
        $contribution->load('media');

        return view('community-contribution', compact('contribution'));
    }

    public function update(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        Gate::authorize('update', $contribution);

        $validated = $this->validateContribution($request);
        $contribution->load('media');
        $requestedRemovalIds = collect($request->input('remove_media', []))
            ->map(fn ($id): int => (int) $id)
            ->all();
        $requestedRemovals = $contribution->media
            ->whereIn('id', $requestedRemovalIds)
            ->values();
        $retainedMediaCount = $contribution->media->count() - $requestedRemovals->count();
        $newFiles = $request->file('supporting_media', []);

        if ($retainedMediaCount + count($newFiles) > 6) {
            throw ValidationException::withMessages([
                'supporting_media' => 'A contribution may contain no more than 6 media files.',
            ]);
        }

        $storedMedia = [];
        $oldStatus = $contribution->status;

        try {
            DB::transaction(function () use (
                $request,
                $validated,
                $contribution,
                $requestedRemovals,
                $retainedMediaCount,
                &$storedMedia,
                $oldStatus
            ): void {
                $action = $validated['submission_action'];
                $isSubmitting = $action === 'submit';

                $contribution->fill([
                    ...$this->contributionData($request, $validated),
                    'status' => $isSubmitting
                        ? HeritageShopContribution::STATUS_PENDING_REVIEW
                        : $oldStatus,
                    'submitted_at' => $isSubmitting
                        ? ($contribution->submitted_at ?? now())
                        : $contribution->submitted_at,
                    'resubmitted_at' => $isSubmitting && $oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED
                        ? now()
                        : $contribution->resubmitted_at,
                    'reviewed_by_user_id' => $isSubmitting ? null : $contribution->reviewed_by_user_id,
                    'review_started_at' => $isSubmitting ? null : $contribution->review_started_at,
                    'withdrawn_at' => null,
                ])->save();

                $requestedRemovals->each->delete();
                $storedMedia = $this->storeNewMedia(
                    $request,
                    "contributions/{$contribution->id}",
                    $request->user()->id,
                    $retainedMediaCount
                );
                $this->createMediaRecords($contribution, $storedMedia);

                $contribution->recordVersion(
                    $request->user(),
                    $isSubmitting
                        ? ($oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED ? 'resubmitted' : 'submitted')
                        : ($oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED ? 'revision_updated' : 'draft_updated')
                );

                if ($isSubmitting && $oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED) {
                    $this->markRevisionNotificationsRead($request, $contribution);
                }
            });
        } catch (Throwable $exception) {
            $this->deleteStoredMedia($storedMedia);
            throw $exception;
        }

        Storage::disk(config('filesystems.media_disk'))->delete($requestedRemovals->pluck('r2_object_key')->all());

        if ($validated['submission_action'] === 'draft') {
            if ($oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED) {
                return redirect()->route('community-contribution.contributions.show', $contribution)
                    ->with('status', 'Revision changes saved. Resubmit when they are ready.');
            }

            return redirect()->route('community-contribution.drafts')
                ->with('status', 'Draft updated successfully.');
        }

        return redirect()->route('community-contribution.contributions.show', $contribution)
            ->with('status', $oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED
                ? 'Revised contribution resubmitted successfully.'
                : 'Draft submitted for review.');
    }

    public function destroyDraft(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        Gate::authorize('deleteDraft', $contribution);

        $media = $contribution->media()->get();
        $contribution->delete();
        $contribution->media()->delete();
        Storage::disk(config('filesystems.media_disk'))->delete($media->pluck('r2_object_key')->all());

        return redirect()->route('community-contribution.drafts')
            ->with('status', 'Draft deleted successfully.');
    }

    public function submitDraft(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        if ((int) $contribution->user_id === (int) $request->user()->id
            && $contribution->status === HeritageShopContribution::STATUS_PENDING_REVIEW) {
            return redirect()->route('community-contribution.contributions.show', $contribution)
                ->with('status', 'This draft was already submitted for review.');
        }

        Gate::authorize('submitDraft', $contribution);

        $missing = collect([
            'contribution_title',
            'shop_name',
            'primary_food_category',
            'establishment_year',
            'founder_name',
            'founder_background',
            'current_owner_name',
            'heritage_story',
            'address',
        ])->filter(fn (string $field) => blank($contribution->{$field}));

        if ($missing->isNotEmpty()) {
            return redirect()->route('community-contribution.edit', $contribution)
                ->withErrors([
                    'submission' => 'Complete all required fields before submitting this draft.',
                ]);
        }

        DB::transaction(function () use ($request, $contribution): void {
            $contribution->forceFill([
                'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
                'submitted_at' => now(),
                'withdrawn_at' => null,
            ])->save();
            $contribution->recordVersion($request->user(), 'submitted');
        });

        return redirect()->route('community-contribution.contributions.show', $contribution)
            ->with('status', 'Draft submitted for review.');
    }

    public function contributions(Request $request): View
    {
        $allowedStatuses = [
            HeritageShopContribution::STATUS_PENDING_REVIEW,
            HeritageShopContribution::STATUS_UNDER_REVIEW,
            HeritageShopContribution::STATUS_REVISION_REQUIRED,
            HeritageShopContribution::STATUS_APPROVED,
            HeritageShopContribution::STATUS_REJECTED,
            HeritageShopContribution::STATUS_WITHDRAWN,
            HeritageShopContribution::STATUS_DELETED,
        ];

        $query = $request->user()->heritageShopContributions()
            ->withTrashed()
            ->whereIn('status', $allowedStatuses)
            ->latest('updated_at');

        if ($request->filled('status') && in_array($request->string('status')->toString(), $allowedStatuses, true)) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim()->toString().'%';
            $query->where(fn ($builder) => $builder
                ->where('contribution_title', 'like', $search)
                ->orWhere('shop_name', 'like', $search));
        }

        $contributions = $query->paginate(10)->withQueryString();
        $notifications = $request->user()->notifications()
            ->whereNotNull('data->contribution_id')
            ->whereNull('read_at')
            ->latest()
            ->limit(5)
            ->get();
        $notificationContributions = HeritageShopContribution::withTrashed()
            ->whereIn('id', $notifications->pluck('data.contribution_id')->filter()->unique()->all())
            ->get()
            ->keyBy('id');

        return view('community-contributions.index', compact(
            'contributions',
            'notifications',
            'notificationContributions',
            'allowedStatuses'
        ));
    }

    public function show(Request $request, HeritageShopContribution $contribution): View
    {
        Gate::authorize('view', $contribution);

        $contribution->load(['media', 'versions.user', 'moderationActivities.actor']);
        $request->user()->unreadNotifications()
            ->where('data->contribution_id', $contribution->id)
            ->update(['read_at' => now()]);

        return view('community-contributions.show', compact('contribution'));
    }

    public function withdraw(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        Gate::authorize('view', $contribution);

        if (! $contribution->canBeWithdrawnBy($request->user())) {
            return back()->withErrors([
                'withdraw' => 'This contribution can no longer be withdrawn because review has started or its status has changed.',
            ]);
        }

        DB::transaction(function () use ($request, $contribution): void {
            $fromStatus = $contribution->status;
            $contribution->forceFill([
                'status' => HeritageShopContribution::STATUS_WITHDRAWN,
                'withdrawn_at' => now(),
            ])->save();
            $contribution->moderationActivities()->create([
                'actor_user_id' => $request->user()->id,
                'action' => 'withdrawn_by_user',
                'from_status' => $fromStatus,
                'to_status' => HeritageShopContribution::STATUS_WITHDRAWN,
            ]);
        });

        return redirect()->route('community-contribution.contributions.show', $contribution)
            ->with('status', 'Contribution withdrawn successfully.');
    }

    private function validateContribution(Request $request): array
    {
        $validated = $request->validate([
            'submission_action' => ['required', Rule::in(['draft', 'submit'])],
            'submission_token' => ['nullable', 'uuid'],
            'contribution_title' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:255'],
            'shop_name' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:255'],
            'primary_food_category' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:255'],
            'establishment_year' => ['required_if:submission_action,submit', 'nullable', 'integer', 'min:1000', 'max:'.now()->year],
            'founder_name' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:255'],
            'founder_background' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:5000'],
            'current_owner_name' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:255'],
            'current_owner_details' => ['nullable', 'string', 'max:5000'],
            'heritage_story' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:10000'],
            'operating_hours' => ['nullable', 'array'],
            'operating_hours.*.day' => ['nullable', 'string', 'max:30'],
            'operating_hours.*.open' => ['nullable', 'date_format:H:i'],
            'operating_hours.*.close' => ['nullable', 'date_format:H:i'],
            'operating_hours.*.closed' => ['nullable', 'boolean'],
            'food_items' => ['nullable', 'array'],
            'food_items.*.name' => ['nullable', 'string', 'max:255'],
            'food_items.*.desc' => ['nullable', 'string', 'max:1000'],
            'contact_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]*$/'],
            'address' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'supporting_media' => ['nullable', 'array', 'max:6'],
            'supporting_media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,avi', 'max:20480'],
            'remove_media' => ['nullable', 'array'],
            'remove_media.*' => ['integer'],
        ]);

        if (($validated['submission_action'] ?? null) === 'draft'
            && ! $this->hasMinimumDraftContent($validated)) {
            throw ValidationException::withMessages([
                'draft' => 'Please enter at least one piece of information before saving this draft.',
            ]);
        }

        return $validated;
    }

    private function contributionData(Request $request, array $validated): array
    {
        $textFields = [
            'contribution_title',
            'shop_name',
            'primary_food_category',
            'founder_name',
            'founder_background',
            'current_owner_name',
            'current_owner_details',
            'heritage_story',
            'contact_number',
            'address',
            'city',
            'state',
            'postal_code',
        ];
        $data = [];

        foreach ($textFields as $field) {
            $data[$field] = $this->normalizeText($validated[$field] ?? null);
        }

        $data['establishment_year'] = isset($validated['establishment_year'])
            ? (int) $validated['establishment_year']
            : null;
        $data['latitude'] = isset($validated['latitude']) ? (float) $validated['latitude'] : null;
        $data['longitude'] = isset($validated['longitude']) ? (float) $validated['longitude'] : null;
        $data['operating_hours'] = collect($request->input('operating_hours', []))
            ->filter(fn (array $schedule) => filled($schedule['day'] ?? null))
            ->map(function (array $schedule): array {
                $open = $this->normalizeText($schedule['open'] ?? null);
                $close = $this->normalizeText($schedule['close'] ?? null);
                $closed = filter_var(
                    $schedule['closed'] ?? false,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                ) ?? false;

                if (filled($open) || filled($close)) {
                    $closed = false;
                }

                return [
                    'day' => $this->normalizeText($schedule['day'] ?? null),
                    'open' => $closed ? null : $open,
                    'close' => $closed ? null : $close,
                    'closed' => $closed,
                ];
            })->values()->all();
        $data['food_items'] = collect($request->input('food_items', []))
            ->filter(fn (array $item) => filled($item['name'] ?? null) || filled($item['desc'] ?? null))
            ->map(fn (array $item): array => [
                'name' => $this->normalizeText($item['name'] ?? null),
                'desc' => $this->normalizeText($item['desc'] ?? null),
            ])->values()->all();

        return $data;
    }

    private function storeNewMedia(Request $request, string $directory, int $userId, int $startOrder = 0): array
    {
        return collect($request->file('supporting_media', []))
            ->values()
            ->map(function (UploadedFile $file, int $index) use ($directory, $userId, $startOrder): array {
                $objectKey = $file->store($directory, config('filesystems.media_disk'));

                if ($objectKey === false) {
                    throw ValidationException::withMessages([
                        'supporting_media' => 'The media file could not be uploaded. Please try again.',
                    ]);
                }

                return [
                    'uploaded_by_user_id' => $userId,
                    'media_type' => $this->mediaType($file),
                    'r2_object_key' => $objectKey,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size_bytes' => $file->getSize(),
                    'display_order' => $startOrder + $index,
                    'is_primary' => $startOrder === 0 && $index === 0,
                ];
            })
            ->values()
            ->all();
    }

    private function createMediaRecords(HeritageShopContribution $contribution, array $records): void
    {
        foreach ($records as $record) {
            $contribution->media()->create($record);
        }
    }

    private function deleteStoredMedia(array $records): void
    {
        Storage::disk(config('filesystems.media_disk'))->delete(
            collect($records)->pluck('r2_object_key')->all()
        );
    }

    private function mediaType(UploadedFile $file): string
    {
        return str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';
    }

    private function existingContributionForToken(Request $request, string $submissionToken): ?HeritageShopContribution
    {
        return HeritageShopContribution::withTrashed()
            ->where('user_id', $request->user()->id)
            ->where('submission_token', $submissionToken)
            ->first();
    }

    private function duplicateSubmissionResponse(HeritageShopContribution $contribution): RedirectResponse
    {
        if ($contribution->status === HeritageShopContribution::STATUS_DRAFT) {
            return redirect()->route('community-contribution.drafts')
                ->with('status', 'This draft was already saved.');
        }

        return redirect()->route('community-contribution.contributions.show', $contribution)
            ->with('status', 'This contribution was already submitted.');
    }

    private function isDuplicateSubmissionTokenException(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'submission_token')
            || str_contains($exception->getMessage(), 'hsc_user_submission_token_unique');
    }

    private function hasMinimumDraftContent(array $validated): bool
    {
        return filled($this->normalizeText($validated['contribution_title'] ?? null))
            || filled($this->normalizeText($validated['shop_name'] ?? null));
    }

    private function markRevisionNotificationsRead(Request $request, HeritageShopContribution $contribution): void
    {
        $request->user()->unreadNotifications()
            ->where('data->contribution_id', $contribution->id)
            ->where('data->status', HeritageShopContribution::STATUS_REVISION_REQUIRED)
            ->update(['read_at' => now()]);
    }

    private function normalizeText(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return blank($value) ? null : trim(strip_tags($value));
    }
}
