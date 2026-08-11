<?php

namespace App\Http\Controllers;

use App\Models\HeritageShopContribution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CommunityContributionController extends Controller
{
    private const TEXT_FIELDS = [
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

    public function index(): View
    {
        return view('community-contribution', ['contribution' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $submissionAction] = $this->validatedPayload($request);
        [$mediaPaths, , $newMediaPaths] = $this->prepareMedia($request);
        $data['supporting_media'] = $mediaPaths ?: null;

        try {
            $contribution = DB::transaction(function () use ($data, $request, $submissionAction) {
                $contribution = HeritageShopContribution::create([
                    ...$data,
                    'user_id' => $request->user()->id,
                    'status' => $submissionAction === 'draft'
                        ? HeritageShopContribution::STATUS_DRAFT
                        : HeritageShopContribution::STATUS_PENDING_REVIEW,
                    'submitted_at' => $submissionAction === 'submit' ? now() : null,
                ]);

                $contribution->recordVersion(
                    $request->user(),
                    $submissionAction === 'draft' ? 'draft_created' : 'submitted'
                );

                return $contribution;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newMediaPaths);

            throw $exception;
        }

        if ($submissionAction === 'draft') {
            return redirect()
                ->route('community-contribution.drafts')
                ->with('status', 'Heritage shop draft saved successfully.');
        }

        return redirect()
            ->route('community-contribution.contributions.show', $contribution)
            ->with('status', 'Heritage shop information submitted for review.');
    }

    public function drafts(Request $request): View
    {
        $drafts = $request->user()
            ->heritageShopContributions()
            ->where('status', HeritageShopContribution::STATUS_DRAFT)
            ->latest('updated_at')
            ->paginate(10);

        return view('community-contributions.drafts', compact('drafts'));
    }

    public function edit(Request $request, HeritageShopContribution $contribution): View
    {
        abort_unless($contribution->canBeEditedBy($request->user()), 403);

        return view('community-contribution', compact('contribution'));
    }

    public function update(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        abort_unless($contribution->canBeEditedBy($request->user()), 403);

        [$data, $submissionAction] = $this->validatedPayload($request);
        [$mediaPaths, $removedMediaPaths, $newMediaPaths] = $this->prepareMedia(
            $request,
            $contribution->supporting_media ?? []
        );
        $data['supporting_media'] = $mediaPaths ?: null;

        try {
            $contribution = DB::transaction(function () use ($contribution, $data, $request, $submissionAction) {
                $lockedContribution = HeritageShopContribution::query()
                    ->lockForUpdate()
                    ->findOrFail($contribution->id);

                abort_unless($lockedContribution->canBeEditedBy($request->user()), 403);

                $wasRevision = $lockedContribution->status === HeritageShopContribution::STATUS_REVISION_REQUIRED;

                if ($submissionAction === 'submit') {
                    $data['status'] = HeritageShopContribution::STATUS_PENDING_REVIEW;
                    $data['submitted_at'] = $lockedContribution->submitted_at ?? now();
                    $data['resubmitted_at'] = $wasRevision ? now() : $lockedContribution->resubmitted_at;
                    $data['reviewed_by_user_id'] = null;
                    $data['review_started_at'] = null;
                }

                $lockedContribution->update($data);
                $lockedContribution->recordVersion(
                    $request->user(),
                    $submissionAction === 'submit'
                        ? ($wasRevision ? 'resubmitted' : 'submitted')
                        : 'draft_updated'
                );

                return $lockedContribution;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newMediaPaths);

            throw $exception;
        }

        Storage::disk('public')->delete($removedMediaPaths);

        if ($submissionAction === 'draft' && $contribution->status === HeritageShopContribution::STATUS_DRAFT) {
            return redirect()
                ->route('community-contribution.drafts')
                ->with('status', 'Draft updated successfully.');
        }

        return redirect()
            ->route('community-contribution.contributions.show', $contribution)
            ->with('status', $submissionAction === 'submit'
                ? 'Contribution submitted for review.'
                : 'Revision saved successfully.');
    }

    public function submitDraft(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        $this->authorizeOwnedDraft($request, $contribution);

        $validator = Validator::make($contribution->toArray(), $this->contentRules('submit'));

        if ($validator->fails()) {
            return redirect()
                ->route('community-contribution.edit', $contribution)
                ->withErrors($validator);
        }

        DB::transaction(function () use ($request, $contribution) {
            $lockedContribution = HeritageShopContribution::query()
                ->lockForUpdate()
                ->findOrFail($contribution->id);

            $this->authorizeOwnedDraft($request, $lockedContribution);

            $lockedContribution->update([
                'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
                'submitted_at' => now(),
            ]);
            $lockedContribution->recordVersion($request->user(), 'submitted');
        });

        return redirect()
            ->route('community-contribution.contributions.show', $contribution)
            ->with('status', 'Contribution submitted for review.');
    }

    public function destroyDraft(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        $this->authorizeOwnedDraft($request, $contribution);

        $mediaPaths = $contribution->supporting_media ?? [];
        $contribution->delete();
        Storage::disk('public')->delete($mediaPaths);

        return redirect()
            ->route('community-contribution.drafts')
            ->with('status', 'Draft deleted successfully.');
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

        $query = $request->user()
            ->heritageShopContributions()
            ->where('status', '!=', HeritageShopContribution::STATUS_DRAFT);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($builder) use ($search) {
                $builder->where('contribution_title', 'like', "%{$search}%")
                    ->orWhere('shop_name', 'like', "%{$search}%");
            });
        }

        if (in_array($request->input('status'), $allowedStatuses, true)) {
            $query->where('status', $request->input('status'));
        }

        $contributions = $query->latest('updated_at')->paginate(10)->withQueryString();
        $notifications = $request->user()->notifications()->latest()->limit(5)->get();

        return view('community-contributions.index', compact(
            'allowedStatuses',
            'contributions',
            'notifications'
        ));
    }

    public function show(Request $request, HeritageShopContribution $contribution): View
    {
        abort_unless((int) $contribution->user_id === (int) $request->user()->id, 403);

        $contribution->load(['versions.user', 'moderationActivities.actor']);

        $request->user()
            ->unreadNotifications()
            ->get()
            ->filter(fn ($notification) => (int) ($notification->data['contribution_id'] ?? 0) === $contribution->id)
            ->each->markAsRead();

        return view('community-contributions.show', compact('contribution'));
    }

    public function withdraw(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        abort_unless((int) $contribution->user_id === (int) $request->user()->id, 403);

        if (! $contribution->canBeWithdrawnBy($request->user())) {
            return redirect()
                ->route('community-contribution.contributions.show', $contribution)
                ->withErrors(['withdraw' => 'This contribution can no longer be withdrawn because review has started.']);
        }

        DB::transaction(function () use ($request, $contribution) {
            $lockedContribution = HeritageShopContribution::query()
                ->lockForUpdate()
                ->findOrFail($contribution->id);

            if (! $lockedContribution->canBeWithdrawnBy($request->user())) {
                throw ValidationException::withMessages([
                    'withdraw' => 'This contribution can no longer be withdrawn because review has started.',
                ]);
            }

            $fromStatus = $lockedContribution->status;
            $lockedContribution->update([
                'status' => HeritageShopContribution::STATUS_WITHDRAWN,
                'withdrawn_at' => now(),
            ]);
            $lockedContribution->moderationActivities()->create([
                'actor_user_id' => $request->user()->id,
                'action' => 'withdrawn_by_user',
                'from_status' => $fromStatus,
                'to_status' => HeritageShopContribution::STATUS_WITHDRAWN,
            ]);
        });

        return redirect()
            ->route('community-contribution.contributions.show', $contribution)
            ->with('status', 'Contribution withdrawn successfully.');
    }

    private function validatedPayload(Request $request): array
    {
        $submissionAction = (string) $request->input('submission_action');
        $validated = $request->validate([
            'submission_action' => ['required', Rule::in(['draft', 'submit'])],
            ...$this->contentRules($submissionAction),
            'supporting_media' => ['nullable', 'array', 'max:6'],
            'supporting_media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,avi', 'max:20480'],
            'remove_media' => ['nullable', 'array', 'max:6'],
            'remove_media.*' => ['string', 'max:2048'],
        ]);

        unset(
            $validated['submission_action'],
            $validated['supporting_media'],
            $validated['remove_media']
        );

        foreach (self::TEXT_FIELDS as $field) {
            $validated[$field] = $this->normalizeText($validated[$field] ?? null);
        }

        if (isset($validated['establishment_year'])) {
            $validated['establishment_year'] = (int) $validated['establishment_year'];
        }

        foreach (['latitude', 'longitude'] as $coordinate) {
            if (isset($validated[$coordinate])) {
                $validated[$coordinate] = (float) $validated[$coordinate];
            }
        }

        $validated['operating_hours'] = collect($validated['operating_hours'] ?? [])
            ->filter(fn (array $schedule) => filled($schedule['day'] ?? null))
            ->map(fn (array $schedule) => [
                'day' => $this->normalizeText($schedule['day'] ?? null),
                'open' => $this->normalizeText($schedule['open'] ?? null),
                'close' => $this->normalizeText($schedule['close'] ?? null),
                'closed' => filter_var($schedule['closed'] ?? false, FILTER_VALIDATE_BOOL),
            ])
            ->values()
            ->all();

        $validated['food_items'] = collect($validated['food_items'] ?? [])
            ->filter(fn (array $item) => filled($item['name'] ?? null) || filled($item['desc'] ?? null))
            ->map(fn (array $item) => [
                'name' => $this->normalizeText($item['name'] ?? null),
                'desc' => $this->normalizeText($item['desc'] ?? null),
            ])
            ->values()
            ->all();

        return [$validated, $submissionAction];
    }

    private function contentRules(string $submissionAction): array
    {
        $requiredForSubmission = Rule::requiredIf($submissionAction === 'submit');

        return [
            'contribution_title' => [$requiredForSubmission, 'nullable', 'string', 'max:255'],
            'shop_name' => [$requiredForSubmission, 'nullable', 'string', 'max:255'],
            'primary_food_category' => [$requiredForSubmission, 'nullable', 'string', 'max:255'],
            'establishment_year' => [$requiredForSubmission, 'nullable', 'integer', 'min:1000', 'max:'.now()->year],
            'founder_name' => [$requiredForSubmission, 'nullable', 'string', 'max:255'],
            'founder_background' => [$requiredForSubmission, 'nullable', 'string', 'max:5000'],
            'current_owner_name' => [$requiredForSubmission, 'nullable', 'string', 'max:255'],
            'current_owner_details' => ['nullable', 'string', 'max:5000'],
            'heritage_story' => [$requiredForSubmission, 'nullable', 'string', 'max:10000'],
            'contact_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s.]+$/'],
            'address' => [$requiredForSubmission, 'nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'operating_hours' => ['nullable', 'array', 'max:7'],
            'operating_hours.*' => ['array'],
            'operating_hours.*.day' => ['nullable', 'string', 'max:30'],
            'operating_hours.*.open' => ['nullable', 'date_format:H:i'],
            'operating_hours.*.close' => ['nullable', 'date_format:H:i'],
            'operating_hours.*.closed' => ['nullable', 'boolean'],
            'food_items' => ['nullable', 'array', 'max:30'],
            'food_items.*' => ['array'],
            'food_items.*.name' => ['nullable', 'string', 'max:255'],
            'food_items.*.desc' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function prepareMedia(Request $request, array $existingMediaPaths = []): array
    {
        $requestedRemovals = (array) $request->input('remove_media', []);
        $removedMediaPaths = array_values(array_intersect($existingMediaPaths, $requestedRemovals));
        $keptMediaPaths = array_values(array_diff($existingMediaPaths, $removedMediaPaths));
        $uploadedFiles = (array) $request->file('supporting_media', []);

        if (count($keptMediaPaths) + count($uploadedFiles) > 6) {
            throw ValidationException::withMessages([
                'supporting_media' => 'A contribution may contain at most 6 media files in total.',
            ]);
        }

        $newMediaPaths = [];

        foreach ($uploadedFiles as $uploadedFile) {
            $newMediaPaths[] = $uploadedFile->store('community-contributions/heritage-shops', 'public');
        }

        return [[...$keptMediaPaths, ...$newMediaPaths], $removedMediaPaths, $newMediaPaths];
    }

    private function authorizeOwnedDraft(Request $request, HeritageShopContribution $contribution): void
    {
        abort_unless(
            (int) $contribution->user_id === (int) $request->user()->id
                && $contribution->status === HeritageShopContribution::STATUS_DRAFT,
            403
        );
    }

    private function normalizeText(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return filled($value) ? trim(strip_tags($value)) : null;
    }
}
