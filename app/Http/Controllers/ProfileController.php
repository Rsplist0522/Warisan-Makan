<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\UserBadge;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        $badges = UserBadge::with('badge')
            ->where('user_id', $user->id)
            ->orderByDesc('earned_at')
            ->get();

        return view('profile.show', [
            'user' => $user,
            'badges' => $badges,
        ]);
    }


    public function edit()
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => 'required|string|max:40',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => ['nullable', 'regex:/^[0-9]{1,11}$/'],
            'city' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:700',
            'profile_photo' => 'nullable|image|max:3072',
            'language' => 'nullable|in:en,ms,zh',
        ], [
            'name.max' => 'Name cannot exceed 40 characters.',
            'city.max' => 'City cannot exceed 100 characters.',
            'profile_photo.max' => 'Profile photo size cannot exceed 3 MB',
        ]);

        $data['language'] = $data['language'] ?? $user->language ?? 'en';

        if ($request->hasFile('profile_photo')) {
            $disk = config('filesystems.media_disk', 'public');
            $path = $request->file('profile_photo')->store("users/{$user->id}/profile", $disk);

            if ($path === false) {
                return back()
                    ->withErrors(['profile_photo' => __('The profile photo could not be uploaded. Please try again.')])
                    ->withInput();
            }

            if ($user->profile_photo && Storage::disk($disk)->exists($user->profile_photo)) {
                Storage::disk($disk)->delete($user->profile_photo);
            }

            $data['profile_photo'] = $path;
        }

        $user->fill($data);
        $user->save();
        Cache::forget(PassportController::LEADERBOARD_CACHE_KEY);
        $request->session()->put('locale', $data['language']);


        if ($request->boolean('language_only')) {
            return redirect()->back();
        }

        return redirect()
            ->route('profile.show')
            ->with('success', __('Profile updated successfully.'));
    }
}
