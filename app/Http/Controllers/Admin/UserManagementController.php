<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Badge;
use App\Models\PassportStamp;
use App\Models\UserBadge;
use App\Models\HeritageShop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        // Base query for all non-admin members.
        $memberQuery = User::query()
            ->where('role', '!=', 'admin');

        // Existing member list with search, status filter, sorting, and pagination.
        $users = (clone $memberQuery)
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
                $query->where(
                    'status',
                   $request->string('status')->toString()
                );
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Summary counts for all non-admin members.
        // These intentionally ignore the current search and status filters.
        $totalMembers = (clone $memberQuery)->count();

        $activeMembers = (clone $memberQuery)
            ->where('status', 'active')
            ->count();

        $inactiveMembers = (clone $memberQuery)
            ->where('status', 'inactive')
            ->count();

        return view('admin.users.index', compact(
            'users',
            'totalMembers',
            'activeMembers',
            'inactiveMembers'
        ));
    }

    public function show(User $user): View
{
    abort_unless(
        $user->role !== 'admin',
        404
    );

    $userBadges = UserBadge::query()
        ->where('user_id', $user->id)
        ->with('badge')
        ->orderByDesc('earned_at')
        ->get();

    $stamps = PassportStamp::query()
        ->where('user_id', $user->id)
        ->orderByDesc('stamp_datetime')
        ->get();

    $shopIds = $stamps
        ->pluck('shop_id')
        ->filter()
        ->unique()
        ->values();

    $visitedShops = HeritageShop::query()
        ->whereIn('id', $shopIds)
        ->get()
        ->keyBy('id');

    $shopVisitCount = $visitedShops->count();

    return view('admin.users.show', [
        'user' => $user,
        'userBadges' => $userBadges,
        'stamps' => $stamps,
        'visitedShops' => $visitedShops,
        'shopVisitCount' => $shopVisitCount,
    ]);
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
