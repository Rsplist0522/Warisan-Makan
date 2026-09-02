<?php

namespace App\Http\Controllers;

use App\Models\HeritageShopContribution;
use App\Rules\MalaysianPhoneNumber;
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
    private const MAX_SUPPORTING_MEDIA = 6;

    public function index(): View
    {
        return view('community-contribution', [
            'contribution' => null,
            'formToken' => (string) Str::uuid(),
            'maxSupportingMedia' => self::MAX_SUPPORTING_MEDIA,
        ]);
    }

    public function create(): View
    {
        return view('community-contribution', [
            'contribution' => null,
            'formToken' => (string) Str::uuid(),
            'maxSupportingMedia' => self::MAX_SUPPORTING_MEDIA,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateContribution($request);
        $submissionToken = $validated['submission_token'] ?? (string) Str::uuid();
        $storedMedia = [];
        $storedFoodItemImages = [];

        $existingContribution = $this->existingContributionForToken($request, $submissionToken);
        if ($existingContribution !== null) {
            return $this->duplicateSubmissionResponse($existingContribution);
        }

        try {
            $contribution = DB::transaction(function () use ($request, $validated, $submissionToken, &$storedMedia, &$storedFoodItemImages) {
                $action = $validated['submission_action'];
                $foodItems = $this->foodItemsFromRequest($request, true);
                $contribution = HeritageShopContribution::create([
                    ...$this->contributionData($request, $validated),
                    'food_items' => $this->foodItemsForStorage($foodItems),
                    'user_id' => $request->user()->id,
                    'submission_token' => $submissionToken,
                    'status' => $action === 'draft'
                        ? HeritageShopContribution::STATUS_DRAFT
                        : HeritageShopContribution::STATUS_PENDING_REVIEW,
                    'submitted_at' => $action === 'submit' ? now() : null,
                ]);

                [$foodItems, $storedFoodItemImages] = $this->storeFoodItemImages($request, $contribution, $foodItems);
                if ($storedFoodItemImages !== []) {
                    $contribution->forceFill(['food_items' => $foodItems])->save();
                }

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
            $this->deleteStoredMedia($storedFoodItemImages);

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
                ->with('status', __('Heritage shop draft saved successfully.'));
        }

        return redirect()->route('community-contribution.contributions.show', $contribution)
            ->with('status', __('Heritage shop information submitted for review.'));
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

        return view('community-contribution', [
            'contribution' => $contribution,
            'maxSupportingMedia' => self::MAX_SUPPORTING_MEDIA,
        ]);
    }

    public function update(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        Gate::authorize('update', $contribution);

        $validated = $this->validateContribution($request, $contribution);
        $contribution->load('media');
        $requestedRemovalIds = collect($request->input('remove_media', []))
            ->map(fn ($id): int => (int) $id)
            ->all();
        $requestedRemovals = $contribution->media
            ->whereIn('id', $requestedRemovalIds)
            ->values();
        $retainedMediaCount = $contribution->media->count() - $requestedRemovals->count();

        $storedMedia = [];
        $storedFoodItemImages = [];
        $oldStatus = $contribution->status;
        $wasPreviouslyWithdrawn = $contribution->withdrawn_at !== null;
        $previousFoodItemImagePaths = $this->foodItemImagePaths($contribution->food_items);

        try {
            DB::transaction(function () use (
                $request,
                $validated,
                $contribution,
                $requestedRemovals,
                $retainedMediaCount,
                &$storedMedia,
                &$storedFoodItemImages,
                $oldStatus,
                $wasPreviouslyWithdrawn
            ): void {
                $action = $validated['submission_action'];
                $isSubmitting = $action === 'submit';
                $foodItems = $this->foodItemsFromRequest($request, true);

                $contribution->fill([
                    ...$this->contributionData($request, $validated),
                    'food_items' => $this->foodItemsForStorage($foodItems),
                    'status' => $isSubmitting
                        ? HeritageShopContribution::STATUS_PENDING_REVIEW
                        : $oldStatus,
                    'submitted_at' => $isSubmitting
                        ? ($contribution->submitted_at ?? now())
                        : $contribution->submitted_at,
                    'resubmitted_at' => $isSubmitting && (
                        $oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED
                        || $wasPreviouslyWithdrawn
                    )
                        ? now()
                        : $contribution->resubmitted_at,
                    'reviewed_by_user_id' => $isSubmitting ? null : $contribution->reviewed_by_user_id,
                    'review_started_at' => $isSubmitting ? null : $contribution->review_started_at,
                ])->save();

                [$foodItems, $storedFoodItemImages] = $this->storeFoodItemImages($request, $contribution, $foodItems);
                if ($storedFoodItemImages !== []) {
                    $contribution->forceFill(['food_items' => $foodItems])->save();
                }

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
                        ? (
                            $oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED || $wasPreviouslyWithdrawn
                                ? 'resubmitted'
                                : 'submitted'
                        )
                        : (
                            $oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED
                                ? 'revision_updated'
                                : ($wasPreviouslyWithdrawn ? 'edited_after_withdrawal' : 'draft_updated')
                        )
                );

                if ($isSubmitting && $oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED) {
                    $this->markRevisionNotificationsRead($request, $contribution);
                }
            });
        } catch (Throwable $exception) {
            $this->deleteStoredMedia($storedMedia);
            $this->deleteStoredMedia($storedFoodItemImages);
            throw $exception;
        }

        Storage::disk(config('filesystems.media_disk'))->delete($requestedRemovals->pluck('r2_object_key')->all());
        Storage::disk(config('filesystems.media_disk'))->delete(
            array_values(array_diff($previousFoodItemImagePaths, $this->foodItemImagePaths($contribution->fresh()->food_items)))
        );

        if ($validated['submission_action'] === 'draft') {
            if ($oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED) {
                return redirect()->route('community-contribution.contributions.show', $contribution)
                    ->with('status', __('Revision changes saved. Resubmit when they are ready.'));
            }

            return redirect()->route('community-contribution.drafts')
                ->with('status', __('Draft updated successfully.'));
        }

        return redirect()->route('community-contribution.contributions.show', $contribution)
            ->with('status', $oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED
                ? __('Revised contribution resubmitted successfully.')
                : __('Draft submitted for review.'));
    }

    public function destroyDraft(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        Gate::authorize('deleteDraft', $contribution);

        $media = $contribution->media()->get();
        $foodItemImagePaths = $this->foodItemImagePaths($contribution->food_items);
        $contribution->delete();
        $contribution->media()->delete();
        Storage::disk(config('filesystems.media_disk'))->delete($media->pluck('r2_object_key')->all());
        Storage::disk(config('filesystems.media_disk'))->delete($foodItemImagePaths);

        return redirect()->route('community-contribution.drafts')
            ->with('status', __('Draft deleted successfully.'));
    }

    public function submitDraft(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        if ((int) $contribution->user_id === (int) $request->user()->id
            && $contribution->status === HeritageShopContribution::STATUS_PENDING_REVIEW) {
            return redirect()->route('community-contribution.contributions.show', $contribution)
                ->with('status', __('This draft was already submitted for review.'));
        }

        Gate::authorize('submitDraft', $contribution);

        $missing = collect([
            'contribution_title',
            'shop_name',
            'primary_food_category',
            'establishment_year',
            'heritage_story',
            'address',
        ])->filter(fn (string $field) => blank($contribution->{$field}));

        if ($missing->isNotEmpty()) {
            return redirect()->route('community-contribution.edit', $contribution)
                ->withErrors([
                    'submission' => __('Complete all required fields before submitting this draft.'),
                ]);
        }

        DB::transaction(function () use ($request, $contribution): void {
            $wasPreviouslyWithdrawn = $contribution->withdrawn_at !== null;
            $contribution->forceFill([
                'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
                'submitted_at' => $contribution->submitted_at ?? now(),
                'resubmitted_at' => $wasPreviouslyWithdrawn ? now() : $contribution->resubmitted_at,
            ])->save();
            $contribution->recordVersion($request->user(), $wasPreviouslyWithdrawn ? 'resubmitted' : 'submitted');
        });

        return redirect()->route('community-contribution.contributions.show', $contribution)
            ->with('status', __('Draft submitted for review.'));
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
            ->with('status', __('Contribution withdrawn successfully.'));
    }

    public function editResubmit(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        Gate::authorize('editResubmit', $contribution);

        DB::transaction(function () use ($request, $contribution): void {
            $fromStatus = $contribution->status;
            $contribution->forceFill([
                'status' => HeritageShopContribution::STATUS_DRAFT,
                'reviewed_by_user_id' => null,
                'review_started_at' => null,
            ])->save();
            $contribution->moderationActivities()->create([
                'actor_user_id' => $request->user()->id,
                'action' => 'reopened_after_withdrawal',
                'from_status' => $fromStatus,
                'to_status' => HeritageShopContribution::STATUS_DRAFT,
            ]);
            $contribution->recordVersion($request->user(), 'reopened_after_withdrawal');
        });

        return redirect()->route('community-contribution.edit', $contribution)
            ->with('status', __('You can now edit and resubmit this contribution.'));
    }

    private function validateContribution(Request $request, ?HeritageShopContribution $contribution = null): array
    {
        $validated = $request->validate([
            'submission_action' => ['required', Rule::in(['draft', 'submit'])],
            'submission_token' => ['nullable', 'uuid'],
            'contribution_title' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:255'],
            'shop_name' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:255'],
            'primary_food_category' => [
                'required_if:submission_action,submit',
                'nullable',
                'string',
                'max:255',
            ],
            'establishment_year' => ['required_if:submission_action,submit', 'nullable', 'integer', 'min:1000', 'max:'.now()->year],
            'founder_name' => ['nullable', 'string', 'max:255'],
            'founder_background' => ['nullable', 'string', 'max:5000'],
            'current_owner_name' => ['nullable', 'string', 'max:255'],
            'current_owner_details' => ['nullable', 'string', 'max:5000'],
            'heritage_story' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:10000'],
            'operating_hours' => ['nullable', 'array'],
            'operating_hours.*.day' => ['nullable', 'string', 'max:30'],
            'operating_hours.*.open' => ['nullable', 'date_format:H:i'],
            'operating_hours.*.close' => ['nullable', 'date_format:H:i'],
            'operating_hours.*.closed' => ['nullable', 'boolean'],
            'operating_hours.*.periods' => ['nullable', 'array', 'max:6'],
            'operating_hours.*.periods.*.open' => ['nullable', 'date_format:H:i'],
            'operating_hours.*.periods.*.close' => ['nullable', 'date_format:H:i'],
            'food_items' => ['nullable', 'array'],
            'food_items.*.name' => ['nullable', 'string', 'max:255'],
            'food_items.*.desc' => ['nullable', 'string', 'max:1000'],
            'food_items.*.price' => ['nullable', 'numeric', 'min:0'],
            'food_items.*.image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('heritage_shop.max_image_kb', 1024)],
            'food_items.*.image_path' => ['nullable', 'string', 'max:500', 'starts_with:contributions/'],
            'contact_number' => ['nullable', 'string', 'max:30', new MalaysianPhoneNumber],
            'address' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'supporting_media' => ['nullable', 'array'],
            'supporting_media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,avi', 'max:20480'],
            'remove_media' => ['nullable', 'array'],
            'remove_media.*' => ['integer'],
        ]);

        $this->validateOperatingHoursPayload($validated['operating_hours'] ?? [], 'operating_hours');
        $this->validateSupportingMediaTotal($request, $contribution);

        if (($validated['submission_action'] ?? null) === 'draft'
            && ! $this->hasMinimumDraftContent($validated)) {
            throw ValidationException::withMessages([
                'draft' => 'Please enter at least one piece of information before saving this draft.',
            ]);
        }

        return $validated;
    }

    private function validateSupportingMediaTotal(Request $request, ?HeritageShopContribution $contribution = null): void
    {
        $newFiles = $request->file('supporting_media', []);
        $newFileCount = is_array($newFiles)
            ? count(array_filter($newFiles))
            : (filled($newFiles) ? 1 : 0);
        $existingMediaCount = 0;
        $removalCount = 0;

        if ($contribution !== null && $contribution->exists) {
            $existingMediaCount = $contribution->media()->count();
            $requestedRemovalIds = collect($request->input('remove_media', []))
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values();

            if ($requestedRemovalIds->isNotEmpty()) {
                $removalCount = $contribution->media()
                    ->whereKey($requestedRemovalIds->all())
                    ->count();
            }
        }

        $retainedExistingCount = max(0, $existingMediaCount - $removalCount);
        $finalMediaCount = $retainedExistingCount + $newFileCount;

        if ($finalMediaCount <= self::MAX_SUPPORTING_MEDIA) {
            return;
        }

        $availableSlots = max(0, self::MAX_SUPPORTING_MEDIA - $retainedExistingCount);
        $message = $retainedExistingCount > 0
            ? trans_choice(
                'You can upload up to :max supporting media files in total. You already have :count saved file selected to keep, so you can add up to :available more.|You can upload up to :max supporting media files in total. You already have :count saved files selected to keep, so you can add up to :available more.',
                $retainedExistingCount,
                [
                    'max' => self::MAX_SUPPORTING_MEDIA,
                    'count' => $retainedExistingCount,
                    'available' => $availableSlots,
                ]
            )
            : __('You can upload up to :max supporting media files in total.', [
                'max' => self::MAX_SUPPORTING_MEDIA,
            ]);

        throw ValidationException::withMessages([
            'supporting_media' => $message.' '.__('Remove an existing file or select fewer new files.'),
        ]);
    }

    private function validateOperatingHoursPayload(array $payload, string $fieldPrefix): void
    {
        $errors = [];

        foreach ($payload as $day => $schedule) {
            if (! is_array($schedule)) {
                $errors["{$fieldPrefix}.{$day}"] = __('The schedule for :day is malformed.', ['day' => (string) $day]);
                continue;
            }

            $closed = filter_var($schedule['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($closed) {
                continue;
            }

            $periods = is_array($schedule['periods'] ?? null) ? $schedule['periods'] : [];
            if ($periods === []) {
                $open = trim((string) ($schedule['open'] ?? ''));
                $close = trim((string) ($schedule['close'] ?? ''));

                if ($open !== '' || $close !== '') {
                    $periods = [['open' => $open, 'close' => $close]];
                }
            }

            $dayPeriods = [];
            $seenPairs = [];
            foreach ($periods as $index => $period) {
                if (! is_array($period)) {
                    $errors["{$fieldPrefix}.{$day}.periods.{$index}"] = __('The time period for :day is malformed.', ['day' => (string) $day]);
                    continue;
                }

                $open = trim((string) ($period['open'] ?? ''));
                $close = trim((string) ($period['close'] ?? ''));

                if ($open === '' && $close === '') {
                    continue;
                }

                if ($open === '' || $close === '') {
                    $errors["{$fieldPrefix}.{$day}.periods.{$index}.open"] = __('Opening and closing time are both required for each open period.');
                    continue;
                }

                if ($open === $close) {
                    $errors["{$fieldPrefix}.{$day}.periods.{$index}.close"] = __('Opening and closing time cannot be identical.');
                    continue;
                }

                $pairKey = $open.'|'.$close;
                if (isset($seenPairs[$pairKey])) {
                    $errors["{$fieldPrefix}.{$day}.periods.{$index}.open"] = __('Duplicate operating periods on the same day are not allowed.');
                    continue;
                }

                $seenPairs[$pairKey] = true;
                $dayPeriods[] = ['open' => $open, 'close' => $close];
            }

            foreach ($dayPeriods as $index => $period) {
                $periodStart = strtotime('1970-01-01 '.$period['open']);
                $periodEnd = strtotime('1970-01-01 '.$period['close']);

                foreach (array_slice($dayPeriods, $index + 1) as $otherIndex => $other) {
                    $otherStart = strtotime('1970-01-01 '.$other['open']);
                    $otherEnd = strtotime('1970-01-01 '.$other['close']);

                    if ($periodStart < $otherEnd && $otherStart < $periodEnd) {
                        $errors["{$fieldPrefix}.{$day}.periods.{$index}.open"] = __('Operating periods on the same day must not overlap.');
                        $errors["{$fieldPrefix}.{$day}.periods.".(($index + 1) + $otherIndex).'.open'] = __('Operating periods on the same day must not overlap.');
                    }
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
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
        $data['operating_hours'] = $this->normalizeOperatingHoursInput($request->input('operating_hours', []));
        $data['food_items'] = $this->foodItemsFromRequest($request);

        return $data;
    }

    private function normalizeOperatingHoursInput(mixed $input): array
    {
        $raw = is_array($input) ? $input : [];
        $normalized = [];

        if ($raw === []) {
            return [];
        }

        if (array_is_list($raw)) {
            foreach ($raw as $schedule) {
                if (! is_array($schedule)) {
                    continue;
                }

                $day = $this->normalizeText($schedule['day'] ?? null);
                if (! filled($day)) {
                    continue;
                }

                $closed = filter_var($schedule['closed'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
                $open = $this->normalizeText($schedule['open'] ?? null);
                $close = $this->normalizeText($schedule['close'] ?? null);

                if ($closed) {
                    $normalized[] = ['day' => $day, 'open' => null, 'close' => null, 'closed' => true];
                    continue;
                }

                if (filled($open) || filled($close)) {
                    $normalized[] = ['day' => $day, 'open' => $open, 'close' => $close, 'closed' => false];
                }
            }

            return $normalized;
        }

        foreach ($raw as $day => $schedule) {
            if (! is_array($schedule)) {
                continue;
            }

            $dayLabel = $this->normalizeText(is_string($day) ? $day : ($schedule['day'] ?? null));
            if (! filled($dayLabel)) {
                continue;
            }

            $closed = filter_var($schedule['closed'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            $periods = is_array($schedule['periods'] ?? null) ? $schedule['periods'] : [];

            if ($closed) {
                $normalized[] = ['day' => $dayLabel, 'open' => null, 'close' => null, 'closed' => true];
                continue;
            }

            if ($periods !== []) {
                foreach ($periods as $period) {
                    if (! is_array($period)) {
                        continue;
                    }

                    $open = $this->normalizeText($period['open'] ?? null);
                    $close = $this->normalizeText($period['close'] ?? null);

                    if ($open === null && $close === null) {
                        continue;
                    }

                    $normalized[] = ['day' => $dayLabel, 'open' => $open, 'close' => $close, 'closed' => false];
                }

                continue;
            }

            $open = $this->normalizeText($schedule['open'] ?? null);
            $close = $this->normalizeText($schedule['close'] ?? null);
            if (filled($open) || filled($close)) {
                $normalized[] = ['day' => $dayLabel, 'open' => $open, 'close' => $close, 'closed' => false];
            }
        }

        return $normalized;
    }

    private function foodItemsFromRequest(Request $request, bool $includeFormIndex = false): array
    {
        $fileRows = $request->file('food_items', []);

        return collect($request->input('food_items', []))
            ->filter(function (array $item, int|string $index) use ($fileRows): bool {
                return filled($item['name'] ?? null)
                    || filled($item['desc'] ?? null)
                    || filled($item['price'] ?? null)
                    || filled($item['image_path'] ?? null)
                    || ($this->foodItemImageFile($fileRows, $index) instanceof UploadedFile);
            })
            ->map(function (array $item, int|string $index) use ($includeFormIndex): array {
                $normalized = [
                    'name' => $this->normalizeText($item['name'] ?? null),
                    'desc' => $this->normalizeText($item['desc'] ?? null),
                    'price' => HeritageShopContribution::normalizeFoodItemPrice($item['price'] ?? null),
                    'image_path' => $this->normalizeFoodItemImagePath($item['image_path'] ?? null),
                ];

                if ($includeFormIndex) {
                    $normalized['_form_index'] = $index;
                }

                return array_filter($normalized, fn ($value, string $key): bool => $key === 'price' || filled($value) || $value === 0, ARRAY_FILTER_USE_BOTH);
            })
            ->values()
            ->all();
    }

    private function foodItemsForStorage(array $foodItems): array
    {
        return collect($foodItems)
            ->map(function (array $item): array {
                unset($item['_form_index']);

                return $item;
            })
            ->values()
            ->all();
    }

    private function foodItemImageFile(array $fileRows, int|string $index): ?UploadedFile
    {
        $row = $fileRows[$index] ?? null;
        $file = is_array($row) ? ($row['image'] ?? null) : null;

        return $file instanceof UploadedFile && $file->isValid() ? $file : null;
    }

    private function storeFoodItemImages(Request $request, HeritageShopContribution $contribution, array $foodItems): array
    {
        $fileRows = $request->file('food_items', []);
        $stored = [];

        foreach ($foodItems as &$item) {
            $file = $this->foodItemImageFile($fileRows, $item['_form_index'] ?? '');

            if ($file instanceof UploadedFile) {
                $objectKey = $file->store("contributions/{$contribution->id}/food-items", config('filesystems.media_disk'));

                if ($objectKey === false) {
                    throw ValidationException::withMessages([
                        'food_items' => 'The food item image could not be uploaded. Please try again.',
                    ]);
                }

                $item['image_path'] = $objectKey;
                $stored[] = ['r2_object_key' => $objectKey];
            }

            unset($item['_form_index']);
        }

        unset($item);

        return [$foodItems, $stored];
    }

    private function normalizeFoodItemImagePath(mixed $path): ?string
    {
        $path = is_string($path) ? trim($path) : null;

        if (blank($path)
            || ! str_starts_with($path, 'contributions/')
            || str_contains($path, '..')
            || str_contains($path, '\\')
            || str_starts_with($path, '/')) {
            return null;
        }

        return $path;
    }

    private function foodItemImagePaths(mixed $foodItems): array
    {
        if (! is_array($foodItems)) {
            return [];
        }

        return collect($foodItems)
            ->pluck('image_path')
            ->filter(fn ($path): bool => filled($this->normalizeFoodItemImagePath($path)))
            ->map(fn ($path): string => (string) $path)
            ->unique()
            ->values()
            ->all();
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
                ->with('status', __('This draft was already saved.'));
        }

        return redirect()->route('community-contribution.contributions.show', $contribution)
                ->with('status', __('This contribution was already submitted.'));
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
