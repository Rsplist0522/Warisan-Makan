<?php

namespace App\Http\Requests;

use App\Models\HeritageFoodItem;
use App\Models\HeritageShop;
use App\Services\HeritageShopImageService;
use App\Services\HeritageShopValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Validator;

class StoreHeritageShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $singleLineFields = [
            'shop_name',
            'primary_food_category',
            'founder_name',
            'current_owner_name',
            'contact_number',
            'address',
            'city',
            'state',
            'postal_code',
        ];
        $normalized = [];
        foreach ($singleLineFields as $field) {
            if (is_string($this->input($field))) {
                $normalized[$field] = trim(preg_replace('/\s+/u', ' ', $this->input($field)) ?? $this->input($field));
            }
        }

        $url = trim((string) $this->input('source_url', ''));
        if ($url !== '') {
            $normalized['source_url'] = $url;
            $parts = parse_url($url);
            if (is_array($parts) && ! empty($parts['host'])) {
                $url = strtolower((string) ($parts['scheme'] ?? 'https')).'://'.strtolower((string) $parts['host'])
                    .(isset($parts['port']) ? ':'.(int) $parts['port'] : '')
                    .rtrim((string) ($parts['path'] ?? ''), '/')
                    .(isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '');
                $normalized['source_url'] = $url;
            }
        }

        $this->merge($normalized);
    }

    public function withValidator($validator): void
    {
        $validator->sometimes(
            ['heritage_story', 'address', 'city'],
            ['required', 'string'],
            fn (Fluent $input): bool => $input->publish_status === HeritageShop::STATUS_PUBLISHED,
        );

        $validator->after(function (Validator $validator): void {
            $this->validateDuplicateIdentity($validator);
            $this->validateQuickFoodItemNames($validator);
            $this->validatePrimaryImage($validator);

            if ($this->input('publish_status') === HeritageShop::STATUS_PUBLISHED) {
                $this->validatePublishedImage($validator);
            }
        });
    }

    public function rules(): array
    {

        $shop = $this->route('heritageShop');

        return [
            ...HeritageShopValidationRules::fields($shop instanceof HeritageShop ? $shop->getKey() : null),
            'version' => [$shop instanceof HeritageShop ? 'required' : 'nullable', 'integer', 'min:1'],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('heritage_shop.max_image_kb', 1024)],
            'replace_images' => ['nullable', 'array'],
            'replace_images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('heritage_shop.max_image_kb', 1024)],

            'existing_images' => ['nullable', 'array'],
            'existing_images.*' => ['integer'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
            'primary_image_id' => ['nullable', 'integer'],
            'menu_items_json' => ['nullable', 'json', 'max:50000'],
            'crawler_images' => ['nullable', 'array'],
            'crawler_images.*' => [
                'string',
                'max:500',
                'starts_with:heritage-shops/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'postal_code.regex' => 'The postal code must contain exactly 5 digits.',
            'publish_status.in' => 'The selected publication status must be Draft, Published, or Archived.',
        ];
    }

    private function validateDuplicateIdentity(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['shop_name', 'address', 'city', 'state'])) {
            return;
        }

        $shop = $this->route('heritageShop');
        $duplicateKey = HeritageShop::duplicateKeyFor(
            $this->input('shop_name'),
            $this->input('address'),
            $this->input('city'),
            $this->input('state'),
        );
        $duplicate = HeritageShop::query()
            ->where('duplicate_key', $duplicateKey)
            ->when($shop instanceof HeritageShop, fn ($query) => $query->whereKeyNot($shop->getKey()))
            ->exists();

        $isUnchangedLegacyIdentity = $shop instanceof HeritageShop
            && $shop->duplicate_key === null
            && HeritageShop::duplicateKeyFor(
                $shop->getOriginal('shop_name'),
                $shop->getOriginal('address'),
                $shop->getOriginal('city'),
                $shop->getOriginal('state'),
            ) === $duplicateKey;

        if ($duplicate && ! $isUnchangedLegacyIdentity) {
            $validator->errors()->add('shop_name', 'A heritage shop with the same name and location already exists.');
        }
    }

    private function validateQuickFoodItemNames(Validator $validator): void
    {
        $seen = [];
        $shop = $this->route('heritageShop');
        foreach ((array) $this->input('food_items', []) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            if (blank($item['name'] ?? null)) {
                if (filled($item['price'] ?? null) || filled($item['desc'] ?? null) || filled($item['description'] ?? null)) {
                    $validator->errors()->add("food_items.{$index}.name", 'The food item name is required when other item details are provided.');
                }

                continue;
            }

            $normalized = HeritageFoodItem::normalizeName($item['name']);
            if (isset($seen[$normalized])) {
                $validator->errors()->add("food_items.{$index}.name", 'A food item with the same name already exists for this shop.');
            }

            if ($shop instanceof HeritageShop) {
                $conflict = $shop->foodItems()
                    ->where('normalized_name', $normalized)
                    ->when(is_numeric($item['id'] ?? null), fn ($query) => $query->whereKeyNot((int) $item['id']))
                    ->where('is_active', true)
                    ->exists();
                if ($conflict) {
                    $validator->errors()->add("food_items.{$index}.name", 'A food item with the same name already exists for this shop.');
                }
            }
            $seen[$normalized] = true;
        }
    }

    private function validatePublishedImage(Validator $validator): void
    {
        $imageService = app(HeritageShopImageService::class);
        $shop = $this->route('heritageShop');
        $currentImages = $shop instanceof HeritageShop
            ? $shop->images()->get()->filter(fn ($image): bool => $imageService->exists($image->path))->keyBy('id')
            : collect();
        $removeIds = collect((array) $this->input('remove_images', []))->map(fn ($id): int => (int) $id);
        $replacementFiles = collect((array) $this->file('replace_images', []))
            ->filter(fn ($file, $id): bool => is_numeric($id) && $file instanceof UploadedFile && $file->isValid() && $currentImages->has((int) $id));
        $replacedIds = $replacementFiles->keys()->map(fn ($id): int => (int) $id);
        $retainedCount = $currentImages->keys()
            ->diff($removeIds)
            ->diff($replacedIds)
            ->count();
        $uploadedCount = collect((array) $this->file('images', []))
            ->filter(fn ($file): bool => $file instanceof UploadedFile && $file->isValid())
            ->count();
        $crawlerCount = collect((array) $this->input('crawler_images', []))
            ->unique()
            ->filter(fn ($path): bool => is_string($path)
                && str_starts_with($path, $imageService->directory().'/crawler/')
                && ! str_contains($path, '..')
                && $imageService->exists($path))
            ->count();

        if ($retainedCount + $replacementFiles->count() + $uploadedCount + $crawlerCount < 1) {
            $validator->errors()->add('images', 'A published heritage shop must have at least one valid shop image.');
        }
    }

    private function validatePrimaryImage(Validator $validator): void
    {
        $imageId = $this->integer('primary_image_id');
        if (! $imageId) {
            return;
        }

        $shop = $this->route('heritageShop');
        if (! $shop instanceof HeritageShop || ! $shop->images()->whereKey($imageId)->exists()) {
            $validator->errors()->add('primary_image_id', 'The selected primary image does not belong to this shop.');
        }

        if (in_array($imageId, array_map('intval', (array) $this->input('remove_images', [])), true)) {
            $validator->errors()->add('primary_image_id', 'Choose a different primary image before removing this one.');
        }
    }
}
