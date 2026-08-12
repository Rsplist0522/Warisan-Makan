<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeritageShopContribution;
use App\Models\ModerationActivity;
use App\Notifications\ContributionStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $search));
            });
        }

        $submissions = $query->paginate(15)->withQueryString();

        return view('community-contributions.admin.submissions', compact('submissions', 'activeStatuses'));
    }

    public function show(HeritageShopContribution $contribution): View
    {
        $contribution->load(['user', 'reviewedBy', 'versions.user', 'moderationActivities.actor']);

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
            'moderation_action' => ['required', Rule::in(['approve', 'reject', 'request_revision'])],
            'feedback' => [
                Rule::requiredIf(fn () => in_array($request->input('moderation_action'), ['reject', 'request_revision'], true)),
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        if ($contribution->status !== HeritageShopContribution::STATUS_UNDER_REVIEW) {
            return back()->withErrors(['moderation' => 'Start the review before selecting a moderation outcome.']);
        }

        $admin = $request->user();
        $fromStatus = $contribution->status;
        $feedback = filled($validated['feedback'] ?? null)
            ? trim(strip_tags($validated['feedback']))
            : null;

        DB::transaction(function () use (
            $validated,
            $contribution,
            $admin,
            $fromStatus,
            $feedback
        ): void {
            if ($validated['moderation_action'] === 'approve') {
                $contribution->forceFill(['admin_feedback' => $feedback])->save();
                $contribution->approve($admin);
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
            } else {
                $toStatus = HeritageShopContribution::STATUS_REVISION_REQUIRED;
                $contribution->forceFill([
                    'status' => $toStatus,
                    'admin_feedback' => $feedback,
                    'rejection_reason' => null,
                ])->save();
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

        $contribution->refresh();
        $contribution->user?->notify(new ContributionStatusChanged($contribution));

        return redirect()->route('admin.community-contributions.show', $contribution)
            ->with('status', 'Moderation outcome saved and the contributor was notified.');
    }

    public function history(Request $request): View
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $historyStatuses = [
            HeritageShopContribution::STATUS_REVISION_REQUIRED,
            HeritageShopContribution::STATUS_APPROVED,
            HeritageShopContribution::STATUS_REJECTED,
            HeritageShopContribution::STATUS_WITHDRAWN,
        ];
        $query = HeritageShopContribution::query()
            ->with(['user', 'reviewedBy', 'moderationActivities.actor'])
            ->whereIn('status', $historyStatuses)
            ->latest('updated_at');

        if ($request->filled('status') && in_array($request->string('status')->toString(), $historyStatuses, true)) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim()->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('contribution_title', 'like', $search)
                    ->orWhere('shop_name', 'like', $search)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $search));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('updated_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('updated_at', '<=', $request->date('date_to'));
        }

        $history = $query->paginate(15)->withQueryString();

        return view('community-contributions.admin.history', compact('history', 'historyStatuses'));
    }

    private function recordActivity(
        HeritageShopContribution $contribution,
        int $actorUserId,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $comment = null
    ): ModerationActivity {
        return $contribution->moderationActivities()->create([
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
        ]);
    }
}
