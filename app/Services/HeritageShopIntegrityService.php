<?php

namespace App\Services;

use App\Models\HeritageShop;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HeritageShopIntegrityService
{
    public function __construct(private HeritageShopImageService $imageService) {}

    public function normalize(array $payload): array
    {
        foreach ([
            'shop_name', 'primary_food_category', 'founder_name', 'current_owner_name',
            'contact_number', 'address', 'city', 'state', 'postal_code',
        ] as $field) {
            if (isset($payload[$field]) && is_string($payload[$field])) {
                $payload[$field] = trim(preg_replace('/\s+/u', ' ', $payload[$field]) ?? $payload[$field]);
            }
        }

        foreach (['heritage_story', 'founder_background', 'current_owner_details'] as $field) {
            if (isset($payload[$field]) && is_string($payload[$field])) {
                $payload[$field] = trim($payload[$field]);
            }
        }

        return $payload;
    }

    /** Validate the proposed final state, regardless of the originating workflow. */
    public function validateFinalState(
        array $candidate,
        ?HeritageShop $shop,
        int $finalGalleryCount,
        ?int $finalValidGalleryCount = null,
    ): array {
        $candidate = $this->normalize($candidate);
        $rules = HeritageShopValidationRules::fields($shop?->getKey());
        $rules['operating_hours'] = ['nullable'];
        $rules['publish_status'] = ['required', Rule::in(HeritageShop::ADMIN_STATUSES)];

        $validator = Validator::make($candidate, $rules, [
            'postal_code.regex' => 'The postal code must contain exactly 5 digits.',
            'publish_status.in' => 'The selected publication status must be Draft, Published, or Archived.',
        ]);

        if (($candidate['publish_status'] ?? null) === HeritageShop::STATUS_PUBLISHED) {
            $validator->sometimes(['heritage_story', 'address', 'city'], ['required', 'string'], fn (): bool => true);
        }

        $validator->after(function ($validator) use ($candidate, $shop, $finalGalleryCount, $finalValidGalleryCount): void {
            if (! $validator->errors()->hasAny(['shop_name', 'address', 'city', 'state'])) {
                $duplicateKey = HeritageShop::duplicateKeyFor(
                    $candidate['shop_name'] ?? null,
                    $candidate['address'] ?? null,
                    $candidate['city'] ?? null,
                    $candidate['state'] ?? null,
                );
                $duplicate = HeritageShop::query()
                    ->where('duplicate_key', $duplicateKey)
                    ->when($shop, fn ($query) => $query->whereKeyNot($shop->getKey()))
                    ->exists();
                $unchangedLegacyIdentity = $shop
                    && $shop->duplicate_key === null
                    && HeritageShop::duplicateKeyFor(
                        $shop->getOriginal('shop_name'),
                        $shop->getOriginal('address'),
                        $shop->getOriginal('city'),
                        $shop->getOriginal('state'),
                    ) === $duplicateKey;

                if ($duplicate && ! $unchangedLegacyIdentity) {
                    $validator->errors()->add('shop_name', 'A heritage shop with the same name and location already exists.');
                }
            }

            $maximum = $this->maximumGalleryImages();
            if ($finalGalleryCount > $maximum) {
                $validator->errors()->add('images', "This shop can contain a maximum of {$maximum} gallery images.");
            }

            $validCount = $finalValidGalleryCount ?? $finalGalleryCount;
            if (($candidate['publish_status'] ?? null) === HeritageShop::STATUS_PUBLISHED && $validCount < 1) {
                $validator->errors()->add('images', 'A Published Heritage Shop must contain at least one gallery image.');
            }
        });

        return $validator->validate();
    }

    /** Replacements are absent because replacing an existing image is net zero. */
    public function finalGalleryCount(
        ?HeritageShop $shop,
        array $removeImageIds = [],
        int $newUploadCount = 0,
        array $newStoredPaths = [],
        bool $validOnly = false,
    ): int {
        $images = $shop?->images()->get() ?? collect();
        if ($validOnly) {
            $images = $images->filter(fn ($image): bool => $this->imageService->exists($image->path));
        }

        $removeIds = collect($removeImageIds)->map(fn ($id): int => (int) $id)->unique();
        $retained = $images->whereNotIn('id', $removeIds)->count();
        $existingPaths = $images->pluck('path');
        $newPathCount = collect($newStoredPaths)
            ->filter(fn ($path): bool => is_string($path) && $path !== '')
            ->unique()
            ->reject(fn (string $path): bool => $existingPaths->contains($path))
            ->when($validOnly, fn ($paths) => $paths->filter(fn (string $path): bool => $this->imageService->exists($path)))
            ->count();

        return $retained + max(0, $newUploadCount) + $newPathCount;
    }

    public function ensurePrimaryImage(HeritageShop $shop, ?int $preferredImageId = null): void
    {
        $images = $shop->images()->orderBy('id')->get();
        if ($images->isEmpty()) {
            return;
        }

        $primaryId = $preferredImageId && $images->contains('id', $preferredImageId)
            ? $preferredImageId
            : ($images->firstWhere('is_primary', true)?->getKey() ?? $images->first()->getKey());

        $shop->images()->whereKeyNot($primaryId)->update(['is_primary' => false]);
        $shop->images()->whereKey($primaryId)->update(['is_primary' => true]);
    }

    public function maximumGalleryImages(): int
    {
        return max(1, (int) config('heritage_shop.max_gallery_images', 10));
    }
}
