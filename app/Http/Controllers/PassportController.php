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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PassportController extends Controller
{
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
        $shops = $this->getActiveShops();
        $totalShops = count($shops);

        $shopsPage = LengthAwarePaginator::resolveCurrentPage('shops_page');
        $availableShops = new LengthAwarePaginator(
            collect($shops)->forPage($shopsPage, 3)->values()->all(),
            $totalShops,
            3,
            $shopsPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'shops_page']
        );
        $shopsForView = $availableShops->items();

        $visitedLocations = collect();
        $visitedCount = 0;

        if ($user) {
            $stamps = PassportStamp::where('user_id', $user->id)
                ->orderByDesc('stamp_datetime')
                ->get();

            $uniqueByShop = [];
            foreach ($stamps as $stamp) {
                if (! isset($uniqueByShop[$stamp->shop_id])) {
                    $uniqueByShop[$stamp->shop_id] = $stamp;
                }
            }

            $visitedLocations = array_values($uniqueByShop);
            $visitedCount = count($uniqueByShop);
        }

        $visitedShopIds = collect($visitedLocations)->pluck('shop_id')->all();
        $visitedShopLookup = HeritageShop::query()
            ->whereIn('id', $visitedShopIds)
            ->with('images')
            ->get()
            ->keyBy('id');

        $visitedLocations = collect($visitedLocations)->map(function ($stamp) use ($visitedShopLookup) {
            $shop = $visitedShopLookup->get($stamp->shop_id);

            return [
                'shop_id' => $stamp->shop_id,
                'shop_name' => $shop ? $shop->shop_name : 'Heritage shop #' . $stamp->shop_id,
                'founder' => $shop ? ($shop->founder_name ?? 'Local founder') : 'Shop details unavailable',
                'stamped_at' => $stamp->stamp_datetime ? $stamp->stamp_datetime->format('d M Y, H:i') : null,
                'image' => $shop ? $this->shopImage($shop) : null,
            ];
        })->values();

        $currentPage = LengthAwarePaginator::resolveCurrentPage('visited_page');
        $visitedLocations = new LengthAwarePaginator(
            $visitedLocations->forPage($currentPage, 5)->values(),
            $visitedLocations->count(),
            5,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'visited_page']
        );

        $completion = $totalShops > 0 ? (int) round(($visitedCount / $totalShops) * 100) : 0;
        $badgeList = $this->badgeSummary($user);

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
        $emptyLeaderboard = collect();

        if (! Schema::hasTable('users') || ! Schema::hasTable('passport_stamps')) {
            return new LengthAwarePaginator(
                $emptyLeaderboard,
                0,
                5,
                $currentPage,
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'leaderboard_page']
            );
        }

        $userTable = (new User())->getTable();
        $stampTable = (new PassportStamp())->getTable();

        $rows = User::query()
            ->select(["{$userTable}.id", "{$userTable}.name"])
            ->selectSub(
                PassportStamp::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn("{$stampTable}.user_id", "{$userTable}.id"),
                'check_ins'
            )
            ->selectSub(
                PassportStamp::query()
                    ->selectRaw('MAX(stamp_datetime)')
                    ->whereColumn("{$stampTable}.user_id", "{$userTable}.id"),
                'last_check_in_at'
            )
            ->whereExists(function ($subquery) use ($userTable, $stampTable) {
                $subquery->selectRaw('1')
                    ->from($stampTable)
                    ->whereColumn("{$stampTable}.user_id", "{$userTable}.id");
            })
            ->get()
            ->map(function ($row) {
                $leaderboardUser = User::find((int) $row->id);
                $row->badges_received = $leaderboardUser
                    ? $this->earnedBadgeCountForUser($leaderboardUser)
                    : 0;
                $row->check_ins = (int) $row->check_ins;
                $row->last_check_in_label = $row->last_check_in_at
                    ? Carbon::parse($row->last_check_in_at)->format('d M Y, H:i')
                    : 'No check-in yet';

                return $row;
            })
            ->sort(function ($first, $second) {
                if ($first->badges_received !== $second->badges_received) {
                    return $second->badges_received <=> $first->badges_received;
                }

                if ($first->check_ins !== $second->check_ins) {
                    return $second->check_ins <=> $first->check_ins;
                }

                return strcmp((string) $second->last_check_in_at, (string) $first->last_check_in_at);
            })
            ->take(10)
            ->values()
            ->map(function ($row, $index) {
                $row->rank = $index + 1;

                return $row;
            });

        return new LengthAwarePaginator(
            $rows->forPage($currentPage, 5)->values(),
            $rows->count(),
            5,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'leaderboard_page']
        );
    }

    private function getActiveShops(): array
    {
        if (Schema::hasTable('heritage_shops')) {
            $shops = HeritageShop::query()
                ->when(true, function ($query) {
                    return $query->where(function ($inner) {
                        $inner->whereIn('publish_status', ['approved', 'published', 'Published'])
                            ->orWhere('publish_status', 'Published')
                            ->orWhereNull('publish_status');
                    });
                })
                    ->whereNotNull('latitude')
                        ->whereNotNull('longitude')
                ->with('images')
                ->orderBy('shop_name')
                ->get();

            return $shops->map(function ($shop) {
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
            })->values()->all();
        }

        return [];
    }

    private function shopImage(HeritageShop $shop): ?string
    {
        $image = $shop->images->first();

        return $image ? $this->imageService->url($image) : null;
    }

    private function badgeSummary(?User $user): array
    {
        $fallback = [
            [
                'id' => 1,
                'name' => 'Heritage Starter',
                'description' => 'Visit your first heritage shop',
                'icon' => '✦',
                'threshold' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Trail Explorer',
                'description' => 'Check in to three heritage shops',
                'icon' => '▣',
                'threshold' => 8,
            ],
            [
                'id' => 3,
                'name' => 'Kopitiam Collector',
                'description' => 'Unlock five heritage stops',
                'icon' => '★',
                'threshold' => 15,
            ],
            [
                'id' => 4,
                'name' => 'Night Market Hunter',
                'description' => 'Visit seven iconic food spots',
                'icon' => '✧',
                'threshold' => 25,
            ],
            [
                'id' => 5,
                'name' => 'Heritage Legend',
                'description' => 'Complete ten memorable heritage visits',
                'icon' => '◎',
                'threshold' => 40,
            ],
            [
                'id' => 6,
                'name' => 'Heritage Ambassador',
                'description' => 'Complete fifty heritage visits and help keep local food stories alive',
                'icon' => '♛',
                'threshold' => 50,
            ],
        ];

        if (! Schema::hasTable('badges') || ! Schema::hasTable('user_badges')) {
            $visitedCount = $user ? PassportStamp::where('user_id', $user->id)->distinct('shop_id')->count('shop_id') : 0;

            return array_map(function ($badge) use ($visitedCount) {
                $earned = $visitedCount >= (int) $badge['threshold'];

                return [
                    'id' => (int) $badge['id'],
                    'name' => $badge['name'],
                    'description' => $badge['description'],
                    'icon' => $badge['icon'],
                    'threshold' => (int) $badge['threshold'],
                    'earned' => $earned,
                    'eligible' => $visitedCount >= (int) $badge['threshold'],
                    'progress' => min($visitedCount, (int) $badge['threshold']) . '/' . (int) $badge['threshold'],
                ];
            }, $fallback);
        }

        $records = Badge::query()
            ->where('is_active', true)
            ->orderBy('criteria_value')
            ->get();

        if ($records->isEmpty()) {
            $visitedCount = $user ? PassportStamp::where('user_id', $user->id)->distinct('shop_id')->count('shop_id') : 0;

            return array_map(function ($badge) use ($visitedCount) {
                $earned = $visitedCount >= (int) $badge['threshold'];

                return [
                    'id' => (int) $badge['id'],
                    'name' => $badge['name'],
                    'description' => $badge['description'],
                    'icon' => $badge['icon'],
                    'threshold' => (int) $badge['threshold'],
                    'earned' => $earned,
                    'eligible' => $earned,
                    'progress' => min($visitedCount, (int) $badge['threshold']) . '/' . (int) $badge['threshold'],
                ];
            }, $fallback);
        }

        $visitedCount = $user ? PassportStamp::where('user_id', $user->id)->distinct('shop_id')->count('shop_id') : 0;
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

        $hasHeritageAmbassador = collect($badgeList)->contains(
            fn ($badge) => $badge['id'] === 6 || $badge['name'] === 'Heritage Ambassador'
        );

        if (! $hasHeritageAmbassador) {
            $ambassador = $fallback[5];
            $earned = $visitedCount >= (int) $ambassador['threshold'];
            $badgeList[] = [
                'id' => (int) $ambassador['id'],
                'name' => $ambassador['name'],
                'description' => $ambassador['description'],
                'icon' => $ambassador['icon'],
                'threshold' => (int) $ambassador['threshold'],
                'earned' => $earned,
                'eligible' => $earned,
                'progress' => min($visitedCount, (int) $ambassador['threshold']) . '/' . (int) $ambassador['threshold'],
            ];
        }

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

        foreach ($badgeRecords as $badge) {
            $alreadyAwarded = UserBadge::where('user_id', $userId)
                ->where('badge_id', $badge->badge_id)
                ->exists();

            if ($alreadyAwarded) {
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

                    $awarded[] = $badge->badge_name;
                }
            }
        }

        return $awarded;
    }

    public function showShop($id)
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
                'message' => 'Please sign in before checking in to your heritage passport.',
            ], 401);
        }

        $data = $request->validate([
            'shop_id' => 'required|integer',
            'user_latitude' => 'required|numeric',
            'user_longitude' => 'required|numeric',
            'demo_mode' => 'sometimes|boolean',
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
                'message' => 'This shop is not available for check-in right now.',
            ], 422);
        }

        $shopId = (int) $shop->id;
        $shopLat = (float) $shop->latitude;
        $shopLng = (float) $shop->longitude;
        $userLat = (float) $data['user_latitude'];
        $userLng = (float) $data['user_longitude'];
        $radius = 100.0;
        $demoMode = (bool) ($data['demo_mode'] ?? false);

        // Prevent duplicate check-ins
        $already = PassportStamp::where('user_id', $user->id)
            ->where('shop_id', $shopId)
            ->exists();

        if ($already) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked in at this shop. Reset the demo to start again.',
            ], 409);
        }

        // Calculate distance (meters)
        $distance = $this->haversineDistance($userLat, $userLng, $shopLat, $shopLng);

        if ($distance > $radius) {
            return response()->json([
                'success' => false,
                'message' => 'You need to be within ' . round($radius) . ' metres of this shop to check in.',
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

            $badgesBeforeLookup = collect($badgesBeforeCheckIn)->keyBy('id');
            $newlyUnlockedBadges = collect($this->badgeSummary($user))
                ->filter(function ($badge) use ($badgesBeforeLookup) {
                    $previousBadge = $badgesBeforeLookup->get($badge['id']);

                    return $badge['earned'] && ! ($previousBadge['earned'] ?? false);
                })
                ->values();

            $message = $demoMode
                ? 'Demo check-in successful! Your passport has been updated.'
                : 'Check-in successful! Your passport has been updated.';

            if ($newlyUnlockedBadges->isNotEmpty()) {
                $message .= ' Congratulations! You unlocked ' . $newlyUnlockedBadges->pluck('name')->join(', ') . '.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'demo_mode' => $demoMode,
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
                'message' => 'We could not complete your check-in right now. Please try again.',
            ], 500);
        }
    }

    public function resetDemoData(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please sign in before resetting the demo passport.',
            ], 401);
        }

        PassportStamp::where('user_id', $user->id)->delete();
        UserBadge::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Demo passport reset. You can start the demonstration again.',
        ]);
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
