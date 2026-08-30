<?php

namespace App\Http\Controllers;

use App\Models\BlindBoxDraw;
use App\Models\HeritageShop;
use App\Services\BlindBoxCatalogManager;
use App\Services\HeritageShopCatalog;
use App\Services\HeritageShopImageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

class BlindBoxController extends Controller
{
    public function __construct(
        private BlindBoxCatalogManager $blindBoxCatalog,
        private HeritageShopCatalog $heritageCatalog,
        private HeritageShopImageService $imageService
    ) {
    }

    private function nowInMalaysia(): Carbon
    {
        return Carbon::now('Asia/Kuala_Lumpur');
    }

    public function index(Request $request)
    {
        $period = $this->currentPeriod();
        $periodInfo = $this->periodInfo($period);

        // Get removed shop IDs (cached)
        $removedIds = Cache::remember('blind_box_removed_ids', 3600, function () {
            $allShops = $this->heritageCatalog->all();
            $removed = [];
            foreach ($allShops as $shop) {
                if ($this->blindBoxCatalog->isRemoved($shop)) {
                    $removed[] = $shop['id'] ?? null;
                }
            }
            return array_filter($removed);
        });

        // Get all active shops (non-removed)
        $allCatalogShops = array_values(array_filter(
            $this->heritageCatalog->all(),
            fn(array $shop): bool => !in_array($shop['id'] ?? null, $removedIds)
        ));

        // --- ENRICH IMAGE URLS (OPTIMISED) ---
        $sourceIds = array_filter(array_column($allCatalogShops, 'source_id'));
        $shopModels = [];
        if (!empty($sourceIds)) {
            $shopModels = HeritageShop::with('images')
                ->whereIn('id', $sourceIds)
                ->get()
                ->keyBy('id');
        }

        $allCatalogShops = array_map(function ($shop) use ($shopModels) {
            if (isset($shop['source_id']) && isset($shopModels[$shop['source_id']])) {
                $model = $shopModels[$shop['source_id']];
                $image = $model->images->first();
                if ($image) {
                    $shop['image'] = $this->imageService->url($image);
                } else {
                    $shop['image'] = null;
                }
            }
            return $shop;
        }, $allCatalogShops);

        // Select up to 6 random shops for the mystery preview
        $randomShops = $allCatalogShops;
        shuffle($randomShops);
        $mysteryShops = array_slice($randomShops, 0, 6);

        // Only category filter is used
        $activeFilters = [
            'category' => trim((string) $request->query('category', '')),
        ];

        // Paginate the active shops
        $perPage = 4;
        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        $shopsCollection = collect($allCatalogShops);
        $paginatedShops = new LengthAwarePaginator(
            $shopsCollection->forPage($currentPage, $perPage)->values(),
            $shopsCollection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $existingDraw = $this->findExistingDraw($request, $period);
        $alreadyDrew = $existingDraw !== null;
        $currentDraw = $existingDraw?->toDrawArray();

        return view('blind-box.index', [
            'shops' => $paginatedShops,
            'totalInCatalog' => count($allCatalogShops),
            'categories' => HeritageShopCatalog::CATEGORIES,
            'activeFilters' => $activeFilters,
            'period' => $period,
            'periodInfo' => $periodInfo,
            'alreadyDrew' => $alreadyDrew,
            'currentDraw' => $currentDraw,
            'mysteryShops' => $mysteryShops,
        ]);
    }

    public function draw(Request $request)
    {
        $userId = $request->user()?->id;

        if (!$userId) {
            return response()->json([
                'error' => __('You must be logged in to open the Blind Box.'),
            ], 401);
        }

        $period = $this->currentPeriod();
        $periodDate = $this->currentPeriodDate();

        if ($this->findExistingDraw($request, $period) !== null) {
            return response()->json([
                'error' => __('You can only draw once per period. Come back next period for another surprise.'),
            ], 429);
        }

        // Only category filter is applied to the blind box draw
        $category = trim((string) $request->query('category', ''));

        // Get active shops from the blind box pool
        $allBlindBoxShops = $this->blindBoxCatalog->activeShops();

        if (empty($allBlindBoxShops)) {
            return response()->json([
                'error' => 'The curated Blind Box pool is currently empty. Our admins are working on it!',
            ], 422);
        }

        // Filter by category if specified
        $shops = array_values(array_filter($allBlindBoxShops, function (array $shop) use ($category): bool {
            return $category === '' || ($shop['category'] ?? '') === $category;
        }));

        if (count($shops) === 0) {
            return response()->json([
                'error' => 'No heritage shops match your current category in our curated pool. Try choosing a different category!',
            ], 422);
        }

        // Pick a random shop
        $indexedShops = array_values($shops);
        $selectedShop = $indexedShops[random_int(0, count($indexedShops) - 1)];

        // The catalog already builds a working image URL for every shop
        // (HeritageShopCatalog::mapShop() -> shopImage()) and includes it
        // under 'image', so just use it directly.
        $imageUrl = $selectedShop['image'] ?? null;

        // --------------------------------------------------------------------
        // Persist the draw in the database, scoped to (user, period, date).
        // The unique constraint on blind_box_draws is the actual enforcement
        // mechanism now - even if two requests race, only one insert wins
        // and the other hits the catch block below as "already drawn".
        // --------------------------------------------------------------------
        try {
            $draw = BlindBoxDraw::create([
                'user_id' => $userId,
                'period' => $period,
                'period_date' => $periodDate,
                'shop_name' => $selectedShop['name'],
                'category' => $selectedShop['category'] ?? null,
                'state' => $selectedShop['state'] ?? null,
                'year' => $selectedShop['year'] ?? null,
                'description' => $selectedShop['description'] ?? null,
                'image' => $imageUrl,
                'address' => $selectedShop['address'] ?? null,
                'shop_source_id' => $selectedShop['source_id'] ?? null,
                'drawn_at' => $this->nowInMalaysia(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Unique constraint violation = a draw already exists for this
            // (user, period, period_date) - most likely a double-submit or
            // a race between two simultaneous requests.
            return response()->json([
                'error' => __('You can only draw once per period. Come back next period for another surprise.'),
            ], 429);
        }

        return response()->json([
            'shop' => $draw->toDrawArray(),
            'period' => $period,
            'period_info' => $this->periodInfo($period),
        ]);
    }

    /**
     * Look up the current period's draw for the authenticated user, if any.
     * Returns null for guests (nothing to restrict/show) or if no draw
     * exists yet for this user in this period.
     */
    private function findExistingDraw(Request $request, string $period): ?BlindBoxDraw
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return null;
        }

        return BlindBoxDraw::query()
            ->where('user_id', $userId)
            ->where('period', $period)
            ->whereDate('period_date', $this->currentPeriodDate())
            ->first();
    }

    private function currentPeriod(): string
    {
        $hour = $this->nowInMalaysia()->hour;
        if ($hour >= 5 && $hour < 12) return 'morning';
        if ($hour >= 12 && $hour < 18) return 'afternoon';
        return 'night';
    }

    /**
     * The calendar date the CURRENT period started on.
     *
     * Morning/afternoon are entirely within one calendar day, so "today"
     * is correct for them. The night period runs 18:00 -> 04:59 and
     * crosses midnight, so between 00:00 and 04:59 the period actually
     * started YESTERDAY at 18:00 - we must use yesterday's date, or a
     * draw made at 23:30 would be treated as a different period than a
     * check made at 00:30, letting the user draw again mid-night.
     */
    private function currentPeriodDate(): Carbon
    {
        $now = $this->nowInMalaysia();

        if ($now->hour < 5) {
            return $now->copy()->subDay()->startOfDay();
        }

        return $now->copy()->startOfDay();
    }

    private function periodInfo(string $period = ''): array
    {
        $period = $period ?: $this->currentPeriod();
        return match ($period) {
            'morning'   => ['key' => 'morning',   'label' => 'Morning',   'tag' => 'Breakfast time', 'icon' => '🌅'],
            'afternoon' => ['key' => 'afternoon', 'label' => 'Afternoon', 'tag' => 'Lunch time',     'icon' => '☀️'],
            default     => ['key' => 'night',     'label' => 'Night',     'tag' => 'Dinner time',    'icon' => '🌙'],
        };
    }
}