<?php

namespace App\Http\Controllers;

use App\Models\HeritageShopContribution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CommunityContributionController extends Controller
{
    public function index(): View
    {
        return view('community-contribution', ['contribution' => null]);
    }

    public function create(): View
    {
        return view('community-contribution', ['contribution' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateContribution($request);
        $newMedia = $this->storeNewMedia($request);

        try {
            $contribution = DB::transaction(function () use ($request, $validated, $newMedia) {
                $action = $validated['submission_action'];
                $contribution = HeritageShopContribution::create([
                    ...$this->contributionData($request, $validated),
                    'user_id' => $request->user()->id,
                    'supporting_media' => $newMedia ?: null,
                    'status' => $action === 'draft'
                        ? HeritageShopContribution::STATUS_DRAFT
                        : HeritageShopContribution::STATUS_PENDING_REVIEW,
                    'submitted_at' => $action === 'submit' ? now() : null,
                ]);

                $contribution->recordVersion(
                    $request->user(),
                    $action === 'draft' ? 'draft_created' : 'submitted'
                );

                return $contribution;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newMedia);
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
        abort_unless($contribution->canBeEditedBy($request->user()), 403);

        return view('community-contribution', compact('contribution'));
    }

    public function update(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        abort_unless($contribution->canBeEditedBy($request->user()), 403);

        $validated = $this->validateContribution($request);
        $existingMedia = $contribution->supporting_media ?? [];
        $requestedRemovals = array_values(array_intersect(
            $existingMedia,
            $request->input('remove_media', [])
        ));
        $retainedMedia = array_values(array_diff($existingMedia, $requestedRemovals));
        $newFiles = $request->file('supporting_media', []);

        if (count($retainedMedia) + count($newFiles) > 6) {
            throw ValidationException::withMessages([
                'supporting_media' => 'A contribution may contain no more than 6 media files.',
            ]);
        }

        $newMedia = $this->storeNewMedia($request);
        $oldStatus = $contribution->status;

        try {
            DB::transaction(function () use (
                $request,
                $validated,
                $contribution,
                $retainedMedia,
                $newMedia,
                $oldStatus
            ): void {
                $action = $validated['submission_action'];
                $isSubmitting = $action === 'submit';

                $contribution->fill([
                    ...$this->contributionData($request, $validated),
                    'supporting_media' => [...$retainedMedia, ...$newMedia] ?: null,
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

                $contribution->recordVersion(
                    $request->user(),
                    $isSubmitting
                        ? ($oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED ? 'resubmitted' : 'submitted')
                        : ($oldStatus === HeritageShopContribution::STATUS_REVISION_REQUIRED ? 'revision_updated' : 'draft_updated')
                );
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newMedia);
            throw $exception;
        }

        Storage::disk('public')->delete($requestedRemovals);

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
        abort_unless(
            $contribution->user_id === $request->user()->id
            && $contribution->status === HeritageShopContribution::STATUS_DRAFT,
            403
        );

        $media = $contribution->supporting_media ?? [];
        $contribution->delete();
        Storage::disk('public')->delete($media);

        return redirect()->route('community-contribution.drafts')
            ->with('status', 'Draft deleted successfully.');
    }

    public function submitDraft(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        abort_unless(
            $contribution->user_id === $request->user()->id
            && $contribution->status === HeritageShopContribution::STATUS_DRAFT,
            403
        );

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
        ];

        $query = $request->user()->heritageShopContributions()
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
            ->latest()
            ->limit(5)
            ->get();

        return view('community-contributions.index', compact(
            'contributions',
            'notifications',
            'allowedStatuses'
        ));
    }

    public function show(Request $request, HeritageShopContribution $contribution): View
    {
        abort_unless($contribution->user_id === $request->user()->id, 403);

        $contribution->load(['versions.user', 'moderationActivities.actor']);
        $request->user()->unreadNotifications()
            ->where('data->contribution_id', $contribution->id)
            ->update(['read_at' => now()]);

        return view('community-contributions.show', compact('contribution'));
    }

    public function withdraw(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        abort_unless($contribution->user_id === $request->user()->id, 403);

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
            'remove_media.*' => ['string'],
        ]);

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
                $closed = filter_var($schedule['closed'] ?? false, FILTER_VALIDATE_BOOL);

                return [
                    'day' => $this->normalizeText($schedule['day'] ?? null),
                    'open' => $closed ? null : $this->normalizeText($schedule['open'] ?? null),
                    'close' => $closed ? null : $this->normalizeText($schedule['close'] ?? null),
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

    private function storeNewMedia(Request $request): array
    {
        return collect($request->file('supporting_media', []))
            ->map(fn ($file) => $file->store('community-contributions/heritage-shops', 'public'))
            ->values()
            ->all();
    }

    private function normalizeText(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return blank($value) ? null : trim(strip_tags($value));
    }
}
