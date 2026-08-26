<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use App\Models\HeritageShopContribution;
use App\Models\ModerationActivity;
use App\Models\User;
use App\Notifications\ContributionStatusChanged;
use App\Notifications\CorrectionRequestStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AdminCommunityContributionController extends Controller
{
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
        ]);

        $allowedStatuses = CorrectionRequest::statuses();
        $query = CorrectionRequest::query()
            ->with(['user', 'heritageShop'])
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

        DB::transaction(function () use ($validated, $correctionRequest, $admin, $fromStatus, $comment): void {
            if ($validated['moderation_action'] === 'approve') {
                $correctionRequest->heritageShop->forceFill(
                    $correctionRequest->heritageShopUpdatePayload()
                )->save();

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
        ]);

        $historyStatuses = [
            HeritageShopContribution::STATUS_REVISION_REQUIRED,
            HeritageShopContribution::STATUS_APPROVED,
            HeritageShopContribution::STATUS_REJECTED,
            HeritageShopContribution::STATUS_WITHDRAWN,
            HeritageShopContribution::STATUS_DELETED,
        ];
        $query = HeritageShopContribution::withTrashed()
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
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search));
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
