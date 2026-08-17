<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\HeritageShop;
use App\Models\PassportStamp;
use App\Models\User;
use App\Models\UserBadge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PassportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $passportData = $this->buildPassportSummary($user);

        return view('foodPassport.index', [
            'shops' => $passportData['shops'],
            'stats' => $passportData['stats'],
            'visitedLocations' => $passportData['visitedLocations'],
            'badges' => $passportData['badges'],
        ]);
    }

    private function buildPassportSummary(?User $user): array
    {
        $shops = $this->getActiveShops();
        $totalShops = count($shops);

        $visitedLocations = [];
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

        foreach ($visitedLocations as $index => $stamp) {
            $shop = $this->resolveShopFromId($stamp->shop_id, $shops);
            $visitedLocations[$index] = [
                'shop_id' => $stamp->shop_id,
                'shop_name' => $shop['name'] ?? 'Heritage Shop',
                'founder' => $shop['founder'] ?? 'Heritage owner',
                'stamped_at' => $stamp->stamp_datetime ? $stamp->stamp_datetime->format('d M Y, H:i') : null,
                'image' => $shop['image'] ?? asset('images/shop1.jpg'),
            ];
        }

        if (empty($visitedLocations) && $user) {
            $visitedLocations = [];
        }

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
            'shops' => $shops,
            'stats' => $stats,
            'visitedLocations' => $visitedLocations,
            'badges' => $badgeList,
        ];
    }

    private function getActiveShops(): array
    {
        if (Schema::hasTable('heritage_shops')) {
            $shops = HeritageShop::query()
                ->when(true, function ($query) {
                    return $query->where(function ($inner) {
                        $inner->where('publish_status', 'published')
                            ->orWhere('publish_status', 'Published')
                            ->orWhereNull('publish_status');
                    });
                })
                ->get();

            if ($shops->isNotEmpty()) {
                return $shops->map(function ($shop) {
                    return [
                        'id' => (int) $shop->id,
                        'name' => $shop->shop_name ?? 'Heritage Shop',
                        'founder' => $shop->founder_name ?? 'Local founder',
                        'lat' => (float) ($shop->latitude ?? 3.139),
                        'lng' => (float) ($shop->longitude ?? 101.6869),
                        'distance' => 'Nearby',
                        'status' => 'Participating',
                        'image' => $this->shopImageForId((int) $shop->id),
                    ];
                })->values()->all();
            }
        }

        return [
            [
                'id' => 1,
                'name' => 'Kedai Kopi Haji',
                'founder' => 'Haji Osman (1965)',
                'lat' => 3.1390,
                'lng' => 101.6869,
                'distance' => '0.8 km away',
                'status' => 'Open today',
                'image' => asset('images/shop1.jpg'),
            ],
            [
                'id' => 2,
                'name' => 'Mee Udang Tok',
                'founder' => 'Aunty Siti (1978)',
                'lat' => 3.1420,
                'lng' => 101.6950,
                'distance' => '1.5 km away',
                'status' => 'Popular this week',
                'image' => asset('images/shop2.jpg'),
            ],
        ];
    }

    private function resolveShopFromId($shopId, array $shops): array
    {
        foreach ($shops as $shop) {
            if ((int) $shop['id'] === (int) $shopId) {
                return $shop;
            }
        }

        return [
            'name' => 'Heritage Shop',
            'founder' => 'Local founder',
            'image' => asset('images/shop1.jpg'),
        ];
    }

    private function shopImageForId(int $shopId): string
    {
        $images = [
            1 => asset('images/shop1.jpg'),
            2 => asset('images/shop2.jpg'),
        ];

        return $images[$shopId] ?? asset('images/shop1.jpg');
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
                'threshold' => 3,
            ],
            [
                'id' => 3,
                'name' => 'Kopitiam Collector',
                'description' => 'Unlock five heritage stops',
                'icon' => '★',
                'threshold' => 5,
            ],
            [
                'id' => 4,
                'name' => 'Night Market Hunter',
                'description' => 'Visit seven iconic food spots',
                'icon' => '✧',
                'threshold' => 7,
            ],
            [
                'id' => 5,
                'name' => 'Heritage Legend',
                'description' => 'Complete ten memorable heritage visits',
                'icon' => '◎',
                'threshold' => 10,
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

        return $records->map(function ($badge) use ($visitedCount, $awardedBadgeIds) {
            $earned = in_array((int) $badge->badge_id, $awardedBadgeIds, true);
            $eligible = $visitedCount >= (int) $badge->criteria_value;

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

    /**
     * Show a module-only shop check-in page. Uses local dummy data until
     * the Heritage Shop module is available.
     */
    public function showShop($id)
    {
        $shops = [
            1 => [
                'id' => 1,
                'name' => 'Kedai Kopi Haji',
                'founder' => 'Haji Osman (1965)',
                'lat' => 3.1390,
                'lng' => 101.6869,
                'participating' => true,
                'published' => true,
                'image' => '/images/shop1.jpg'
            ],
            2 => [
                'id' => 2,
                'name' => 'Mee Udang Tok',
                'founder' => 'Aunty Siti (1978)',
                'lat' => 3.1420,
                'lng' => 101.6950,
                'participating' => true,
                'published' => true,
                'image' => '/images/shop2.jpg'
            ],
        ];

        if (! array_key_exists($id, $shops)) {
            abort(404);
        }

        $shop = (object) $shops[$id];

        return view('foodPassport.shop', ['shop' => $shop]);
    }

    /**
     * Public test check-in that simulates a logged-in user for local development.
     */
    public function checkInTest(Request $request)
    {
        $user = $request->user();

        if (! $user && $request->filled('user_id')) {
            $user = User::find($request->input('user_id'));
        }

        if (! $user) {
            $user = User::firstOrCreate(
                ['email' => 'dev+test@local'],
                ['name' => 'Dev Tester']
            );
        }

        $data = $request->validate([
            'shop_id' => 'required|integer',
            'shop_latitude' => 'required|numeric',
            'shop_longitude' => 'required|numeric',
            'user_latitude' => 'required|numeric',
            'user_longitude' => 'required|numeric',
            'radius_meters' => 'sometimes|numeric',
            'is_participating' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'demo_mode' => 'sometimes|boolean',
            'allow_repeat' => 'sometimes|boolean',
        ]);

        if (array_key_exists('is_participating', $data) && ! $data['is_participating']) {
            return response()->json(['error' => 'Shop is not participating'], 422);
        }

        if (array_key_exists('is_published', $data) && ! $data['is_published']) {
            return response()->json(['error' => 'Shop is not published'], 422);
        }

        $shopId = $data['shop_id'];
        $shopLat = (float) $data['shop_latitude'];
        $shopLng = (float) $data['shop_longitude'];
        $userLat = (float) $data['user_latitude'];
        $userLng = (float) $data['user_longitude'];
        $radius = isset($data['radius_meters']) ? (float) $data['radius_meters'] : 100.0; // default 100m

        $demoMode = (bool) ($data['demo_mode'] ?? false);
        $allowRepeat = (bool) ($data['allow_repeat'] ?? false);

        if (! $demoMode && ! $allowRepeat) {
            $already = PassportStamp::where('user_id', $user->id)
                ->where('shop_id', $shopId)
                ->exists();

            if ($already) {
                return response()->json(['error' => 'User already checked in at this shop'], 409);
            }
        }

        $distance = $this->haversineDistance($userLat, $userLng, $shopLat, $shopLng);

        if ($distance > $radius) {
            return response()->json(['error' => 'User is outside permitted radius', 'distance_m' => $distance], 422);
        }

        DB::beginTransaction();

        try {
            $stamp = new PassportStamp();
            $stamp->user_id = $user->id;
            $stamp->shop_id = $shopId;
            $stamp->stamp_datetime = Carbon::now();
            $stamp->gps_latitude = $userLat;
            $stamp->gps_longitude = $userLng;
            $stamp->save();

            $awarded = $this->awardBadgesForUser((int) $user->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'stamp_id' => $stamp->stamp_id,
                'distance_m' => $distance,
                'awarded_badges' => $awarded,
                'demo_mode' => $demoMode,
                'user_id' => $user->id,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }

    public function resetDemoData(Request $request)
    {
        $user = $request->user();

        if (! $user && $request->filled('user_id')) {
            $user = User::find($request->input('user_id'));
        }

        if (! $user) {
            $user = User::firstOrCreate(
                ['email' => 'dev+test@local'],
                ['name' => 'Dev Tester']
            );
        }

        PassportStamp::where('user_id', $user->id)->delete();
        UserBadge::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Passport demo data reset for this user.',
            'user_id' => $user->id,
        ]);
    }

    public function checkIn(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'shop_id' => 'required|integer',
            'shop_latitude' => 'required|numeric',
            'shop_longitude' => 'required|numeric',
            'user_latitude' => 'required|numeric',
            'user_longitude' => 'required|numeric',
            'radius_meters' => 'sometimes|numeric',
            'is_participating' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
        ]);

        // Basic server-side participation/published checks when front-end supplies flags
        if (array_key_exists('is_participating', $data) && ! $data['is_participating']) {
            return response()->json(['error' => 'Shop is not participating'], 422);
        }

        if (array_key_exists('is_published', $data) && ! $data['is_published']) {
            return response()->json(['error' => 'Shop is not published'], 422);
        }

        $shopId = $data['shop_id'];
        $shopLat = (float) $data['shop_latitude'];
        $shopLng = (float) $data['shop_longitude'];
        $userLat = (float) $data['user_latitude'];
        $userLng = (float) $data['user_longitude'];
        $radius = isset($data['radius_meters']) ? (float) $data['radius_meters'] : 100.0; // default 100m

        // Prevent duplicate check-ins
        $already = PassportStamp::where('user_id', $user->id)
                    ->where('shop_id', $shopId)
                    ->exists();

        if ($already) {
            return response()->json(['error' => 'User already checked in at this shop'], 409);
        }

        // Calculate distance (meters)
        $distance = $this->haversineDistance($userLat, $userLng, $shopLat, $shopLng);

        if ($distance > $radius) {
            return response()->json(['error' => 'User is outside permitted radius', 'distance_m' => $distance], 422);
        }

        DB::beginTransaction();

        try {
            $stamp = new PassportStamp();
            $stamp->user_id = $user->id;
            $stamp->shop_id = $shopId;
            $stamp->stamp_datetime = Carbon::now();
            $stamp->gps_latitude = $userLat;
            $stamp->gps_longitude = $userLng;
            $stamp->save();

            $awarded = $this->awardBadgesForUser((int) $user->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'stamp_id' => $stamp->stamp_id,
                'distance_m' => $distance,
                'awarded_badges' => $awarded,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
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
