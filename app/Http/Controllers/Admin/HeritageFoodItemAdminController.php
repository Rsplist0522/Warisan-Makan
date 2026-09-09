<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHeritageFoodItemRequest;
use App\Models\HeritageFoodItem;
use App\Models\HeritageShop;
use App\Services\HeritageShopImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HeritageFoodItemAdminController extends Controller
{
    public function __construct(private HeritageShopImageService $imageService)
    {
    }

    public function image(HeritageShop $heritageShop, HeritageFoodItem $foodItem)
    {
        abort_unless($foodItem->heritage_shop_id === $heritageShop->id && filled($foodItem->image_path), 404);

        return $this->imageService->responseForPath($foodItem->image_path, $foodItem->name.'.jpg');
    }

    public function index(HeritageShop $heritageShop): View
    {
        $heritageShop->load(['foodItems', 'images']);

        return view('admin.heritage-food-items.index', [
            'shop' => $heritageShop,
            'foodItems' => $heritageShop->foodItems,
            'imageService' => $this->imageService,
        ]);
    }

    public function store(StoreHeritageFoodItemRequest $request, HeritageShop $heritageShop): RedirectResponse
    {
        DB::transaction(function () use ($request, $heritageShop): void {
            $data = $request->validated();
            $data['heritage_shop_id'] = $heritageShop->id;
            $data['is_active'] = $request->boolean('is_active', true);
            unset($data['image']);

            if ($request->hasFile('image')) {
                $data['image_path'] = $this->imageService->store($request->file('image'), $this->imageService->directory().'/food-items');
            }

            $heritageShop->foodItems()->create($data);
            $this->syncLegacyJson($heritageShop);
        });

        return back()->with('success', 'Food item added to the HeritageShop menu.');
    }

    public function update(StoreHeritageFoodItemRequest $request, HeritageShop $heritageShop, HeritageFoodItem $foodItem): RedirectResponse
    {
        abort_unless($foodItem->heritage_shop_id === $heritageShop->id, 404);

        DB::transaction(function () use ($request, $heritageShop, $foodItem): void {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', false);
            unset($data['image']);

            if ($request->hasFile('image')) {
                $this->imageService->delete($foodItem->image_path);
                $data['image_path'] = $this->imageService->store($request->file('image'), $this->imageService->directory().'/food-items');
            }

            $foodItem->update($data);
            $this->syncLegacyJson($heritageShop);
        });

        return back()->with('success', 'Food item updated successfully.');
    }

    public function toggle(HeritageShop $heritageShop, HeritageFoodItem $foodItem): RedirectResponse
    {
        abort_unless($foodItem->heritage_shop_id === $heritageShop->id, 404);

        $foodItem->update(['is_active' => ! $foodItem->is_active]);
        $this->syncLegacyJson($heritageShop);

        return back()->with('success', $foodItem->is_active ? 'Food item is now visible to visitors.' : 'Food item is now hidden from visitors.');
    }

    public function destroy(HeritageShop $heritageShop, HeritageFoodItem $foodItem): RedirectResponse
    {
        abort_unless($foodItem->heritage_shop_id === $heritageShop->id, 404);

        DB::transaction(function () use ($heritageShop, $foodItem): void {
            $this->imageService->delete($foodItem->image_path);
            $foodItem->delete();
            $this->syncLegacyJson($heritageShop);
        });

        return back()->with('success', 'Food item deleted from the menu successfully.');
    }

    private function syncLegacyJson(HeritageShop $heritageShop): void
    {
        $heritageShop->setAttribute('food_items', $heritageShop->foodItems()->get()->map(fn (HeritageFoodItem $item): array => array_filter([
            'id' => $item->id,
            'name' => $item->name,
            'price' => $item->price,
            'desc' => $item->description,
            'description' => $item->description,
            'category' => $item->category,
            'heritage_significance' => $item->heritage_significance,
            'availability' => $item->availability,
            'image_path' => $item->image_path,
            'is_active' => $item->is_active,
        ], fn ($value) => filled($value) || $value === false))->values()->all());
        $heritageShop->save();
    }
}
