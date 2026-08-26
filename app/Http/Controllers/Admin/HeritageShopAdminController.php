<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CrawlHeritageShopRequest;
use App\Http\Requests\StoreHeritageShopRequest;
use App\Models\HeritageShop;
use App\Models\ShopImage;
use App\Services\HeritageShopImageService;
use App\Services\HeritageShopUrlGuard;
use App\Services\ShopCrawlerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Illuminate\View\View;
use RuntimeException;

class HeritageShopAdminController extends Controller
{
    public function __construct(
        private HeritageShopImageService $imageService,
        private HeritageShopUrlGuard $urlGuard,
    )
    {
    }

    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $category = trim($request->string('category')->toString());
        $state = trim($request->string('state')->toString());
        $sort = $request->string('sort')->toString();

        $shopsQuery = HeritageShop::query()
            ->with(['images', 'foodItems'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('shop_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('state', 'like', "%{$search}%")
                        ->orWhere('primary_food_category', 'like', "%{$search}%")
                        ->orWhere('heritage_story', 'like', "%{$search}%");
                });
            })
            ->when($category !== '', fn ($query) => $query->where('primary_food_category', 'like', "%{$category}%"))
            ->when($state !== '', fn ($query) => $query->where('state', 'like', "%{$state}%"));

        match ($sort) {
            'name_desc' => $shopsQuery->orderByDesc('shop_name'),
            'newest' => $shopsQuery->latest(),
            'oldest' => $shopsQuery->oldest(),
            default => $shopsQuery->orderBy('shop_name'),
        };

        $shops = $shopsQuery->paginate(12)->withQueryString();
        $categories = HeritageShop::query()
            ->whereNotNull('primary_food_category')
            ->where('primary_food_category', '!=', '')
            ->distinct()
            ->orderBy('primary_food_category')
            ->pluck('primary_food_category');
        $states = HeritageShop::query()
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->distinct()
            ->orderBy('state')
            ->pluck('state');

        return view('admin.heritage-shops.index', compact('shops', 'categories', 'states', 'search', 'category', 'state', 'sort'))
            ->with('imageService', $this->imageService);
    }

    public function image(HeritageShop $heritageShop, ShopImage $image)
    {
        abort_unless($image->shop_id === $heritageShop->id, 404);

        return $this->imageService->response($image);
    }

    public function create(): View
    {
        return view('admin.heritage-shops.form', [
            'shop' => new HeritageShop(),
            'mode' => 'create',
            'imageService' => $this->imageService,
        ]);
    }

    public function store(StoreHeritageShopRequest $request): RedirectResponse
    {
        $data = $this->shopPayload($request);

        $shop = DB::transaction(function () use ($data, $request): HeritageShop {
            $shop = HeritageShop::query()->create($data);
            $this->attachUploadedImages($shop, $request->file('images', []));
            $this->attachStoredCrawlerImages($shop, $request->input('crawler_images', []));
            $this->syncNormalizedFoodItems($shop, $data['food_items'] ?? []);
            $this->removeRequestedImageIds($shop, $request->input('remove_images', []));
            $this->replaceRequestedImages($shop, $request->file('replace_images', []));

            return $shop;
        });

        return redirect()->route('admin.heritage-shops.edit', $shop)
            ->with('success', 'Heritage shop saved successfully.');
    }

    public function edit(HeritageShop $heritageShop): View
    {
        $heritageShop->load(['images', 'foodItems']);

        return view('admin.heritage-shops.form', [
            'shop' => $heritageShop,
            'mode' => 'edit',
            'imageService' => $this->imageService,
        ]);
    }

    public function update(StoreHeritageShopRequest $request, HeritageShop $heritageShop): RedirectResponse
    {
        $data = $this->shopPayload($request);

        $shop = DB::transaction(function () use ($data, $request, $heritageShop): HeritageShop {
            $heritageShop->fill($data)->save();
            $this->attachUploadedImages($heritageShop, $request->file('images', []));
            $this->attachStoredCrawlerImages($heritageShop, $request->input('crawler_images', []));
            $this->syncNormalizedFoodItems($heritageShop, $data['food_items'] ?? []);
            $this->removeRequestedImageIds($heritageShop, $request->input('remove_images', []));
            $this->replaceRequestedImages($heritageShop, $request->file('replace_images', []));

            return $heritageShop;
        });

        return redirect()->route('admin.heritage-shops.edit', $shop)
            ->with('success', 'Heritage shop updated successfully.');
    }

    public function crawl(CrawlHeritageShopRequest $request, ShopCrawlerService $service): JsonResponse
    {
        try {
            $payload = $service->crawl($request->string('url')->toString());
            $storedImages = [];

            foreach (($payload['images'] ?? []) as $imageUrl) {
                try {
                    $storedImages[] = $this->persistCrawledImage($imageUrl);
                } catch (RuntimeException) {
                    // A page may include SVGs, tracking pixels, or blocked CDN
                    // images. They must not prevent its text fields being used.
                }
            }

            $payload['images'] = $storedImages;

            return response()->json($payload, 200);
        } catch (RuntimeException|\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    private function shopPayload(StoreHeritageShopRequest $request): array
    {
        $validated = $request->validated();
        $menuItems = $request->input('food_items', []);

        if (is_string($menuItems)) {
            $decoded = json_decode($menuItems, true);
            $menuItems = is_array($decoded) ? $decoded : [];
        }

        $operatingHours = $request->input('operating_hours');

        return [
            'shop_name' => $validated['shop_name'] ?? null,
            'primary_food_category' => $validated['primary_food_category'] ?? null,
            'establishment_year' => $validated['establishment_year'] ?? null,
            'founder_name' => $validated['founder_name'] ?? null,
            'founder_background' => $validated['founder_background'] ?? null,
            'current_owner_name' => $validated['current_owner_name'] ?? null,
            'current_owner_details' => $validated['current_owner_details'] ?? null,
            'heritage_story' => $validated['heritage_story'] ?? null,
            'operating_hours' => is_array($operatingHours) ? $operatingHours : ($operatingHours ? ['raw' => trim((string) $operatingHours)] : null),
            'food_items' => $this->normalizeFoodItems($menuItems),
            'contact_number' => $validated['contact_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'source_url' => $validated['source_url'] ?? null,
            'publish_status' => $validated['publish_status'] ?? 'draft',
        ];
    }

    private function normalizeFoodItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn ($item) => is_array($item) && (filled($item['name'] ?? null) || filled($item['price'] ?? null) || filled($item['desc'] ?? null)))
            ->map(fn (array $item): array => array_filter([
                'id' => is_numeric($item['id'] ?? null) ? (int) $item['id'] : null,
                'name' => trim((string) ($item['name'] ?? '')),
                'price' => trim((string) ($item['price'] ?? '')) ?: null,
                'desc' => trim((string) ($item['desc'] ?? $item['description'] ?? '')) ?: null,
                'description' => trim((string) ($item['description'] ?? $item['desc'] ?? '')) ?: null,
                'category' => trim((string) ($item['category'] ?? '')) ?: null,
                'heritage_significance' => trim((string) ($item['heritage_significance'] ?? '')) ?: null,
                'availability' => trim((string) ($item['availability'] ?? '')) ?: null,
                'image_path' => trim((string) ($item['image_path'] ?? '')) ?: null,
                'is_active' => ($item['is_active'] ?? true) !== false,
            ], fn ($value) => filled($value) || $value === false))
            ->values()
            ->all();
    }

    private function syncNormalizedFoodItems(HeritageShop $shop, array $items): void
    {
        if ($items === []) {
            return;
        }

        foreach (array_values($items) as $order => $item) {
            if (! is_array($item) || blank($item['name'] ?? null)) {
                continue;
            }

            $payload = [
                'name' => trim((string) $item['name']),
                'description' => filled($item['description'] ?? null) ? trim((string) $item['description']) : (filled($item['desc'] ?? null) ? trim((string) $item['desc']) : null),
                'category' => filled($item['category'] ?? null) ? trim((string) $item['category']) : null,
                'heritage_significance' => filled($item['heritage_significance'] ?? null) ? trim((string) $item['heritage_significance']) : null,
                'availability' => filled($item['availability'] ?? null) ? trim((string) $item['availability']) : null,
                'price' => filled($item['price'] ?? null) ? trim((string) $item['price']) : null,
                'display_order' => $order,
                'is_active' => ($item['is_active'] ?? true) !== false,
            ];

            $existing = is_numeric($item['id'] ?? null)
                ? $shop->foodItems()->find((int) $item['id'])
                : $shop->foodItems()->where('name', $payload['name'])->orderBy('id')->first();

            if ($existing) {
                $existing->update($payload);
            } else {
                $shop->foodItems()->create($payload);
            }
        }
    }

    private function attachUploadedImages(HeritageShop $shop, array $uploadedFiles): void
    {
        foreach ($uploadedFiles as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $relativePath = $this->imageService->store($file);

            $shop->images()->create([
                'path' => $relativePath,
                'is_primary' => $shop->images()->count() === 0,
            ]);
        }
    }

    private function attachStoredCrawlerImages(HeritageShop $shop, array $storedImagePaths): void
    {
        foreach ($storedImagePaths as $path) {
            if (! is_string($path) || blank($path)) {
                continue;
            }

            if (
                ! str_starts_with($path, $this->imageService->directory().'/crawler/')
                || str_contains($path, '..')
                || ! $this->imageService->exists($path)
            ) {
                continue;
            }

            $shop->images()->create([
                'path' => $path,
                'is_primary' => $shop->images()->count() === 0,
            ]);

        }
    }

    private function replaceRequestedImages(HeritageShop $shop, array $replacements): void
    {
        foreach ($replacements as $imageId => $replacementFile) {
            if (! is_numeric($imageId) || ! $replacementFile instanceof UploadedFile || ! $replacementFile->isValid()) {
                continue;
            }

            $image = $shop->images()->withTrashed()->find((int) $imageId);
            if (! $image) {
                continue;
            }

            $wasPrimary = (bool) $image->is_primary;
            $this->imageService->delete($image->path);
            $image->forceDelete();
            $path = $this->imageService->store($replacementFile);
            $shop->images()->create([
                'path' => $path,
                'is_primary' => $wasPrimary || $shop->images()->count() === 0,
            ]);

        }
    }

    private function removeRequestedImageIds(HeritageShop $shop, array $imageIds): void
    {
        foreach ($imageIds as $imageId) {
            $image = $shop->images()->find((int) $imageId);
            if (! $image) {
                continue;
            }

            $wasPrimary = (bool) $image->is_primary;
            $this->imageService->delete($image->path);
            $image->delete();

            if ($wasPrimary) {
                $shop->images()->where('id', '!=', $image->id)->orderBy('id')->first()?->update(['is_primary' => true]);
            }

        }
    }

    private function persistCrawledImage(string $imageUrl): string
    {
        try {
            $this->urlGuard->assertAllowed($imageUrl);
        } catch (RuntimeException $exception) {
            throw new RuntimeException('The crawler returned an unsafe image URL.', 0, $exception);
        }

        $response = Http::timeout(20)->accept('image/*')->get($imageUrl);
        if ($response->failed()) {
            throw new RuntimeException('The crawler image could not be downloaded.');
        }

        $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $extension = $allowedTypes[$contentType] ?? null;

        if (! $extension) {
            throw new RuntimeException('Crawler image type is unsupported. Use JPG, PNG, or WebP.');
        }

        $body = $response->body();
        if ($body === '' || @getimagesizefromstring($body) === false) {
            throw new RuntimeException('The crawler returned invalid image data.');
        }

        $sizeInKb = $response->header('Content-Length');
        $sizeBytes = is_numeric($sizeInKb) ? ((int) $sizeInKb) : strlen($body);
        if ($sizeBytes > 5 * 1024 * 1024) {
            throw new RuntimeException('Crawler image exceeds the 5MB limit.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'wm-crawler-');
        if ($tempPath === false) {
            throw new RuntimeException('The crawler image temporary file could not be created.');
        }

        if (file_put_contents($tempPath, $body) === false) {
            @unlink($tempPath);
            throw new RuntimeException('The crawler image could not be written temporarily.');
        }

        $uploadedFile = new \Illuminate\Http\File($tempPath);
        try {
            $relativePath = $this->imageService->store($uploadedFile, $this->imageService->directory().'/crawler');
        } finally {
            @unlink($tempPath);
        }

        if ($relativePath === false || blank($relativePath)) {
            throw new RuntimeException('The crawler image could not be stored.');
        }

        return $relativePath;
    }
}
