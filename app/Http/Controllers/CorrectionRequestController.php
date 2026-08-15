<?php

namespace App\Http\Controllers;

use App\Models\CorrectionRequest;
use App\Models\HeritageShop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CorrectionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $allowedStatuses = CorrectionRequest::statuses();

        $query = $request->user()->correctionRequests()
            ->with('heritageShop')
            ->latest('updated_at');

        if ($request->filled('status') && in_array($request->string('status')->toString(), $allowedStatuses, true)) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim()->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('field_name', 'like', $search)
                    ->orWhere('suggested_value', 'like', $search)
                    ->orWhereHas('heritageShop', function ($shopQuery) use ($search): void {
                        $shopQuery->where('shop_name', 'like', $search)
                            ->orWhere('name', 'like', $search);
                    });
            });
        }

        $correctionRequests = $query->paginate(10)->withQueryString();
        $notifications = $request->user()->notifications()
            ->whereNotNull('data->correction_request_id')
            ->latest()
            ->limit(5)
            ->get();

        return view('community-contributions.correction-requests.index', compact(
            'allowedStatuses',
            'correctionRequests',
            'notifications'
        ));
    }

    public function create(HeritageShop $heritageShop): View
    {
        return view('community-contributions.correction-requests.create', [
            'heritageShop' => $heritageShop,
            'allowedFields' => CorrectionRequest::allowedFields(),
            'currentValues' => collect(array_keys(CorrectionRequest::allowedFields()))
                ->mapWithKeys(fn (string $field): array => [$field => $this->currentValueForField($heritageShop, $field)])
                ->all(),
        ]);
    }

    public function store(Request $request, HeritageShop $heritageShop): RedirectResponse
    {
        $validated = $this->validateCorrectionRequest($request);
        $evidence = $this->storeEvidence($request, 'evidence');

        try {
            $correctionRequest = DB::transaction(function () use ($request, $heritageShop, $validated, $evidence) {
                $correctionRequest = CorrectionRequest::create([
                    'user_id' => $request->user()->id,
                    'heritage_shop_id' => $heritageShop->id,
                    'field_name' => $validated['field_name'],
                    'current_value' => $this->currentValueForField($heritageShop, $validated['field_name']),
                    'suggested_value' => $this->normalizeText($validated['suggested_value']),
                    'reason' => $this->normalizeText($validated['reason']),
                    'evidence_paths' => $evidence ?: null,
                    'status' => CorrectionRequest::STATUS_PENDING,
                ]);

                $this->recordCorrectionActivity(
                    $correctionRequest,
                    $request->user()->id,
                    'correction_submitted',
                    null,
                    CorrectionRequest::STATUS_PENDING
                );

                return $correctionRequest;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($evidence);
            throw $exception;
        }

        return redirect()->route('community-contribution.correction-requests.show', $correctionRequest)
            ->with('status', 'Correction request submitted for review.');
    }

    public function show(Request $request, CorrectionRequest $correctionRequest): View
    {
        abort_unless((int) $correctionRequest->user_id === (int) $request->user()->id, 403);

        $correctionRequest->load(['heritageShop', 'reviewedBy', 'moderationActivities.actor']);
        $request->user()->unreadNotifications()
            ->where('data->correction_request_id', $correctionRequest->id)
            ->update(['read_at' => now()]);

        return view('community-contributions.correction-requests.show', compact('correctionRequest'));
    }

    public function provideInformation(Request $request, CorrectionRequest $correctionRequest): RedirectResponse
    {
        abort_unless($correctionRequest->canReceiveAdditionalInformationFrom($request->user()), 403);

        $validated = $request->validate([
            'additional_information' => ['required', 'string', 'max:5000'],
            'additional_evidence' => ['nullable', 'array', 'max:4'],
            'additional_evidence.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:10240'],
        ]);

        $existingEvidence = $correctionRequest->evidence_paths ?? [];
        $newEvidence = $this->storeEvidence($request, 'additional_evidence');

        if (count($existingEvidence) + count($newEvidence) > 8) {
            Storage::disk('public')->delete($newEvidence);

            throw ValidationException::withMessages([
                'additional_evidence' => 'A correction request may contain no more than 8 evidence files.',
            ]);
        }

        $fromStatus = $correctionRequest->status;

        try {
            DB::transaction(function () use ($request, $correctionRequest, $validated, $existingEvidence, $newEvidence, $fromStatus): void {
                $correctionRequest->forceFill([
                    'additional_information' => $this->normalizeText($validated['additional_information']),
                    'evidence_paths' => [...$existingEvidence, ...$newEvidence] ?: null,
                    'status' => CorrectionRequest::STATUS_PENDING,
                    'reviewed_by_user_id' => null,
                    'review_started_at' => null,
                    'reviewed_at' => null,
                ])->save();

                $this->recordCorrectionActivity(
                    $correctionRequest,
                    $request->user()->id,
                    'additional_information_provided',
                    $fromStatus,
                    CorrectionRequest::STATUS_PENDING,
                    $correctionRequest->additional_information
                );
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newEvidence);
            throw $exception;
        }

        return redirect()->route('community-contribution.correction-requests.show', $correctionRequest)
            ->with('status', 'Additional information submitted. Your correction request is pending review again.');
    }

    private function validateCorrectionRequest(Request $request): array
    {
        return $request->validate([
            'field_name' => ['required', Rule::in(array_keys(CorrectionRequest::allowedFields()))],
            'suggested_value' => ['required', 'string', 'max:5000'],
            'reason' => ['required', 'string', 'max:5000'],
            'evidence' => ['nullable', 'array', 'max:6'],
            'evidence.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:10240'],
        ]);
    }

    private function currentValueForField(HeritageShop $heritageShop, string $field): string
    {
        $value = $heritageShop->{$field};

        if (is_array($value)) {
            return collect($value)
                ->map(function ($item): string {
                    if (is_array($item)) {
                        return collect($item)
                            ->filter(fn ($value) => filled($value))
                            ->map(fn ($value, $key): string => str($key)->replace('_', ' ')->title().': '.$value)
                            ->join(', ');
                    }

                    return (string) $item;
                })
                ->filter()
                ->join("\n");
        }

        return filled($value) ? (string) $value : 'Not provided';
    }

    private function storeEvidence(Request $request, string $field): array
    {
        return collect($request->file($field, []))
            ->map(fn ($file) => $file->store('community-contributions/correction-requests', 'public'))
            ->values()
            ->all();
    }

    private function recordCorrectionActivity(
        CorrectionRequest $correctionRequest,
        int $actorUserId,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $comment = null
    ): void {
        $correctionRequest->moderationActivities()->create([
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
        ]);
    }

    private function normalizeText(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return blank($value) ? null : trim(strip_tags($value));
    }
}
