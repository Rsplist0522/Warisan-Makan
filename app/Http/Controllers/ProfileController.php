<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        return view('profile.show', [
            'user' => auth()->user(),
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:40',
            'city' => 'nullable|string|max:80',
            'bio' => 'nullable|string|max:700',
            'profile_photo' => 'nullable|image|max:2048',
            'language' => 'nullable|in:en,ms,zh',
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

        if ($request->boolean('language_only')) {
            return redirect()->route('home');
        }

        return redirect()->route('profile.show')->with('success', __('Profile updated successfully.'));
    }
}
