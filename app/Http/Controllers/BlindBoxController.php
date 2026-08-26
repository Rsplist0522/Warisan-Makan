<?php

namespace App\Http\Controllers;

use App\Services\BlindBoxCatalogManager;
use App\Services\HeritageShopCatalog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BlindBoxController extends Controller
{
    public function __construct(private BlindBoxCatalogManager $blindBoxCatalog)
    {
    }

    private function nowInMalaysia(): Carbon
    {
        return Carbon::now('Asia/Kuala_Lumpur');
    }

    public function index(Request $request)
    {
        $period = $this->currentPeriod();
        $periodInfo = $this->periodInfo($period);

        $allShops = $this->blindBoxCatalog->activeShops();
        $states = collect($allShops)->pluck('state')->filter()->unique()->sort()->values()->all();

        $activeFilters = [
            'state' => trim((string) $request->query('state', '')),
            'category' => trim((string) $request->query('category', '')),
        ];

        $shops = $this->filterShops($allShops, $activeFilters);

        $alreadyDrew = $this->hasDrawnThisPeriod($request, $period);
        $currentDraw = $request->session()->get("blind_box_draws.{$period}");

        return view('blind-box.index', [
            'shops' => $shops,
            'states' => $states,
            'categories' => HeritageShopCatalog::CATEGORIES,
            'activeFilters' => $activeFilters,
            'period' => $period,
            'periodInfo' => $periodInfo,
            'alreadyDrew' => $alreadyDrew,
            'currentDraw' => $currentDraw,
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

        $activeFilters = [
            'state' => trim((string) $request->query('state', '')),
            'category' => trim((string) $request->query('category', '')),
        ];

        $shops = $this->filterShops($this->blindBoxCatalog->activeShops(), $activeFilters);

        if (count($shops) === 0) {
            return response()->json([
                'error' => __('No heritage shops match your current filters. Try widening your filters and open the box again.'),
            ], 422);
        }

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

        // Session only — no database persistence, per requirement (no history feature).
        $request->session()->put("blind_box_draws.{$period}", array_merge($drawData, [
            'drawn_at' => $this->nowInMalaysia()->toDateTimeString(),
        ]));

        return response()->json([
            'shop' => $shop,
            'period' => $period,
            'period_info' => $this->periodInfo($period),
        ]);
    }

    private function currentPeriod(): string
    {
        $hour = $this->nowInMalaysia()->hour;

        if ($hour >= 5 && $hour < 12) {
            return 'morning';
        }

        if ($hour >= 12 && $hour < 18) {
            return 'afternoon';
        }

        return 'night'; // 18:00 - 04:59, covers evening + late night in one period
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

        if (!is_array($draw)) {
            return false;
        }

        return isset($draw['drawn_at'])
            && Carbon::parse($draw['drawn_at'], 'Asia/Kuala_Lumpur')->isToday();
    }

    private function filterShops(array $shops, array $filters): array
    {
        return array_values(array_filter($shops, function (array $shop) use ($filters): bool {
            return ($filters['state'] === '' || ($shop['state'] ?? '') === $filters['state'])
                && ($filters['category'] === '' || ($shop['category'] ?? '') === $filters['category']);
        }));
    }

}