<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HeritageFoodItem;
use App\Models\HeritageShop;
use App\Models\ShopImage;
use App\Services\HeritageShopAiGuideService;
use App\Services\HeritageShopImageService;
use App\Services\ShopCrawlerService;
use App\Models\User;


class HeritageShopController extends Controller
{
    public function __construct(
        private HeritageShopImageService $imageService,
        private ShopCrawlerService $crawlerService,
    )
    {
    }

    // Satisfies FR 2.1.1 and FR 2.1.2
    public function index(Request $request)
    {
        $search = mb_substr(trim((string) $request->input('search', '')), 0, 100);
        $category = mb_substr(trim((string) $request->input('category', '')), 0, 100);
        $state = mb_substr(trim((string) $request->input('state', '')), 0, 100);
        $requestedSort = (string) $request->input('sort', 'name_asc');
        $sort = in_array($requestedSort, ['name_asc', 'name_desc', 'newest', 'oldest'], true)
            ? $requestedSort
            : 'name_asc';

        $shopsQuery = HeritageShop::query()
            ->published()
            ->with(['images', 'activeFoodItems'])
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('shop_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('state', 'like', "%{$search}%")
                        ->orWhere('heritage_story', 'like', "%{$search}%")
                        ->orWhere('primary_food_category', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($query) => $query->where('primary_food_category', 'like', "%{$category}%"))
            ->when($state, fn ($query) => $query->where('state', 'like', "%{$state}%"));

        match ($sort) {
            'name_desc' => $shopsQuery->orderByDesc('shop_name'),
            'newest' => $shopsQuery->latest(),
            'oldest' => $shopsQuery->oldest(),
            default => $shopsQuery->orderBy('shop_name'),
        };

        $shops = $shopsQuery->paginate(12)->withQueryString();
        $categories = HeritageShop::query()
            ->published()
            ->whereNotNull('primary_food_category')
            ->where('primary_food_category', '!=', '')
            ->distinct()
            ->orderBy('primary_food_category')
            ->pluck('primary_food_category');
        $states = HeritageShop::query()
            ->published()
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->distinct()
            ->orderBy('state')
            ->pluck('state');

        return view('heritage.shops', compact('shops', 'search', 'category', 'state', 'sort', 'categories', 'states'))
            ->with('imageService', $this->imageService);
    }

    private function allShops(): array
    {
        return [
            [
                'name' => 'Restoran Warisan Selera',
                'location' => 'Kuala Lumpur',
                'category' => 'Traditional Noodles',
                'description' => 'A family-run noodle house serving heirloom recipes passed through three generations.',
                'participating_since' => '2018',
                'highlight' => 'Known for hand-pulled noodles and heritage sambal.',
                'founder' => 'Ahmad Rahman',
                'establishment_year' => '1968',
                'heritage_story' => 'Started by a single-carriage hawker, recipes were preserved and taught within the family.',
                'operating_hours' => 'Tue–Sun 09:00–18:00',
            ],
            [
                'name' => 'Satay House Batu Pahat',
                'location' => 'Johor Bahru',
                'category' => 'Street Food',
                'description' => 'A heritage satay stall that has been serving locals since the 1970s.',
                'participating_since' => '2015',
                'highlight' => 'Signature satay and charcoal-grilled skewers.',
                'founder' => 'Haji Musa',
                'establishment_year' => '1974',
                'heritage_story' => 'A roadside stall that grew a loyal following for its marinade and charcoal technique.',
                'operating_hours' => 'Daily 17:00–23:00',
            ],
            [
                'name' => 'Kampung Desserts Hub',
                'location' => 'Penang',
                'category' => 'Desserts',
                'description' => 'A dessert boutique featuring traditional kuih and syrup recipes from the village.',
                'participating_since' => '2021',
                'highlight' => 'Popular for kuih lapis and gula melaka treats.',
                'founder' => 'Puan Siti',
                'establishment_year' => '1986',
                'heritage_story' => 'Recipes collected from neighbouring kampungs and adapted to a boutique setting.',
                'operating_hours' => 'Wed–Sun 10:00–16:00',
            ],
            [
                'name' => 'Makan Tradisi Kuantan',
                'location' => 'Pahang',
                'category' => 'Rice Dishes',
                'description' => 'A heritage rice shop serving classic dishes and old-fashioned hospitality.',
                'participating_since' => '2017',
                'highlight' => 'Famous for nasi dagang and slow-cooked curries.',
                'founder' => 'Liang & Family',
                'establishment_year' => '1959',
                'heritage_story' => 'A multi-ethnic family-run kitchen that blended recipes across generations.',
                'operating_hours' => 'Mon–Sat 08:00–15:00',
            ],
        ];
    }

    private function searchShops(?string $search = null, ?string $category = null): array
    {
        $shops = $this->allShops();
        $search = mb_strtolower(trim((string) $search));
        $category = mb_strtolower(trim((string) $category));

        return array_values(array_filter($shops, function (array $shop) use ($search, $category): bool {
            $name = mb_strtolower($shop['name']);
            $location = mb_strtolower($shop['location']);
            $description = mb_strtolower($shop['description']);
            $heritageStory = mb_strtolower($shop['heritage_story'] ?? '');
            $shopCategory = mb_strtolower($shop['category']);

            $matchesSearch = $search === ''
                || str_contains($name, $search)
                || str_contains($location, $search)
                || str_contains($description, $search)
                || str_contains($heritageStory, $search)
                || str_contains($shopCategory, $search);

            $matchesCategory = $category === '' || $shopCategory === $category;

            return $matchesSearch && $matchesCategory;
        }));
    }

    public function aiGuide(Request $request, HeritageShop $heritageShop, HeritageShopAiGuideService $aiGuide): \Illuminate\Http\JsonResponse
    {
        abort_unless($heritageShop->isPubliclyVisible(), 404);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        return response()->json($aiGuide->answer($heritageShop, $validated['question']));
    }

    public function foodItem(HeritageShop $heritageShop, HeritageFoodItem $foodItem)
    {
        abort_unless($foodItem->heritage_shop_id === $heritageShop->id, 404);
        abort_unless($heritageShop->isPubliclyVisible() && $foodItem->is_active, 404);

        return view('heritage.food-item', [
            'shop' => $heritageShop->load('images'),
            'foodItem' => $foodItem,
            'imageService' => $this->imageService,
        ]);
    }

    public function foodImage(HeritageShop $heritageShop, HeritageFoodItem $foodItem)
    {
        abort_unless($foodItem->heritage_shop_id === $heritageShop->id, 404);
        abort_unless($heritageShop->isPubliclyVisible() && $foodItem->is_active && filled($foodItem->image_path), 404);

        return $this->imageService->responseForPath($foodItem->image_path, $foodItem->name.'.jpg');
    }

    public function image(HeritageShop $heritageShop, ShopImage $image)
    {
        abort_unless($image->shop_id === $heritageShop->id, 404);
        $currentUser = request()->user();
        
        abort_unless(
            $heritageShop->isPubliclyVisible()
            || ($currentUser instanceof User && $currentUser->isAdmin()),
            404,
        );
        return $this->imageService->response($image);
    }

    // Show a single shop detail by DB id
    public function show(string $id)
    {
        $shop = HeritageShop::query()->published()->with(['images', 'activeFoodItems'])->findOrFail($id);
        $shops = HeritageShop::query()->published()->with(['images', 'activeFoodItems'])->orderBy('shop_name')->get();
        $menuItems = $this->resolveFoodItems($shop);

        $search = '';
        $category = '';
        $state = '';
        $sort = 'name_asc';
        $categories = collect();
        $states = collect();

        return view('heritage.shops', compact('shop', 'shops', 'search', 'category', 'state', 'sort', 'categories', 'states', 'menuItems'))
            ->with('imageService', $this->imageService);
    }

    private function resolveFoodItems(HeritageShop $shop): array
    {
        if ($shop->relationLoaded('activeFoodItems') && $shop->activeFoodItems->isNotEmpty()) {
            return $shop->activeFoodItems->map(fn (HeritageFoodItem $item): array => array_filter([
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'desc' => $item->description,
                'description' => $item->description,
                'category' => $item->category,
                'heritage_significance' => $item->heritage_significance,
                'availability' => $item->availability,
                'image_url' => $item->image_path ? route('heritage-shops.food-images.show', [$shop, $item]) : null,
            ], fn ($value) => filled($value)))->values()->all();
        }

        if (! empty($shop->food_items) && is_array($shop->food_items)) {
            return collect($shop->food_items)
                ->filter(fn ($item) => is_array($item) && filled($item['name'] ?? null) && ($item['is_active'] ?? true) !== false)
                ->map(fn (array $item): array => array_filter([
                    'id' => $item['id'] ?? null,
                    'name' => $item['name'] ?? null,
                    'price' => $item['price'] ?? null,
                    'desc' => $item['desc'] ?? $item['description'] ?? null,
                    'description' => $item['description'] ?? $item['desc'] ?? null,
                    'category' => $item['category'] ?? null,
                    'heritage_significance' => $item['heritage_significance'] ?? null,
                    'availability' => $item['availability'] ?? null,
                    'image_url' => ! empty($item['image_path']) && ! empty($item['id']) ? route('heritage-shops.food-images.show', [$shop, $item['id']]) : null,
                ], fn ($value) => filled($value)))->values()->all();
        }

        if ($shop->source_url) {
            try {
                $crawled = $this->crawlerService->crawl($shop->source_url);
                $crawledMenu = $crawled['food_items'] ?? $crawled['menu'] ?? [];

                if (is_array($crawledMenu) && count($crawledMenu)) {
                    return collect($crawledMenu)
                        ->filter(fn ($item) => is_array($item) && filled($item['name'] ?? null))
                        ->map(fn (array $item): array => [
                            'name' => (string) ($item['name'] ?? 'House special'),
                            'price' => $item['price'] ?? null,
                            'desc' => $item['desc'] ?? $item['description'] ?? null,
                        ])
                        ->values()
                        ->all();
                }
            } catch (\Throwable) {
                // A temporary source failure must not prevent the saved
                // Heritage profile from remaining viewable.
            }
        }

        // Do not invent dishes from a category or shop name. The public page
        // should display menu highlights only when the data was stored or
        // returned by the configured source crawler.
        return [];
    }
}
