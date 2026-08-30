<?php

namespace App\Http\Controllers;

use App\Services\BlindBoxCatalogManager;
use App\Services\HeritageShopCatalog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

class BlindBoxController extends Controller
{
    public function __construct(
        private BlindBoxCatalogManager $blindBoxCatalog,
        private HeritageShopCatalog $heritageCatalog
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

        // Get removed shop IDs (cached for 1 hour to reduce repeated checks)
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

        // Select up to 6 random shops for the mystery preview
        $randomShops = $allCatalogShops;
        shuffle($randomShops);
        $mysteryShops = array_slice($randomShops, 0, 6);

        // Only category filter is used (for the dropdown selected state and blind box draw)
        $activeFilters = [
            'category' => trim((string) $request->query('category', '')),
        ];

        // Paginate the active shops (in memory, but only active shops)
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

        $alreadyDrew = $this->hasDrawnThisPeriod($request, $period);
        $currentDraw = $request->session()->get("blind_box_draws.{$period}");

        return view('blind-box.index', [
            'shops' => $paginatedShops,
            'totalInCatalog' => count($allCatalogShops),
            'categories' => HeritageShopCatalog::CATEGORIES,
            'activeFilters' => $activeFilters,
            'period' => $period,
            'periodInfo' => $periodInfo,
            'alreadyDrew' => $alreadyDrew,
            'currentDraw' => $currentDraw,
            'mysteryShops' => $mysteryShops, // Pass random shops for the preview
        ]);
    }

    public function draw(Request $request)
    {
        $period = $this->currentPeriod();

        if ($this->hasDrawnThisPeriod($request, $period)) {
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
        $shop = $indexedShops[random_int(0, count($indexedShops) - 1)];

        $drawData = [
            'period' => $period,
            'shop_name' => $shop['name'],
            'category' => $shop['category'] ?? null,
            'state' => $shop['state'] ?? null,
            'year' => $shop['year'] ?? null,
            'description' => $shop['description'] ?? null,
            'image' => $shop['image'] ?? null,
            'address' => $shop['address'] ?? null,
        ];

        $request->session()->put("blind_box_draws.{$period}", array_merge($drawData, [
            'drawn_at' => $this->nowInMalaysia()->toDateTimeString(),
        ]));

        return response()->json([
            'shop' => $drawData,
            'period' => $period,
            'period_info' => $this->periodInfo($period),
        ]);
    }

    private function currentPeriod(): string
    {
        $hour = $this->nowInMalaysia()->hour;
        if ($hour >= 5 && $hour < 12) return 'morning';
        if ($hour >= 12 && $hour < 18) return 'afternoon';
        return 'night';
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

    private function hasDrawnThisPeriod(Request $request, string $period): bool
    {
        $draw = $request->session()->get("blind_box_draws.{$period}");
        if (!is_array($draw)) return false;
        return isset($draw['drawn_at']) && Carbon::parse($draw['drawn_at'], 'Asia/Kuala_Lumpur')->isToday();
    }
}