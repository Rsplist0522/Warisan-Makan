<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CrawlHeritageShopRequest;
use App\Http\Requests\StoreHeritageShopRequest;
use App\Models\HeritageShop;
use App\Models\ShopImage;
use App\Services\ShopCrawlerService;
use Illuminate\Http\JsonResponse;
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
    public function index(): View
    {
        $shops = HeritageShop::query()->latest()->get();

        return view('admin.heritage-shops.index', compact('shops'));
    }

    public function create(): View
    {
        return view('admin.heritage-shops.form', [
            'shop' => new HeritageShop(),
            'mode' => 'create',
        ]);
    }

    public function store(StoreHeritageShopRequest $request): RedirectResponse
    {
        $data = $this->shopPayload($request);

        $shop = DB::transaction(function () use ($data, $request): HeritageShop {
            $shop = HeritageShop::query()->create($data);
            $this->attachUploadedImages($shop, $request->file('images', []));
            $this->attachStoredCrawlerImages($shop, $request->input('crawler_images', []));
            $this->removeRequestedImageIds($request->input('remove_images', []));
            $this->replaceRequestedImages($shop, $request->allFiles(), $request->input('replace_images', []));

            return $shop;
        });

        return redirect()->route('admin.heritage-shops.edit', $shop)
            ->with('success', 'Heritage shop saved successfully.');
    }

    public function edit(HeritageShop $heritageShop): View
    {
        $heritageShop->load('images');

        return view('admin.heritage-shops.form', [
            'shop' => $heritageShop,
            'mode' => 'edit',
        ]);
    }

    public function update(StoreHeritageShopRequest $request, HeritageShop $heritageShop): RedirectResponse
    {
        $data = $this->shopPayload($request);

        $shop = DB::transaction(function () use ($data, $request, $heritageShop): HeritageShop {
            $heritageShop->fill($data)->save();
            $this->attachUploadedImages($heritageShop, $request->file('images', []));
            $this->attachStoredCrawlerImages($heritageShop, $request->input('crawler_images', []));
            $this->removeRequestedImageIds($request->input('remove_images', []));
            $this->replaceRequestedImages($heritageShop, $request->allFiles(), $request->input('replace_images', []));

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
            ->map(fn (array $item): array => [
                'name' => trim((string) ($item['name'] ?? '')),
                'price' => trim((string) ($item['price'] ?? '')) ?: null,
                'desc' => trim((string) ($item['desc'] ?? '')) ?: null,
            ])
            ->values()
            ->all();
    }

    private function attachUploadedImages(HeritageShop $shop, array $uploadedFiles): void
    {
        foreach ($uploadedFiles as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $disk = Storage::disk(config('filesystems.media_disk') ?: 'public');
            $relativePath = $disk->putFile('heritage-shops', $file);

            if ($relativePath === false || blank($relativePath)) {
                throw new RuntimeException('One of the uploaded images could not be saved.');
            }

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

            $normalizedPath = str_starts_with($path, 'http') ? $this->persistCrawledImage($path) : $path;
            $shop->images()->create([
                'path' => $normalizedPath,
                'is_primary' => $shop->images()->count() === 0,
            ]);
        }
    }

    private function replaceRequestedImages(HeritageShop $shop, array $files, array $replacements): void
    {
        foreach ($replacements as $imageId => $replacementFile) {
            if (! is_numeric($imageId) || ! $replacementFile instanceof UploadedFile || ! $replacementFile->isValid()) {
                continue;
            }

            $image = $shop->images()->withTrashed()->find((int) $imageId);
            if (! $image) {
                continue;
            }

            Storage::disk(config('filesystems.media_disk') ?: 'public')->delete($image->path);
            $image->forceDelete();
            $disk = Storage::disk(config('filesystems.media_disk') ?: 'public');
            $path = $disk->putFile('heritage-shops', $replacementFile);
            $shop->images()->create([
                'path' => $path,
                'is_primary' => $shop->images()->count() === 0,
            ]);
        }
    }

    private function removeRequestedImageIds(array $imageIds): void
    {
        foreach ($imageIds as $imageId) {
            $image = ShopImage::query()->find((int) $imageId);
            if (! $image) {
                continue;
            }

            Storage::disk(config('filesystems.media_disk') ?: 'public')->delete($image->path);
            $image->delete();
        }
    }

    private function persistCrawledImage(string $imageUrl): string
    {
        if (! filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('The crawler returned an invalid image URL.');
        }

        $response = Http::timeout(20)->accept('image/*')->get($imageUrl);
        if ($response->failed()) {
            throw new RuntimeException('The crawler image could not be downloaded.');
        }

        $contentType = $response->header('Content-Type');
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $extension = $allowedTypes[$contentType] ?? null;

        if (! $extension) {
            throw new RuntimeException('Crawler image type is unsupported. Use JPG, PNG, or WebP.');
        }

        $sizeInKb = $response->header('Content-Length');
        $sizeBytes = is_numeric($sizeInKb) ? ((int) $sizeInKb) : strlen($response->body());
        if ($sizeBytes > 5 * 1024 * 1024) {
            throw new RuntimeException('Crawler image exceeds the 5MB limit.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'wm-crawler-');
        if ($tempPath === false) {
            throw new RuntimeException('The crawler image temporary file could not be created.');
        }

        file_put_contents($tempPath, $response->body());
        $uploadedFile = new \Illuminate\Http\File($tempPath);
        $relativePath = Storage::disk(config('filesystems.media_disk') ?: 'public')->putFile('heritage-shops/crawler', $uploadedFile);

        @unlink($tempPath);

        if ($relativePath === false || blank($relativePath)) {
            throw new RuntimeException('The crawler image could not be stored.');
        }

        return $relativePath;
    }
}
