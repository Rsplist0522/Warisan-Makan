<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHeritageFoodItemRequest;
use App\Models\HeritageFoodItem;
use App\Models\HeritageShop;
use App\Services\HeritageAuditLogger;
use App\Services\HeritageFoodItemSyncService;
use App\Services\HeritageShopImageService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class HeritageFoodItemAdminController extends Controller
{
    public function __construct(
        private HeritageShopImageService $imageService,
        private HeritageAuditLogger $auditLogger,
        private HeritageFoodItemSyncService $foodItemSync,
    ) {}

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
        $newPath = null;

        try {
            $foodItem = DB::transaction(function () use ($request, $heritageShop, &$newPath): HeritageFoodItem {
                $data = $request->validated();
                $data['heritage_shop_id'] = $heritageShop->id;
                $data['is_active'] = $request->boolean('is_active', true);
                unset($data['image']);

                if ($request->hasFile('image')) {
                    $newPath = $this->imageService->store($request->file('image'), $this->imageService->directory().'/food-items');
                    $data['image_path'] = $newPath;
                }

                $foodItem = $heritageShop->foodItems()->create($data);
                $this->foodItemSync->syncLegacyJson($heritageShop);

                return $foodItem;
            });
        } catch (Throwable $exception) {
            $this->imageService->delete($newPath);
            $this->rethrowDuplicate($exception);
        }

        $this->auditLogger->record($request->user(), $foodItem, 'heritage_food_item.created', [], $foodItem->getAttributes());

        return back()->with('success', 'Food item added to the HeritageShop menu.');
    }

    public function update(StoreHeritageFoodItemRequest $request, HeritageShop $heritageShop, HeritageFoodItem $foodItem): RedirectResponse
    {
        abort_unless($foodItem->heritage_shop_id === $heritageShop->id, 404);

        $oldValues = $foodItem->getAttributes();
        $oldPath = $foodItem->image_path;
        $newPath = null;

        try {
            DB::transaction(function () use ($request, $heritageShop, $foodItem, &$newPath): void {
                $data = $request->validated();
                $data['is_active'] = $request->boolean('is_active', false);
                unset($data['image']);

                if ($request->hasFile('image')) {
                    $newPath = $this->imageService->store($request->file('image'), $this->imageService->directory().'/food-items');
                    $data['image_path'] = $newPath;
                }

                $foodItem->update($data);
                $this->foodItemSync->syncLegacyJson($heritageShop);
            });
        } catch (Throwable $exception) {
            $this->imageService->delete($newPath);
            $this->rethrowDuplicate($exception);
        }

        if ($newPath !== null) {
            $this->imageService->delete($oldPath);
        }
        $this->auditLogger->record($request->user(), $foodItem->fresh(), 'heritage_food_item.updated', $oldValues, $foodItem->fresh()->getAttributes());

        return back()->with('success', 'Food item updated successfully.');
    }

    public function toggle(Request $request, HeritageShop $heritageShop, HeritageFoodItem $foodItem): RedirectResponse
    {
        abort_unless($foodItem->heritage_shop_id === $heritageShop->id, 404);

        $oldValues = $foodItem->getAttributes();
        DB::transaction(function () use ($heritageShop, $foodItem): void {
            $foodItem->update(['is_active' => ! $foodItem->is_active]);
            $this->foodItemSync->syncLegacyJson($heritageShop);
        });
        $this->auditLogger->record($request->user(), $foodItem, 'heritage_food_item.updated', $oldValues, $foodItem->getAttributes());

        return back()->with('success', $foodItem->is_active ? 'Food item is now visible to visitors.' : 'Food item is now hidden from visitors.');
    }

    public function destroy(Request $request, HeritageShop $heritageShop, HeritageFoodItem $foodItem): RedirectResponse
    {
        abort_unless($foodItem->heritage_shop_id === $heritageShop->id, 404);

        $oldValues = $foodItem->getAttributes();
        $oldPath = $foodItem->image_path;
        DB::transaction(function () use ($heritageShop, $foodItem): void {
            $foodItem->delete();
            $this->foodItemSync->syncLegacyJson($heritageShop);
        });
        $this->imageService->delete($oldPath);
        $this->auditLogger->record($request->user(), $foodItem, 'heritage_food_item.deleted', $oldValues, []);

        return back()->with('success', 'Food item deleted from the menu successfully.');
    }

    private function rethrowDuplicate(Throwable $exception): never
    {
        if ($exception instanceof QueryException) {
            $message = strtolower($exception->getMessage());
            if (str_contains($message, 'heritage_food_shop_normalized_name_unique')
                || str_contains($message, 'heritage_food_items.heritage_shop_id, heritage_food_items.normalized_name')) {
                throw ValidationException::withMessages([
                    'name' => 'A food item with the same name already exists for this shop.',
                ]);
            }
        }

        throw $exception;
    }
}
