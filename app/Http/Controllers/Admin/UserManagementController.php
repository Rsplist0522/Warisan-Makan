<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->where('role', '!=', 'admin')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = '%'.$request->string('search')->trim()->toString().'%';

                $query->where(function ($builder) use ($term): void {
                    $builder->where('name', 'like', $term)
                        ->orWhere('username', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('city', 'like', $term);
                });
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                $query->where('status', $request->string('status')->toString());
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role !== 'admin', 403, 'Admin accounts cannot be deactivated from the member management module.');

        $isActive = $user->status !== 'inactive';

        $user->forceFill([
            'status' => $isActive ? 'inactive' : 'active',
            'deactivated_at' => $isActive ? now() : null,
            'deactivated_by_user_id' => $isActive ? $request->user()->id : null,
        ])->save();

        return redirect()->route('admin.users.index')->with('status', $isActive ? 'User account deactivated.' : 'User account activated.');
    }
}
