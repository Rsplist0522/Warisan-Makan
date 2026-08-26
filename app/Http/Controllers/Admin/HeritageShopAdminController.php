<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CrawlHeritageShopRequest;
use App\Http\Requests\DiscoverHeritageShopsRequest;
use App\Http\Requests\StoreHeritageShopRequest;
use App\Models\HeritageShop;
use App\Models\PassportStamp;
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

    public function destroy(HeritageShop $heritageShop): RedirectResponse
    {
        if (PassportStamp::query()->where('shop_id', $heritageShop->getKey())->exists()) {
            return back()->with('error', 'This shop cannot be permanently deleted because visitor passport history is linked to it. Set the shop to Archived instead so the history remains valid.');
        }

        DB::transaction(function () use ($heritageShop): void {
            $heritageShop->load(['images', 'foodItems']);

            foreach ($heritageShop->images as $image) {
                $this->imageService->delete($image->path);
            }

            foreach ($heritageShop->foodItems as $foodItem) {
                $this->imageService->delete($foodItem->image_path);
            }

            $heritageShop->delete();
        });

        return redirect()->route('admin.heritage-shops.index')
            ->with('success', 'Heritage shop and its catalog were deleted successfully.');
    }

    public function crawl(CrawlHeritageShopRequest $request, ShopCrawlerService $service): JsonResponse
    {
        $url = $service->normalizeUrl($request->string('url')->toString());
        $existing = $this->findShopBySourceUrl($url, $request->integer('heritage_shop_id'), $service);
        if ($existing) {
            return response()->json([
                'message' => 'This source URL is already linked to “'.$existing->shop_name.'”. Open the existing record instead of importing it again.',
                'existing_shop_id' => $existing->getKey(),
                'existing_shop_url' => route('admin.heritage-shops.edit', $existing),
            ], 409);
        }

        try {
            $payload = $service->crawl($url);
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

    public function discover(DiscoverHeritageShopsRequest $request, ShopCrawlerService $service): JsonResponse
    {
        $url = $service->normalizeUrl($request->string('url')->toString());
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (str_contains($host, 'tripadvisor.')) {
            return response()->json([
                'message' => 'This source is not supported for automated discovery. Use an official, user-authorized, or explicitly permitted list page instead of copying restricted directory content.',
            ], 422);
        }

        try {
            $discovery = $service->discoverFromList(
                $url,
                min($request->integer('limit', 6), (int) config('heritage_shop.max_list_discovery_items', 10)),
            );
            foreach ($discovery['items'] as &$item) {
                $existing = $this->findShopBySourceUrl((string) ($item['source_url'] ?? ''), null, $service);
                $item['duplicate'] = $existing !== null;
                $item['duplicate_shop_name'] = $existing?->shop_name;
                $item['can_import'] = $existing === null;
            }
            unset($item);

            return response()->json($discovery);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'The list page could not be discovered safely.',
            ], 422);
        }
    }

    public function importDiscovered(Request $request, ShopCrawlerService $service): RedirectResponse
    {
        $listUrl = $service->normalizeUrl($request->string('list_url')->toString());
        $listHost = strtolower((string) parse_url($listUrl, PHP_URL_HOST));
        if (! filter_var($listUrl, FILTER_VALIDATE_URL) || $listHost === '' || str_contains($listHost, 'tripadvisor.')) {
            return back()->with('error', 'This import session is invalid or uses a restricted source. Run a fresh preview from an official or explicitly permitted list page.');
        }

        $items = $request->input('items', []);
        if (! is_array($items) || $items === []) {
            return back()->with('error', 'Select at least one reviewed shop before importing.');
        }

        $items = array_slice($items, 0, (int) config('heritage_shop.max_list_discovery_items', 10));
        $created = 0;
        $duplicates = [];
        $invalid = 0;

        DB::transaction(function () use ($items, $service, $listHost, &$created, &$duplicates, &$invalid): void {
            foreach ($items as $item) {
                if (! is_array($item)) {
                    $invalid++;
                    continue;
                }

                $name = trim((string) ($item['shop_name'] ?? $item['name'] ?? ''));
                $rawSourceUrl = trim((string) ($item['source_url'] ?? ''));
                $sourceUrl = $service->normalizeUrl($rawSourceUrl);
                $sourceHost = strtolower((string) parse_url($sourceUrl, PHP_URL_HOST));
                if ($name === '' || ! filter_var($sourceUrl, FILTER_VALIDATE_URL) || $sourceHost !== $listHost) {
                    $invalid++;
                    continue;
                }

                $existing = $this->findShopBySourceUrl($sourceUrl, null, $service);
                if ($existing) {
                    $duplicates[] = $name.' (already linked to '.$existing->shop_name.')';
                    continue;
                }

                $foodItems = $item['food_items'] ?? $item['menu'] ?? [];
                if (is_string($foodItems)) {
                    $decodedFoodItems = json_decode($foodItems, true);
                    $foodItems = is_array($decodedFoodItems) ? $decodedFoodItems : [];
                }
                $shop = HeritageShop::query()->create([
                    'shop_name' => $name,
                    'primary_food_category' => filled($item['primary_food_category'] ?? null) ? trim((string) $item['primary_food_category']) : null,
                    'establishment_year' => is_numeric($item['establishment_year'] ?? null) ? (int) $item['establishment_year'] : null,
                    'heritage_story' => filled($item['heritage_story'] ?? $item['description'] ?? null) ? trim((string) ($item['heritage_story'] ?? $item['description'])) : null,
                    'contact_number' => filled($item['contact_number'] ?? null) ? trim((string) $item['contact_number']) : null,
                    'address' => filled($item['address'] ?? null) ? trim((string) $item['address']) : null,
                    'city' => filled($item['city'] ?? null) ? trim((string) $item['city']) : null,
                    'state' => filled($item['state'] ?? null) ? trim((string) $item['state']) : null,
                    'postal_code' => filled($item['postal_code'] ?? null) ? trim((string) $item['postal_code']) : null,
                    'source_url' => $sourceUrl,
                    'publish_status' => HeritageShop::STATUS_DRAFT,
                    'food_items' => $this->normalizeFoodItems($foodItems),
                ]);
                $this->syncNormalizedFoodItems($shop, $this->normalizeFoodItems($foodItems));
                $created++;
            }
        });

        $message = $created.' shop'.($created === 1 ? '' : 's').' imported as Draft for review.';
        if ($duplicates !== []) {
            $message .= ' Skipped duplicates: '.implode(', ', $duplicates).'.';
        }
        if ($invalid > 0) {
            $message .= ' Skipped '.$invalid.' incomplete result'.($invalid === 1 ? '' : 's').'.';
        }

        return redirect()->route('admin.heritage-shops.index')->with('success', $message);
    }

    private function findShopBySourceUrl(string $url, ?int $ignoreId, ShopCrawlerService $service): ?HeritageShop
    {
        return HeritageShop::query()
            ->whereNotNull('source_url')
            ->get(['id', 'shop_name', 'source_url'])
            ->first(function (HeritageShop $shop) use ($url, $ignoreId, $service): bool {
                return $shop->getKey() !== $ignoreId && $service->normalizeUrl((string) $shop->source_url) === $url;
            });
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

        $sourceUrl = $validated['source_url'] ?? null;
        if (is_string($sourceUrl) && $sourceUrl !== '') {
            $parts = parse_url(trim($sourceUrl));
            if (is_array($parts) && ! empty($parts['host'])) {
                $sourceUrl = strtolower((string) ($parts['scheme'] ?? 'https')).'://'.strtolower((string) $parts['host'])
                    .(isset($parts['port']) ? ':'.(int) $parts['port'] : '')
                    .rtrim((string) ($parts['path'] ?? ''), '/')
                    .(isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '');
            }
        }

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
            'source_url' => $sourceUrl,
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
        if ($sizeBytes > (int) config('heritage_shop.max_image_bytes', 1024 * 1024)) {
            throw new RuntimeException('Crawler image exceeds the '.number_format(((int) config('heritage_shop.max_image_bytes', 1024 * 1024)) / 1024 / 1024, 2).' MB HeritageShop limit.');
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
