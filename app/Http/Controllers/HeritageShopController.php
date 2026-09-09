<?php

namespace App\Http\Controllers;

use App\Models\HeritageFoodItem;
use App\Models\HeritageShop;
use App\Models\ShopImage;
use App\Models\User;
use App\Services\HeritageShopAiGuideService;
use App\Services\HeritageShopImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeritageShopController extends Controller
{
    public function __construct(private HeritageShopImageService $imageService) {}

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

        $shops = $shopsQuery->paginate(20)->withQueryString();
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

    public function aiGuide(Request $request, HeritageShop $heritageShop, HeritageShopAiGuideService $aiGuide): JsonResponse
    {
        abort_unless($heritageShop->isPubliclyVisible(), 404);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        return response()->json($aiGuide->answer($heritageShop, $validated['question']));
    }

    public function menu(HeritageShop $heritageShop)
    {
        abort_unless($heritageShop->isPubliclyVisible(), 404);
        $heritageShop->load(['images', 'activeFoodItems']);

        return view('heritage.menu', [
            'shop' => $heritageShop,
            'menuItems' => $this->resolveFoodItems($heritageShop),
            'imageService' => $this->imageService,
        ]);
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

    public function show(string $id)
    {
        $shop = HeritageShop::query()
            ->published()
            ->with(['images', 'activeFoodItems'])
            ->findOrFail($id);
        $menuItems = $this->resolveFoodItems($shop);

        return view('heritage.shops', compact('shop', 'menuItems'))
            ->with('imageService', $this->imageService);
    }

    private function resolveFoodItems(HeritageShop $shop): array
    {
        if (! $shop->relationLoaded('activeFoodItems')) {
            $shop->load('activeFoodItems');
        }

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
}
