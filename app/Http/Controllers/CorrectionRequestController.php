<?php

namespace App\Http\Controllers;

use App\Models\CorrectionRequest;
use App\Models\HeritageShop;
use App\Rules\MalaysianPhoneNumber;
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
            'operatingHoursCorrection' => CorrectionRequest::operatingHoursEditorValue($heritageShop),
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
                    'suggested_value' => $validated['suggested_value'],
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
        $validated = $request->validate([
            'field_name' => ['required', Rule::in(array_keys(CorrectionRequest::allowedFields()))],
            'reason' => ['required', 'string', 'max:5000'],
            'evidence' => ['nullable', 'array', 'max:6'],
            'evidence.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,avi', 'max:20480'],
        ]);

        $field = $validated['field_name'];
        $validated['suggested_value'] = $this->suggestedValueForField($request, $field);

        return $validated;
    }

    private function currentValueForField(HeritageShop $heritageShop, string $field): string
    {
        return CorrectionRequest::currentPublishedValue($heritageShop, $field);
    }

    private function suggestedValueForField(Request $request, string $field): string
    {
        $structuredTargets = CorrectionRequest::structuredFieldTargets()[$field] ?? null;

        if ($structuredTargets !== null) {
            $rules = collect($structuredTargets)
                ->mapWithKeys(fn (string $label, string $target): array => ["suggested_fields.{$target}" => ['nullable', 'string', 'max:5000']])
                ->all();
            $request->validate([
                'suggested_fields' => ['nullable', 'array'],
                ...$rules,
            ]);

            $values = collect(array_keys($structuredTargets))
                ->mapWithKeys(fn (string $target): array => [$target => $this->normalizeText($request->input("suggested_fields.{$target}"))])
                ->filter(fn (?string $value): bool => filled($value))
                ->all();

            if ($values === []) {
                throw ValidationException::withMessages([
                    'suggested_fields' => __('Please provide at least one corrected value for the selected information.'),
                ]);
            }

            return json_encode($values, JSON_THROW_ON_ERROR);
        }

        if ($field === 'operating_hours') {
            return json_encode($this->validatedOperatingHoursCorrection($request), JSON_THROW_ON_ERROR);
        }

        if ($field === 'map_location') {
            throw ValidationException::withMessages([
                'field_name' => __('The selected field is invalid.'),
            ]);
        }

        $rules = match ($field) {
            'shop_name' => ['required', 'string', 'max:255'],
            'primary_food_category' => ['required', 'string', 'max:255'],
            'establishment_year' => ['required', 'integer', 'min:1000', 'max:'.now()->year],
            'heritage_story' => ['required', 'string', 'max:10000'],
            'contact_number' => ['required', 'string', 'max:30', new MalaysianPhoneNumber],
            default => ['required', 'string', 'max:5000'],
        };

        $validated = $request->validate([
            'suggested_value' => $rules,
        ]);

        $value = $this->normalizeText($validated['suggested_value'] ?? null);

        if ($value === null) {
            throw ValidationException::withMessages([
                'suggested_value' => __('Please provide the corrected information.'),
            ]);
        }

        return $value;
    }

    private function validatedOperatingHoursCorrection(Request $request): array
    {
        $request->validate([
            'operating_hours_correction' => ['required', 'array'],
            'operating_hours_correction.*.closed' => ['nullable', 'boolean'],
            'operating_hours_correction.*.periods' => ['nullable', 'array', 'max:6'],
            'operating_hours_correction.*.periods.*.open' => ['nullable', 'date_format:H:i'],
            'operating_hours_correction.*.periods.*.close' => ['nullable', 'date_format:H:i'],
        ]);

        $submitted = $request->input('operating_hours_correction', []);
        $allowedDays = CorrectionRequest::OPERATING_HOUR_DAYS;
        $errors = [];
        $normalized = [];

        foreach ($submitted as $day => $schedule) {
            if (! in_array($day, $allowedDays, true)) {
                $errors["operating_hours_correction.{$day}"] = __('Please use valid weekday names only.');
                continue;
            }

            if (! is_array($schedule)) {
                $errors["operating_hours_correction.{$day}"] = __('The schedule for :day is malformed.', ['day' => $day]);
                continue;
            }

            $closed = filter_var($schedule['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($closed) {
                $normalized[] = [
                    'day' => $day,
                    'open' => null,
                    'close' => null,
                    'closed' => true,
                ];
                continue;
            }

            $periods = is_array($schedule['periods'] ?? null) ? $schedule['periods'] : [];
            $dayPeriods = [];

            foreach ($periods as $index => $period) {
                if (! is_array($period)) {
                    $errors["operating_hours_correction.{$day}.periods.{$index}"] = __('The time period for :day is malformed.', ['day' => $day]);
                    continue;
                }

                $open = trim((string) ($period['open'] ?? ''));
                $close = trim((string) ($period['close'] ?? ''));

                if ($open === '' && $close === '') {
                    continue;
                }

                if ($open === '' || $close === '') {
                    $errors["operating_hours_correction.{$day}.periods.{$index}.open"] = __('Opening and closing time are both required for each open period.');
                    continue;
                }

                if ($open === $close) {
                    $errors["operating_hours_correction.{$day}.periods.{$index}.close"] = __('Opening and closing time cannot be identical.');
                    continue;
                }

                $dayPeriods[] = ['open' => $open, 'close' => $close];
            }

            $seenPairs = [];
            foreach ($dayPeriods as $index => $period) {
                $pairKey = $period['open'].'|'.$period['close'];
                if (isset($seenPairs[$pairKey])) {
                    $errors["operating_hours_correction.{$day}.periods.{$index}.open"] = __('Duplicate operating periods on the same day are not allowed.');
                    continue;
                }

                $seenPairs[$pairKey] = true;
            }

            foreach ($dayPeriods as $index => $period) {
                $periodStart = strtotime('1970-01-01 '.$period['open']);
                $periodEnd = strtotime('1970-01-01 '.$period['close']);

                foreach (array_slice($dayPeriods, $index + 1) as $otherIndex => $other) {
                    $otherStart = strtotime('1970-01-01 '.$other['open']);
                    $otherEnd = strtotime('1970-01-01 '.$other['close']);

                    $rangesOverlap = $periodStart < $otherEnd && $otherStart < $periodEnd;
                    if ($rangesOverlap) {
                        $errors["operating_hours_correction.{$day}.periods.{$index}.open"] = __('Operating periods on the same day must not overlap.');
                        $errors["operating_hours_correction.{$day}.periods.".(($index + 1) + $otherIndex).'.open'] = __('Operating periods on the same day must not overlap.');
                    }
                }
            }

            foreach ($dayPeriods as $period) {
                $normalized[] = [
                    'day' => $day,
                    'open' => $period['open'],
                    'close' => $period['close'],
                    'closed' => false,
                ];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'operating_hours_correction' => __('Please provide at least one open period or closed day.'),
            ]);
        }

        return $normalized;
    }

    private function storeEvidence(Request $request, string $field, string $directory, int $userId, int $startOrder = 0): array
    {
        return collect($request->file($field, []))
            ->values()
            ->map(function (UploadedFile $file, int $index) use ($field, $directory, $userId, $startOrder): array {
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
