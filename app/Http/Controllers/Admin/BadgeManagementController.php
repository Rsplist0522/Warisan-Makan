<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PassportController;
use App\Models\Badge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class BadgeManagementController extends Controller
{
    public function index(): View
    {
        $badges = Badge::query()->withCount('userBadges')->orderByDesc('is_active')->orderBy('criteria_value')->orderBy('badge_name')->get();

        return view('admin.badges.index', compact('badges'));
    }

    public function create(): View
    {
        return view('admin.badges.form', ['badge' => new Badge(['is_active' => true]), 'mode' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $badge = Badge::create($this->validatedPayload($request));
        Cache::forget(PassportController::LEADERBOARD_CACHE_KEY);

        return redirect()->route('admin.badges.edit', $badge)->with('status', 'Badge created successfully.');
    }

    public function edit(Badge $badge): View
    {
        return view('admin.badges.form', ['badge' => $badge, 'mode' => 'edit']);
    }

    public function update(Request $request, Badge $badge): RedirectResponse
    {
        $payload = $this->validatedPayload($request);

        if ($badge->is_active && ! $payload['is_active'] && $badge->userBadges()->exists()) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'badge' => 'This badge cannot be deactivated because it has already been earned by one or more users.',
                ]);
        }

        $badge->update($payload);
        Cache::forget(PassportController::LEADERBOARD_CACHE_KEY);

        return redirect()->route('admin.badges.edit', $badge)->with('status', 'Badge updated successfully.');
    }

    public function toggle(Badge $badge): RedirectResponse
    {
        if ($badge->is_active && $badge->userBadges()->exists()) {
            return redirect()->route('admin.badges.index')
                ->withErrors([
                    'badge' => 'Deactivation failed. Badge has already been awarded to users',
                ]);
        }

        $badge->update(['is_active' => ! $badge->is_active]);
        Cache::forget(PassportController::LEADERBOARD_CACHE_KEY);

        return redirect()->route('admin.badges.index')
            ->with('status', $badge->is_active ? 'Badge activated successfully.' : 'Badge deactivated successfully.');
    }

    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'badge_name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'criteria_type' => ['required', 'in:visits'],
            'criteria_value' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
