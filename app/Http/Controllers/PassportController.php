<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\PassportStamp;
use App\Models\Badge;
use App\Models\UserBadge;
use App\Models\User;

class PassportController extends Controller
{
    public function index()
    {
        return view('foodPassport.index');
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
        // Create or get a local test user
        $user = User::firstOrCreate(
            ['email' => 'dev+test@local'],
            ['name' => 'Dev Tester']
        );

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

            // Simple badge evaluation: supports criteria_type = 'visits'
            $awarded = [];

            $badges = Badge::where('is_active', true)->get();

            foreach ($badges as $badge) {
                $has = UserBadge::where('user_id', $user->id)
                        ->where('badge_id', $badge->badge_id)
                        ->exists();

                if ($has) {
                    continue;
                }

                if ($badge->criteria_type === 'visits') {
                    $uniqueVisits = PassportStamp::where('user_id', $user->id)
                                        ->distinct('shop_id')
                                        ->count('shop_id');

                    if ($uniqueVisits >= (int) $badge->criteria_value) {
                        $userBadge = new UserBadge();
                        $userBadge->user_id = $user->id;
                        $userBadge->badge_id = $badge->badge_id;
                        $userBadge->earned_at = Carbon::now();
                        $userBadge->save();
                        $awarded[] = $badge->badge_name;
                    }
                }
            }

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

            // Simple badge evaluation: supports criteria_type = 'visits'
            $awarded = [];

            $badges = Badge::where('is_active', true)->get();

            foreach ($badges as $badge) {
                // skip if already awarded
                $has = UserBadge::where('user_id', $user->id)
                        ->where('badge_id', $badge->badge_id)
                        ->exists();

                if ($has) {
                    continue;
                }

                if ($badge->criteria_type === 'visits') {
                    $uniqueVisits = PassportStamp::where('user_id', $user->id)
                                        ->distinct('shop_id')
                                        ->count('shop_id');

                    if ($uniqueVisits >= (int) $badge->criteria_value) {
                        $userBadge = new UserBadge();
                        $userBadge->user_id = $user->id;
                        $userBadge->badge_id = $badge->badge_id;
                        $userBadge->earned_at = Carbon::now();
                        $userBadge->save();
                        $awarded[] = $badge->badge_name;
                    }
                }
            }

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
