<?php

namespace App\Http\Controllers;

use App\Models\CorrectionRequest;
use App\Models\HeritageShop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
                        $shopQuery->where('shop_name', 'like', $search);
                    });
            });
        }

        $correctionRequests = $query->paginate(10)->withQueryString();
        $notifications = $request->user()->notifications()
            ->whereNotNull('data->correction_request_id')
            ->whereNull('read_at')
            ->latest()
            ->limit(5)
            ->get();
        $notificationCorrectionRequests = CorrectionRequest::query()
            ->whereIn('id', $notifications->pluck('data.correction_request_id')->filter()->unique()->all())
            ->get()
            ->keyBy('id');

        return view('community-contributions.correction-requests.index', compact(
            'allowedStatuses',
            'correctionRequests',
            'notificationCorrectionRequests',
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
        $storedMedia = [];

        try {
            $correctionRequest = DB::transaction(function () use ($request, $heritageShop, $validated, &$storedMedia) {
                $correctionRequest = CorrectionRequest::create([
                    'user_id' => $request->user()->id,
                    'heritage_shop_id' => $heritageShop->id,
                    'field_name' => $validated['field_name'],
                    'current_value' => $this->currentValueForField($heritageShop, $validated['field_name']),
                    'suggested_value' => $this->normalizeText($validated['suggested_value']),
                    'reason' => $this->normalizeText($validated['reason']),
                    'status' => CorrectionRequest::STATUS_PENDING,
                ]);

                $storedMedia = $this->storeEvidence(
                    $request,
                    'evidence',
                    "correction-requests/{$correctionRequest->id}",
                    $request->user()->id
                );
                $this->createMediaRecords($correctionRequest, $storedMedia);

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
            $this->deleteStoredMedia($storedMedia);
            throw $exception;
        }

        return redirect()->route('community-contribution.correction-requests.show', $correctionRequest)
            ->with('status', __('Correction request submitted for review.'));
    }

    public function show(Request $request, CorrectionRequest $correctionRequest): View
    {
        abort_unless((int) $correctionRequest->user_id === (int) $request->user()->id, 403);

        $correctionRequest->load(['media', 'heritageShop', 'reviewedBy', 'moderationActivities.actor']);
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
            'additional_evidence.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,avi', 'max:20480'],
        ]);

        $correctionRequest->load('media');
        $existingEvidenceCount = $correctionRequest->media->count();
        $newFiles = $request->file('additional_evidence', []);

        if ($existingEvidenceCount + count($newFiles) > 8) {
            throw ValidationException::withMessages([
                'additional_evidence' => __('A correction request may contain no more than 8 evidence files.'),
            ]);
        }

        $fromStatus = $correctionRequest->status;
        $storedMedia = [];

        try {
            DB::transaction(function () use ($request, $correctionRequest, $validated, $existingEvidenceCount, &$storedMedia, $fromStatus): void {
                $correctionRequest->forceFill([
                    'additional_information' => $this->normalizeText($validated['additional_information']),
                    'status' => CorrectionRequest::STATUS_PENDING,
                    'reviewed_by_user_id' => null,
                    'review_started_at' => null,
                    'reviewed_at' => null,
                ])->save();

                $storedMedia = $this->storeEvidence(
                    $request,
                    'additional_evidence',
                    "correction-requests/{$correctionRequest->id}",
                    $request->user()->id,
                    $existingEvidenceCount
                );
                $this->createMediaRecords($correctionRequest, $storedMedia);

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
            $this->deleteStoredMedia($storedMedia);
            throw $exception;
        }

        return redirect()->route('community-contribution.correction-requests.show', $correctionRequest)
            ->with('status', __('Additional information submitted. Your correction request is pending review again.'));
    }

    private function validateCorrectionRequest(Request $request): array
    {
        return $request->validate([
            'field_name' => ['required', Rule::in(array_keys(CorrectionRequest::allowedFields()))],
            'suggested_value' => ['required', 'string', 'max:5000'],
            'reason' => ['required', 'string', 'max:5000'],
            'evidence' => ['nullable', 'array', 'max:6'],
            'evidence.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,avi', 'max:20480'],
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

        return filled($value) ? (string) $value : __('Not provided');
    }

    private function storeEvidence(Request $request, string $field, string $directory, int $userId, int $startOrder = 0): array
    {
        return collect($request->file($field, []))
            ->values()
            ->map(function (UploadedFile $file, int $index) use ($directory, $userId, $startOrder): array {
                $objectKey = $file->store($directory, config('filesystems.media_disk'));

                if ($objectKey === false) {
                    throw ValidationException::withMessages([
                        $field => __('The evidence file could not be uploaded. Please try again.'),
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

    private function createMediaRecords(CorrectionRequest $correctionRequest, array $records): void
    {
        foreach ($records as $record) {
            $correctionRequest->media()->create($record);
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
