<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeritageShopContribution;
use App\Notifications\ContributionStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminCommunityContributionController extends Controller
{
    public function submissions(Request $request): View
    {
        $activeStatuses = [
            HeritageShopContribution::STATUS_PENDING_REVIEW,
            HeritageShopContribution::STATUS_UNDER_REVIEW,
        ];

        $query = HeritageShopContribution::query()
            ->with('user')
            ->whereIn('status', $activeStatuses);

        $this->applySearch($query, $request);

        if (in_array($request->input('status'), $activeStatuses, true)) {
            $query->where('status', $request->input('status'));
        }

        $submissions = $query
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [HeritageShopContribution::STATUS_PENDING_REVIEW])
            ->oldest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('community-contributions.admin.submissions', compact('activeStatuses', 'submissions'));
    }

    public function show(HeritageShopContribution $contribution): View
    {
        $contribution->load(['user', 'versions.user', 'moderationActivities.actor']);

        return view('community-contributions.admin.show', compact('contribution'));
    }

    public function startReview(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        DB::transaction(function () use ($request, $contribution) {
            $lockedContribution = HeritageShopContribution::query()
                ->lockForUpdate()
                ->findOrFail($contribution->id);

            if ($lockedContribution->status !== HeritageShopContribution::STATUS_PENDING_REVIEW) {
                throw ValidationException::withMessages([
                    'review' => 'Only a pending contribution can be moved into review.',
                ]);
            }

            $fromStatus = $lockedContribution->status;
            $lockedContribution->update([
                'status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
                'reviewed_by_user_id' => $request->user()->id,
                'review_started_at' => now(),
            ]);
            $lockedContribution->moderationActivities()->create([
                'actor_user_id' => $request->user()->id,
                'action' => 'start_review',
                'from_status' => $fromStatus,
                'to_status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
            ]);
        });

        return redirect()
            ->route('admin.community-contributions.show', $contribution)
            ->with('status', 'Review started. The contributor can no longer withdraw this submission.');
    }

    public function moderate(Request $request, HeritageShopContribution $contribution): RedirectResponse
    {
        $validated = $request->validate([
            'moderation_action' => ['required', Rule::in(['approve', 'request_revision', 'reject', 'delete'])],
            'feedback' => [
                Rule::requiredIf(in_array($request->input('moderation_action'), ['request_revision', 'reject', 'delete'], true)),
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $processedContribution = DB::transaction(function () use ($request, $validated, $contribution) {
            $lockedContribution = HeritageShopContribution::query()
                ->lockForUpdate()
                ->findOrFail($contribution->id);

            if ($lockedContribution->status !== HeritageShopContribution::STATUS_UNDER_REVIEW) {
                throw ValidationException::withMessages([
                    'moderation_action' => 'Only a contribution under review can be moderated.',
                ]);
            }

            $action = $validated['moderation_action'];
            $feedback = $this->normalizeText($validated['feedback'] ?? null);
            $fromStatus = $lockedContribution->status;

            if ($action === 'approve') {
                $this->validateForApproval($lockedContribution);
                $lockedContribution->forceFill(['admin_feedback' => $feedback])->save();
                $lockedContribution->approve($request->user());
                $toStatus = HeritageShopContribution::STATUS_APPROVED;
                $activityAction = 'approved';
            } elseif ($action === 'request_revision') {
                $toStatus = HeritageShopContribution::STATUS_REVISION_REQUIRED;
                $activityAction = 'revision_requested';
                $lockedContribution->update([
                    'status' => $toStatus,
                    'admin_feedback' => $feedback,
                    'rejection_reason' => null,
                ]);
            } elseif ($action === 'reject') {
                $toStatus = HeritageShopContribution::STATUS_REJECTED;
                $activityAction = 'rejected';
                $lockedContribution->update([
                    'status' => $toStatus,
                    'admin_feedback' => $feedback,
                    'rejection_reason' => $feedback,
                ]);
            } else {
                $toStatus = HeritageShopContribution::STATUS_DELETED;
                $activityAction = 'deleted';
                $lockedContribution->update([
                    'status' => $toStatus,
                    'admin_feedback' => $feedback,
                ]);
            }

            $lockedContribution->moderationActivities()->create([
                'actor_user_id' => $request->user()->id,
                'action' => $activityAction,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'comment' => $feedback,
                'metadata' => ['version_number' => $lockedContribution->versions()->max('version_number')],
            ]);

            return $lockedContribution->fresh('user');
        });

        $processedContribution->user?->notify(new ContributionStatusChanged($processedContribution));

        return redirect()
            ->route('admin.community-contributions.show', $processedContribution)
            ->with('status', 'Moderation decision recorded successfully.');
    }

    public function history(Request $request): View
    {
        $historyStatuses = [
            HeritageShopContribution::STATUS_REVISION_REQUIRED,
            HeritageShopContribution::STATUS_APPROVED,
            HeritageShopContribution::STATUS_REJECTED,
            HeritageShopContribution::STATUS_WITHDRAWN,
            HeritageShopContribution::STATUS_DELETED,
        ];

        $query = HeritageShopContribution::query()
            ->with(['user', 'moderationActivities.actor'])
            ->whereIn('status', $historyStatuses);

        $this->applySearch($query, $request);

        if (in_array($request->input('status'), $historyStatuses, true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('updated_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('updated_at', '<=', $request->input('date_to'));
        }

        $history = $query->latest('updated_at')->paginate(15)->withQueryString();

        return view('community-contributions.admin.history', compact('history', 'historyStatuses'));
    }

    private function applySearch($query, Request $request): void
    {
        if (! $request->filled('search')) {
            return;
        }

        $search = trim((string) $request->input('search'));
        $query->where(function ($builder) use ($search) {
            $builder->where('contribution_title', 'like', "%{$search}%")
                ->orWhere('shop_name', 'like', "%{$search}%")
                ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
        });
    }

    private function validateForApproval(HeritageShopContribution $contribution): void
    {
        Validator::make($contribution->toArray(), [
            'contribution_title' => ['required', 'string'],
            'shop_name' => ['required', 'string'],
            'primary_food_category' => ['required', 'string'],
            'establishment_year' => ['required', 'integer'],
            'founder_name' => ['required', 'string'],
            'founder_background' => ['required', 'string'],
            'current_owner_name' => ['required', 'string'],
            'heritage_story' => ['required', 'string'],
            'address' => ['required', 'string'],
        ])->validate();
    }

    private function normalizeText(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return filled($value) ? trim(strip_tags($value)) : null;
    }
}
