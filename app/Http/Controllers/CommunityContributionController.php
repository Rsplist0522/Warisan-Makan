<?php

namespace App\Http\Controllers;

use App\Models\HeritageShopContribution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommunityContributionController extends Controller
{
    public function index(): View
    {
        return view('community-contribution');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'submission_action' => ['required', Rule::in(['draft', 'submit'])],
            'shop_name' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:255'],
            'primary_food_category' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:255'],
            'establishment_year' => ['required_if:submission_action,submit', 'nullable', 'integer', 'min:1000', 'max:' . now()->year],
            'founder_name' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:255'],
            'founder_background' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:5000'],
            'current_owner_name' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:255'],
            'heritage_story' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:10000'],
            'operating_hours' => ['nullable', 'array'],
            'operating_hours.*.day' => ['nullable', 'string', 'max:30'],
            'operating_hours.*.open' => ['nullable', 'date_format:H:i'],
            'operating_hours.*.close' => ['nullable', 'date_format:H:i'],
            'operating_hours.*.closed' => ['nullable', 'boolean'],
            'food_items' => ['nullable', 'array'],
            'food_items.*.name' => ['nullable', 'string', 'max:255'],
            'food_items.*.desc' => ['nullable', 'string', 'max:1000'],
            'address' => ['required_if:submission_action,submit', 'nullable', 'string', 'max:500'],
            'supporting_media' => ['nullable', 'array', 'max:6'],
            'supporting_media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,avi', 'max:20480'],
        ]);

        $submissionAction = $validated['submission_action'];
        unset($validated['submission_action']);

        $textFields = [
            'shop_name',
            'primary_food_category',
            'founder_name',
            'founder_background',
            'current_owner_name',
            'heritage_story',
            'address',
        ];

        foreach ($textFields as $field) {
            $validated[$field] = $this->normalizeText($validated[$field] ?? null);
        }

        if (isset($validated['establishment_year'])) {
            $validated['establishment_year'] = (int) $validated['establishment_year'];
        }

        $validated['operating_hours'] = collect($request->input('operating_hours', []))
            ->filter(fn (array $daySchedule) => ! empty($daySchedule['day'] ?? null))
            ->map(function (array $daySchedule): array {
                return [
                    'day' => $this->normalizeText($daySchedule['day'] ?? null),
                    'open' => $this->normalizeText($daySchedule['open'] ?? null),
                    'close' => $this->normalizeText($daySchedule['close'] ?? null),
                    'closed' => filter_var($daySchedule['closed'] ?? false, FILTER_VALIDATE_BOOL),
                ];
            })
            ->values()
            ->all();

        $validated['food_items'] = collect($request->input('food_items', []))
            ->filter(fn (array $foodItem) => ! empty($foodItem['name'] ?? null) || ! empty($foodItem['desc'] ?? null))
            ->map(function (array $foodItem): array {
                return [
                    'name' => $this->normalizeText($foodItem['name'] ?? null),
                    'desc' => $this->normalizeText($foodItem['desc'] ?? null),
                ];
            })
            ->values()
            ->all();

        $mediaPaths = [];

        foreach ($request->file('supporting_media', []) as $uploadedFile) {
            $mediaPaths[] = $uploadedFile->store('community-contributions/heritage-shops', 'public');
        }

        HeritageShopContribution::create([
            ...$validated,
            'user_id' => $request->user()?->id,
            'supporting_media' => $mediaPaths ?: null,
            'status' => $submissionAction === 'draft'
                ? HeritageShopContribution::STATUS_DRAFT
                : HeritageShopContribution::STATUS_PENDING_REVIEW,
            'submitted_at' => $submissionAction === 'submit' ? now() : null,
        ]);

        $message = $submissionAction === 'draft'
            ? 'Heritage shop draft saved successfully.'
            : 'Heritage shop information submitted for review.';

        return redirect()->route('home')->with('status', $message);
    }

    private function normalizeText(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === null || $value === '') {
            return null;
        }

        return trim(strip_tags($value));
    }
}