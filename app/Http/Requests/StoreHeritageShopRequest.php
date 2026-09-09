<?php

namespace App\Http\Requests;

use App\Models\HeritageFoodItem;
use App\Models\HeritageShop;
use App\Services\HeritageShopIntegrityService;
use App\Services\HeritageShopValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Fluent;
use Illuminate\Validation\ValidationException;
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
            $this->validateQuickFoodItemNames($validator);
            $this->validatePrimaryImage($validator);

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $integrity = app(HeritageShopIntegrityService::class);
            $shop = $this->route('heritageShop');
            $shop = $shop instanceof HeritageShop ? $shop : null;
            $removeIds = (array) $this->input('remove_images', []);
            $newUploads = collect((array) $this->file('images', []))
                ->filter(fn ($file): bool => $file instanceof UploadedFile && $file->isValid())
                ->count();
            $replacementIds = collect((array) $this->file('replace_images', []))
                ->filter(fn ($file, $id): bool => is_numeric($id) && $file instanceof UploadedFile && $file->isValid())
                ->keys()
                ->all();
            $crawlerPaths = (array) $this->input('crawler_images', []);

            $finalCount = $integrity->finalGalleryCount($shop, $removeIds, $newUploads, $crawlerPaths);
            $finalValidCount = $integrity->finalGalleryCount(
                $shop,
                [...$removeIds, ...$replacementIds],
                $newUploads + count($replacementIds),
                $crawlerPaths,
                true,
            );

            try {
                $integrity->validateFinalState($this->all(), $shop, $finalCount, $finalValidCount);
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        });
    }

    public function rules(): array
    {

        $shop = $this->route('heritageShop');

        return [
            ...HeritageShopValidationRules::fields($shop instanceof HeritageShop ? $shop->getKey() : null),
            'version' => [$shop instanceof HeritageShop ? 'required' : 'nullable', 'integer', 'min:1'],

            'images' => ['nullable', 'array', 'max:'.(int) config('heritage_shop.max_gallery_images', 10)],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('heritage_shop.max_image_kb', 2048)],
            'replace_images' => ['nullable', 'array'],
            'replace_images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('heritage_shop.max_image_kb', 2048)],

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
        $maximumImages = (int) config('heritage_shop.max_gallery_images', 10);
        $maximumMegabytes = round((int) config('heritage_shop.max_image_kb', 2048) / 1024, 1);

        return [
            'postal_code.regex' => 'The postal code must contain exactly 5 digits.',
            'publish_status.in' => 'The selected publication status must be Draft, Published, or Archived.',
            'images.max' => "A Heritage Shop gallery can contain at most {$maximumImages} images.",
            'images.*.max' => "Each Heritage Shop image must be {$maximumMegabytes} MB or smaller.",
            'replace_images.*.max' => "Each Heritage Shop image must be {$maximumMegabytes} MB or smaller.",
            'images.*.mimes' => 'Heritage Shop images must be JPG, JPEG, PNG, or WebP files.',
            'replace_images.*.mimes' => 'Heritage Shop images must be JPG, JPEG, PNG, or WebP files.',
        ];
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
