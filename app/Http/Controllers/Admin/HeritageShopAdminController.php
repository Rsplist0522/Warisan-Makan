<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CrawlHeritageShopRequest;
use App\Http\Requests\DiscoverHeritageShopsRequest;
use App\Http\Requests\ImportDiscoveredHeritageShopsRequest;
use App\Http\Requests\StoreHeritageShopRequest;
use App\Models\HeritageFoodItem;
use App\Models\HeritageShop;
use App\Models\PassportStamp;
use App\Models\ShopImage;
use App\Services\HeritageAuditLogger;
use App\Services\HeritageFoodItemSyncService;
use App\Services\HeritageShopImageService;
use App\Services\HeritageShopUrlGuard;
use App\Services\HeritageShopValidationRules;
use App\Services\ShopCrawlerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class HeritageShopAdminController extends Controller
{
    public function __construct(
        private HeritageShopImageService $imageService,
        private HeritageShopUrlGuard $urlGuard,
        private HeritageAuditLogger $auditLogger,
        private HeritageFoodItemSyncService $foodItemSync,
    ) {}

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
            'shop' => new HeritageShop,
            'mode' => 'create',
            'imageService' => $this->imageService,
        ]);
    }

    public function store(StoreHeritageShopRequest $request): RedirectResponse
    {
        $data = $this->shopPayload($request);
        $newPaths = [];

        try {
            $shop = DB::transaction(function () use ($data, $request, &$newPaths): HeritageShop {
                $shop = HeritageShop::query()->create($data);
                $this->attachUploadedImages($shop, $request->file('images', []), $newPaths);
                $this->attachStoredCrawlerImages($shop, $request->input('crawler_images', []));
                if (array_key_exists('food_items', $data)) {
                    $this->foodItemSync->syncQuickItems($shop, $data['food_items']);
                }

                return $shop;
            });
        } catch (Throwable $exception) {
            $this->deleteImagePaths($newPaths);
            $this->rethrowIntegrityViolation($exception);
        }

        $this->auditLogger->record($request->user(), $shop, 'heritage_shop.created', [], $shop->getAttributes());
        if ($shop->publish_status === HeritageShop::STATUS_PUBLISHED) {
            $this->auditLogger->record($request->user(), $shop, 'heritage_shop.published', [], $shop->getAttributes());
        }

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
        $newPaths = [];
        $oldPaths = [];
        $oldValues = [];

        try {
            $shop = DB::transaction(function () use ($data, $request, $heritageShop, &$newPaths, &$oldPaths, &$oldValues): HeritageShop {
                $lockedShop = HeritageShop::query()->lockForUpdate()->findOrFail($heritageShop->getKey());
                if ((int) $request->validated('version') !== (int) $lockedShop->version) {
                    throw ValidationException::withMessages([
                        'version' => 'This shop was updated by another administrator. Review the latest version before saving your changes.',
                    ]);
                }

                $oldValues = $lockedShop->getAttributes();
                $lockedShop->fill($data)->save();
                $this->attachUploadedImages($lockedShop, $request->file('images', []), $newPaths);
                $this->attachStoredCrawlerImages($lockedShop, $request->input('crawler_images', []));
                if (array_key_exists('food_items', $data)) {
                    $this->foodItemSync->syncQuickItems($lockedShop, $data['food_items']);
                }
                $this->removeRequestedImageIds($lockedShop, $request->input('remove_images', []), $oldPaths);
                $this->replaceRequestedImages($lockedShop, $request->file('replace_images', []), $newPaths, $oldPaths);

                return $lockedShop->fresh();
            });
        } catch (Throwable $exception) {
            $this->deleteImagePaths($newPaths);
            $this->rethrowIntegrityViolation($exception);
        }

        $this->deleteImagePaths($oldPaths);
        $this->auditLogger->record($request->user(), $shop, 'heritage_shop.updated', $oldValues, $shop->getAttributes());
        $this->recordPublicationTransition($request, $shop, $oldValues);

        return redirect()->route('admin.heritage-shops.edit', $shop)
            ->with('success', 'Heritage shop updated successfully.');
    }

    public function destroy(Request $request, HeritageShop $heritageShop): RedirectResponse
    {
        if (PassportStamp::query()->where('shop_id', $heritageShop->getKey())->exists()) {
            return back()->with('error', 'This shop cannot be permanently deleted because visitor passport history is linked to it. Set the shop to Archived instead so the history remains valid.');
        }

        $oldValues = $heritageShop->getAttributes();
        $paths = DB::transaction(function () use ($heritageShop): array {
            $paths = $heritageShop->images()->withTrashed()->pluck('path')
                ->merge($heritageShop->foodItems()->withTrashed()->pluck('image_path'))
                ->filter()
                ->all();

            $heritageShop->delete();

            return $paths;
        });
        $this->deleteImagePaths($paths);
        $this->auditLogger->record($request->user(), $heritageShop, 'heritage_shop.deleted', $oldValues, []);

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
        } catch (RuntimeException|Throwable $exception) {
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
        } catch (Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'The list page could not be discovered safely.',
            ], 422);
        }
    }

    public function importDiscovered(ImportDiscoveredHeritageShopsRequest $request, ShopCrawlerService $service): RedirectResponse
    {
        $listUrl = $service->normalizeUrl($request->string('list_url')->toString());
        $listHost = strtolower((string) parse_url($listUrl, PHP_URL_HOST));
        try {
            $this->urlGuard->assertAllowed($listUrl);
        } catch (RuntimeException) {
            return back()->withInput()->with('error', 'This import session is invalid or uses a restricted source. Run a fresh preview from an official or explicitly permitted list page.');
        }

        if ($listHost === '' || str_contains($listHost, 'tripadvisor.')) {
            return back()->with('error', 'This import session is invalid or uses a restricted source. Run a fresh preview from an official or explicitly permitted list page.');
        }

        $items = $request->validated('items');
        $created = 0;
        $duplicates = [];
        $invalid = 0;

        foreach ($items as $item) {
            $candidate = $this->normalizeDiscoveredItem($item, $service);
            $name = $candidate['shop_name'] ?? 'Unnamed result';
            $sourceUrl = (string) ($candidate['source_url'] ?? '');
            $sourceHost = strtolower((string) parse_url($sourceUrl, PHP_URL_HOST));

            if ($sourceHost !== $listHost) {
                $invalid++;

                continue;
            }

            try {
                $this->urlGuard->assertAllowed($sourceUrl);
            } catch (RuntimeException) {
                $invalid++;

                continue;
            }

            $existing = $this->findShopBySourceUrl($sourceUrl, null, $service);
            if ($existing) {
                $duplicates[] = $name.' (already linked to '.$existing->shop_name.')';

                continue;
            }

            $validator = Validator::make($candidate, HeritageShopValidationRules::fields(null, true));
            $validator->after(function ($validator) use ($candidate): void {
                $key = HeritageShop::duplicateKeyFor(
                    $candidate['shop_name'] ?? null,
                    $candidate['address'] ?? null,
                    $candidate['city'] ?? null,
                    $candidate['state'] ?? null,
                );
                if (HeritageShop::query()->where('duplicate_key', $key)->exists()) {
                    $validator->errors()->add('shop_name', 'A heritage shop with the same name and location already exists.');
                }

                $seenNames = [];
                foreach ((array) ($candidate['food_items'] ?? []) as $index => $foodItem) {
                    if (! is_array($foodItem)) {
                        continue;
                    }

                    if (blank($foodItem['name'] ?? null)) {
                        if (filled($foodItem['price'] ?? null) || filled($foodItem['desc'] ?? null) || filled($foodItem['description'] ?? null)) {
                            $validator->errors()->add("food_items.{$index}.name", 'The food item name is required when other item details are provided.');
                        }

                        continue;
                    }

                    $normalizedName = HeritageFoodItem::normalizeName($foodItem['name']);
                    if (isset($seenNames[$normalizedName])) {
                        $validator->errors()->add("food_items.{$index}.name", 'A food item with the same name already exists for this shop.');
                    }
                    $seenNames[$normalizedName] = true;
                }
            });

            if ($validator->fails()) {
                $isDuplicate = in_array('A heritage shop with the same name and location already exists.', $validator->errors()->get('shop_name'), true);
                $isDuplicate ? $duplicates[] = $name : $invalid++;

                continue;
            }

            $valid = $validator->validated();
            $foodItems = $this->normalizeFoodItems($valid['food_items'] ?? []);

            try {
                $shop = DB::transaction(function () use ($valid, $foodItems): HeritageShop {
                    $shop = HeritageShop::query()->create([
                        ...collect($valid)->except(['publish_status', 'food_items', 'operating_hours'])->all(),
                        'operating_hours' => $this->normalizeOperatingHours($valid['operating_hours'] ?? null),
                        'food_items' => $foodItems,
                        'publish_status' => HeritageShop::STATUS_DRAFT,
                    ]);
                    $this->foodItemSync->syncQuickItems($shop, $foodItems);

                    return $shop;
                });
            } catch (QueryException $exception) {
                if ($this->isDuplicateIntegrityViolation($exception)) {
                    $duplicates[] = $name;

                    continue;
                }

                throw $exception;
            }

            $this->auditLogger->record($request->user(), $shop, 'heritage_shop.created', [], $shop->getAttributes());
            $created++;
        }

        $message = $created.' shop'.($created === 1 ? '' : 's').' imported as Draft for review.';
        if ($duplicates !== []) {
            $message .= ' Skipped duplicates: '.implode(', ', $duplicates).'.';
        }
        if ($invalid > 0) {
            $message .= ' Skipped '.$invalid.' incomplete result'.($invalid === 1 ? '' : 's').'.';
        }

        return redirect()->route('admin.heritage-shops.index')->with('success', $message);
    }

    private function normalizeDiscoveredItem(array $item, ShopCrawlerService $service): array
    {
        $foodItems = $item['food_items'] ?? $item['menu'] ?? [];
        if (is_string($foodItems)) {
            $decoded = json_decode($foodItems, true);
            $foodItems = is_array($decoded) ? $decoded : $foodItems;
        }

        $candidate = $item;
        $candidate['shop_name'] = trim((string) ($item['shop_name'] ?? $item['name'] ?? ''));
        $candidate['heritage_story'] = $item['heritage_story'] ?? $item['description'] ?? null;
        $candidate['source_url'] = $service->normalizeUrl((string) ($item['source_url'] ?? ''));
        $candidate['food_items'] = $foodItems;
        unset($candidate['name'], $candidate['description'], $candidate['menu'], $candidate['publish_status']);

        foreach (['shop_name', 'primary_food_category', 'founder_name', 'current_owner_name', 'contact_number', 'address', 'city', 'state', 'postal_code'] as $field) {
            if (isset($candidate[$field]) && is_string($candidate[$field])) {
                $candidate[$field] = trim(preg_replace('/\s+/u', ' ', $candidate[$field]) ?? $candidate[$field]);
            }
        }

        return $candidate;
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
        $menuItems = $validated['food_items'] ?? [];

        if (is_string($menuItems)) {
            $decoded = json_decode($menuItems, true);
            $menuItems = is_array($decoded) ? $decoded : [];
        }

        $operatingHours = $this->normalizeOperatingHours($validated['operating_hours'] ?? null);

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

        $payload = [
            'shop_name' => $validated['shop_name'] ?? null,
            'primary_food_category' => $validated['primary_food_category'] ?? null,
            'establishment_year' => $validated['establishment_year'] ?? null,
            'founder_name' => $validated['founder_name'] ?? null,
            'founder_background' => $validated['founder_background'] ?? null,
            'current_owner_name' => $validated['current_owner_name'] ?? null,
            'current_owner_details' => $validated['current_owner_details'] ?? null,
            'heritage_story' => $validated['heritage_story'] ?? null,
            'operating_hours' => $operatingHours,
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

        if ($request->has('food_items')) {
            $payload['food_items'] = $this->normalizeFoodItems($menuItems);
        }

        return $payload;
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

    private function attachUploadedImages(HeritageShop $shop, array $uploadedFiles, array &$newPaths): void
    {
        foreach ($uploadedFiles as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $relativePath = $this->imageService->store($file);
            $newPaths[] = $relativePath;

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

    private function replaceRequestedImages(HeritageShop $shop, array $replacements, array &$newPaths, array &$oldPaths): void
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
            $path = $this->imageService->store($replacementFile);
            $newPaths[] = $path;
            $shop->images()->create([
                'path' => $path,
                'is_primary' => $wasPrimary || $shop->images()->count() === 0,
            ]);
            $oldPaths[] = $image->path;
            $image->forceDelete();

        }
    }

    private function removeRequestedImageIds(HeritageShop $shop, array $imageIds, array &$oldPaths): void
    {
        foreach ($imageIds as $imageId) {
            $image = $shop->images()->find((int) $imageId);
            if (! $image) {
                continue;
            }

            $wasPrimary = (bool) $image->is_primary;
            $oldPaths[] = $image->path;
            $image->delete();

            if ($wasPrimary) {
                $shop->images()->where('id', '!=', $image->id)->orderBy('id')->first()?->update(['is_primary' => true]);
            }

        }
    }

    private function normalizeOperatingHours(mixed $value): ?array
    {
        $operatingHours = trim((string) $value);

        return $operatingHours !== ''
            ? array_values(preg_split('/\\r\\n|\\r|\\n/', $operatingHours, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            : null;
    }

    private function deleteImagePaths(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            $this->imageService->delete($path);
        }
    }

    private function rethrowIntegrityViolation(Throwable $exception): never
    {
        if ($exception instanceof QueryException && $this->isDuplicateIntegrityViolation($exception)) {
            $message = strtolower($exception->getMessage());
            if (str_contains($message, 'heritage_shops_source_url_unique')
                || str_contains($message, 'heritage_shops.source_url')) {
                throw ValidationException::withMessages([
                    'source_url' => 'This source URL is already linked to another heritage shop.',
                ]);
            }

            if (str_contains($message, 'heritage_food_shop_normalized_name_unique')
                || str_contains($message, 'heritage_food_items.heritage_shop_id, heritage_food_items.normalized_name')) {
                throw ValidationException::withMessages([
                    'food_items' => 'A food item with the same name already exists for this shop.',
                ]);
            }

            throw ValidationException::withMessages([
                'shop_name' => 'A heritage shop with the same name and location already exists.',
            ]);
        }

        throw $exception;
    }

    private function isDuplicateIntegrityViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'heritage_shops_duplicate_key_unique')
            || str_contains($message, 'heritage_shops.duplicate_key')
            || str_contains($message, 'heritage_shops_source_url_unique')
            || str_contains($message, 'heritage_shops.source_url')
            || str_contains($message, 'heritage_food_shop_normalized_name_unique')
            || str_contains($message, 'heritage_food_items.heritage_shop_id, heritage_food_items.normalized_name');
    }

    private function recordPublicationTransition(Request $request, HeritageShop $shop, array $oldValues): void
    {
        $oldStatus = $oldValues['publish_status'] ?? HeritageShop::STATUS_DRAFT;
        $newStatus = $shop->publish_status;

        if ($oldStatus === $newStatus) {
            return;
        }

        $action = match ($newStatus) {
            HeritageShop::STATUS_PUBLISHED => 'heritage_shop.published',
            HeritageShop::STATUS_ARCHIVED => 'heritage_shop.archived',
            default => $oldStatus === HeritageShop::STATUS_PUBLISHED ? 'heritage_shop.unpublished' : null,
        };

        if ($action !== null) {
            $this->auditLogger->record($request->user(), $shop, $action, $oldValues, $shop->getAttributes());
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

        $uploadedFile = new File($tempPath);
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
