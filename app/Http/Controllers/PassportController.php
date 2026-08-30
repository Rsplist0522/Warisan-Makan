<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\HeritageShop;
use App\Models\PassportStamp;
use App\Models\User;
use App\Models\UserBadge;
use App\Services\HeritageShopImageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PassportController extends Controller
{
    public const LEADERBOARD_CACHE_KEY = 'passport:leaderboard:top-ten';

    public function __construct(private HeritageShopImageService $imageService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $passportData = $this->buildPassportSummary($user);

        return view('foodPassport.index', [
            'shops' => $passportData['shops'],
            'availableShops' => $passportData['availableShops'],
            'stats' => $passportData['stats'],
            'visitedLocations' => $passportData['visitedLocations'],
            'badges' => $passportData['badges'],
            'leaderboard' => $passportData['leaderboard'],
        ]);
    }

    private function buildPassportSummary(?User $user): array
    {
        $shopsPage = LengthAwarePaginator::resolveCurrentPage('shops_page');
        $availableShops = $this->getActiveShops($shopsPage);
        $totalShops = $availableShops->total();
        $shopsForView = $availableShops->items();

        $currentPage = LengthAwarePaginator::resolveCurrentPage('visited_page');
        $visitedLocations = $user
            ? $this->visitedLocationsForUser($user, $currentPage)
            : new LengthAwarePaginator([], 0, 5, $currentPage, [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'visited_page',
            ]);
        $visitedCount = $visitedLocations->total();

        $completion = $totalShops > 0 ? (int) round(($visitedCount / $totalShops) * 100) : 0;
        $badgeList = $this->badgeSummary($user, $visitedCount);

        $stats = [
            'visited' => $visitedCount,
            'goal' => $totalShops,
            'badges' => count(array_filter($badgeList, fn ($badge) => $badge['earned'])),
            'completion' => $completion,
            'stamps' => $user ? PassportStamp::where('user_id', $user->id)->count() : 0,
        ];

        return [
            'shops' => $shopsForView,
            'availableShops' => $availableShops,
            'stats' => $stats,
            'visitedLocations' => $visitedLocations,
            'badges' => $badgeList,
            'leaderboard' => $this->buildLeaderboard(),
        ];
    }

    private function buildLeaderboard(): LengthAwarePaginator
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage('leaderboard_page');
        $rows = collect(Cache::remember(self::LEADERBOARD_CACHE_KEY, now()->addMinute(), function (): array {
            return $this->leaderboardRows()->all();
        }))->map(fn (array $row) => (object) $row);

        return new LengthAwarePaginator(
            $rows->forPage($currentPage, 5)->values(),
            $rows->count(),
            5,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'leaderboard_page']
        );
    }

    private function leaderboardRows()
    {
        $badgeDefinitions = Badge::query()
            ->where('is_active', true)
            ->orderBy('criteria_value')
            ->get(['badge_id', 'criteria_value'])
            ->map(fn (Badge $badge): array => [
                'id' => (int) $badge->badge_id,
                'threshold' => (int) $badge->criteria_value,
            ]);

        if ($badgeDefinitions->isEmpty()) {
            $badgeDefinitions = collect([1, 8, 15, 25, 40, 50])
                ->map(fn (int $threshold, int $index): array => [
                    'id' => $index + 1,
                    'threshold' => $threshold,
                ]);
        }

        $stampStats = PassportStamp::query()
            ->select('user_id')
            ->selectRaw('COUNT(*) as check_ins')
            ->selectRaw('COUNT(DISTINCT shop_id) as visited_shops')
            ->selectRaw('MAX(stamp_datetime) as last_check_in_at')
            ->groupBy('user_id');

        $rows = User::query()
            ->joinSub($stampStats, 'passport_stats', fn ($join) => $join->on('users.id', '=', 'passport_stats.user_id'))
            ->get([
                'users.id',
                'users.name',
                'passport_stats.check_ins',
                'passport_stats.visited_shops',
                'passport_stats.last_check_in_at',
            ]);

        $badgeIds = $badgeDefinitions->pluck('id');
        $awardedBadgesByUser = $badgeIds->isEmpty() || $rows->isEmpty()
            ? collect()
            : UserBadge::query()
                ->whereIn('user_id', $rows->pluck('id'))
                ->whereIn('badge_id', $badgeIds)
                ->get(['user_id', 'badge_id'])
                ->groupBy('user_id')
                ->map(fn ($badges) => $badges->pluck('badge_id')->map(fn ($id) => (int) $id)->all());

        $rankedRows = $rows->map(function ($row) use ($badgeDefinitions, $awardedBadgesByUser) {
            $eligibleBadgeIds = $badgeDefinitions
                ->filter(fn (array $badge) => (int) $row->visited_shops >= $badge['threshold'])
                ->pluck('id')
                ->all();
            $awardedBadgeIds = $awardedBadgesByUser->get($row->id, []);

            return [
                'id' => (int) $row->id,
                'name' => $row->name,
                'check_ins' => (int) $row->check_ins,
                'last_check_in_at' => $row->last_check_in_at,
                'last_check_in_label' => $row->last_check_in_at
                    ? Carbon::parse($row->last_check_in_at)->format('d M Y, H:i')
                    : 'No check-in yet',
                'badges_received' => count(array_unique(array_merge($eligibleBadgeIds, $awardedBadgeIds))),
            ];
        })->sort(function ($first, $second) {
            if ($first['badges_received'] !== $second['badges_received']) {
                return $second['badges_received'] <=> $first['badges_received'];
            }

            if ($first['check_ins'] !== $second['check_ins']) {
                return $second['check_ins'] <=> $first['check_ins'];
            }

            return strcmp((string) $second['last_check_in_at'], (string) $first['last_check_in_at']);
        })->take(10)->values();

        return $rankedRows->map(function ($row, $index) {
            $row['rank'] = $index + 1;

            return $row;
        });
    }

    private function getActiveShops(int $page): LengthAwarePaginator
    {
        $shops = HeritageShop::query()
            ->where(function ($query) {
                $query->whereIn('publish_status', ['approved', 'published', 'Published'])
                    ->orWhereNull('publish_status');
            })
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('images')
            ->orderBy('shop_name')
            ->paginate(3, ['id', 'shop_name', 'founder_name', 'latitude', 'longitude'], 'shops_page', $page);

        $shops->setCollection($shops->getCollection()->map(function (HeritageShop $shop): array {
            return [
                'id' => (int) $shop->id,
                'name' => $shop->shop_name ?? 'Heritage Shop',
                'founder' => $shop->founder_name ?? 'Local founder',
                'lat' => $shop->latitude,
                'lng' => $shop->longitude,
                'distance' => 'Nearby',
                'status' => 'Participating',
                'image' => $this->shopImage($shop),
            ];
        })->values());

        return $shops;
    }

    private function visitedLocationsForUser(User $user, int $page): LengthAwarePaginator
    {
        $stamps = PassportStamp::query()
            ->where('user_id', $user->id)
            ->select('shop_id')
            ->selectRaw('MAX(stamp_datetime) as stamp_datetime')
            ->groupBy('shop_id')
            ->orderByDesc('stamp_datetime')
            ->paginate(5, ['shop_id', DB::raw('MAX(stamp_datetime) as stamp_datetime')], 'visited_page', $page);

        $shops = HeritageShop::query()
            ->whereIn('id', $stamps->pluck('shop_id'))
            ->with('images')
            ->get(['id', 'shop_name', 'founder_name'])
            ->keyBy('id');

        $stamps->setCollection($stamps->getCollection()->map(function (PassportStamp $stamp) use ($shops): array {
            $shop = $shops->get($stamp->shop_id);

            return [
                'shop_id' => $stamp->shop_id,
                'shop_name' => $shop ? $shop->shop_name : 'Heritage shop #' . $stamp->shop_id,
                'founder' => $shop ? ($shop->founder_name ?? 'Local founder') : 'Shop details unavailable',
                'stamped_at' => $stamp->stamp_datetime ? Carbon::parse($stamp->stamp_datetime)->format('d M Y, H:i') : null,
                'image' => $shop ? $this->shopImage($shop) : null,
            ];
        })->values());

        return $stamps;
    }

    private function shopImage(HeritageShop $shop): ?string
    {
        $image = $shop->images->first();

        return $image ? $this->imageService->url($image) : null;
    }

    private function badgeSummary(?User $user, ?int $visitedCount = null): array
    {
        $records = Badge::query()
            ->where('is_active', true)
            ->orderBy('criteria_value')
            ->get();

        $visitedCount ??= $user
            ? PassportStamp::where('user_id', $user->id)->distinct('shop_id')->count('shop_id')
            : 0;

        if ($records->isEmpty()) {
            return [];
        }

        $awardedBadgeIds = $user ? UserBadge::where('user_id', $user->id)->pluck('badge_id')->all() : [];

        $badgeList = $records->map(function ($badge) use ($visitedCount, $awardedBadgeIds) {
            $eligible = $visitedCount >= (int) $badge->criteria_value;
            $earned = $eligible || in_array((int) $badge->badge_id, $awardedBadgeIds, true);

            return [
                'id' => (int) $badge->badge_id,
                'name' => $badge->badge_name,
                'description' => $badge->description,
                'icon' => $badge->icon ?: '★',
                'threshold' => (int) $badge->criteria_value,
                'earned' => $earned,
                'eligible' => $eligible,
                'progress' => min($visitedCount, (int) $badge->criteria_value) . '/' . (int) $badge->criteria_value,
            ];
        })->values()->all();

        return $badgeList;
    }

    private function earnedBadgeCountForUser(User $user): int
    {
        return count(array_filter(
            $this->badgeSummary($user),
            fn ($badge) => $badge['earned']
        ));
    }

    private function awardBadgesForUser(int $userId): array
    {
        if (! Schema::hasTable('badges') || ! Schema::hasTable('user_badges') || ! Schema::hasTable('passport_stamps')) {
            return [];
        }

        $user = User::find($userId);
        if (! $user) {
            return [];
        }

        $awarded = [];
        $uniqueVisits = PassportStamp::where('user_id', $userId)
            ->distinct('shop_id')
            ->count('shop_id');

        $badgeRecords = Badge::where('is_active', true)->get();
        $awardedBadgeIds = UserBadge::where('user_id', $userId)->pluck('badge_id')->all();

        foreach ($badgeRecords as $badge) {
            if (in_array((int) $badge->badge_id, $awardedBadgeIds, true)) {
                continue;
            }

            $criteriaType = $badge->criteria_type ?? 'visits';
            if ($criteriaType === 'visits' || (int) $badge->criteria_type === 0) {
                if ($uniqueVisits >= (int) $badge->criteria_value) {
                    UserBadge::create([
                        'user_id' => $userId,
                        'badge_id' => $badge->badge_id,
                        'earned_at' => Carbon::now(),
                    ]);

                    $awardedBadgeIds[] = (int) $badge->badge_id;
                    $awarded[] = $badge->badge_name;
                }
            }
        }

        return $awarded;
    }

    public function showShop(int $id)
    {
        $heritageShop = HeritageShop::query()
            ->whereKey($id)
            ->where(function ($query) {
                $query->whereIn('publish_status', ['approved', 'published', 'Published'])
                    ->orWhereNull('publish_status');
            })
            ->with(['images' => function ($query) {
                $query->where('is_primary', true)->orderBy('id');
            }])
            ->firstOrFail();

        $shop = (object) [
            'id' => $heritageShop->id,
            'name' => $heritageShop->shop_name,
            'founder' => $heritageShop->founder_name,
            'lat' => $heritageShop->latitude,
            'lng' => $heritageShop->longitude,
            'participating' => true,
            'published' => true,
            'image' => $this->shopImage($heritageShop),
        ];

        return view('foodPassport.shop', ['shop' => $shop]);
    }

    public function checkIn(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => __('Please sign in before checking in to your heritage passport.'),
            ], 401);
        }

        $data = $request->validate([
            'shop_id' => 'required|integer',
            'user_latitude' => 'required|numeric',
            'user_longitude' => 'required|numeric',
        ]);

        $shop = HeritageShop::query()
            ->whereKey($data['shop_id'])
            ->where(function ($query) {
                $query->whereIn('publish_status', ['approved', 'published', 'Published'])
                    ->orWhereNull('publish_status');
            })
            ->first();

        if (! $shop || $shop->latitude === null || $shop->longitude === null) {
            return response()->json([
                'success' => false,
                'message' => __('This shop is not available for check-in right now.'),
            ], 422);
        }

        $shopId = (int) $shop->id;
        $shopLat = (float) $shop->latitude;
        $shopLng = (float) $shop->longitude;
        $userLat = (float) $data['user_latitude'];
        $userLng = (float) $data['user_longitude'];
        $radius = (float) config('heritage_shop.checkin_radius_meters', 150);

        $withinShopRadius = $this->haversineDistance($userLat, $userLng, $shopLat, $shopLng) <= $radius;

        // Prevent duplicate check-ins
        $already = PassportStamp::where('user_id', $user->id)
            ->where('shop_id', $shopId)
            ->exists();

        if ($already) {
            return response()->json([
                'success' => false,
                'message' => __('You have already checked in at this shop. One passport entry per shop is allowed. Please try another shop or check back later.'),
            ], 409);
        }

        // Accept a valid check-in only when the user is within the actual shop radius.
        if (! $withinShopRadius) {
            return response()->json([
                'success' => false,
                'message' => __('You need to be within :radius metres of this shop to check in.', [
                    'radius' => round($radius),
                ]),
            ], 422);
        }

        $badgesBeforeCheckIn = $this->badgeSummary($user);

        DB::beginTransaction();

        try {
            $stamp = new PassportStamp();
            $stamp->user_id = $user->id;
            $stamp->shop_id = $shopId;
            $stamp->stamp_datetime = Carbon::now();
            $stamp->gps_latitude = $userLat;
            $stamp->gps_longitude = $userLng;
            $stamp->save();

            $this->awardBadgesForUser((int) $user->id);

            DB::commit();
            Cache::forget(self::LEADERBOARD_CACHE_KEY);

            $badgesBeforeLookup = collect($badgesBeforeCheckIn)->keyBy('id');
            $newlyUnlockedBadges = collect($this->badgeSummary($user))
                ->filter(function ($badge) use ($badgesBeforeLookup) {
                    $previousBadge = $badgesBeforeLookup->get($badge['id']);

                    return $badge['earned'] && ! ($previousBadge['earned'] ?? false);
                })
                ->values();

            $message = __('Check-in successful! Your passport has been updated.');

            if ($newlyUnlockedBadges->isNotEmpty()) {
                $message .= ' ' . __('Congratulations! You unlocked :badges.', ['badges' => $newlyUnlockedBadges->pluck('name')->join(', ')]);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'newly_unlocked_badges' => $newlyUnlockedBadges->all(),
                'badge_count' => $this->earnedBadgeCountForUser($user),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Passport check-in failed.', [
                'user_id' => $user->id,
                'shop_id' => $shopId,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('We could not complete your check-in right now. Please try again.'),
            ], 500);
        }
    }

    /**
     * Haversine formula to calculate distance between two GPS coordinates in meters.
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
