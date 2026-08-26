<?php

namespace App\Http\Requests;

use App\Models\HeritageShop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;

class StoreHeritageShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $url = trim((string) $this->input('source_url', ''));
        if ($url !== '') {
            $parts = parse_url($url);
            if (is_array($parts) && ! empty($parts['host'])) {
                $url = strtolower((string) ($parts['scheme'] ?? 'https')).'://'.strtolower((string) $parts['host'])
                    .(isset($parts['port']) ? ':'.(int) $parts['port'] : '')
                    .rtrim((string) ($parts['path'] ?? ''), '/')
                    .(isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '');
                $this->merge(['source_url' => $url]);
            }
        }
    }

    public function withValidator($validator): void
    {
        $validator->sometimes(
            ['heritage_story', 'address', 'city'],
            ['required', 'string'],
            fn (Fluent $input): bool => $input->publish_status === HeritageShop::STATUS_PUBLISHED,
        );
    }

    public function rules(): array
    {

        return [
            'shop_name' => ['required', 'string', 'max:255'],
            'primary_food_category' => ['nullable', 'string', 'max:255'],
            'establishment_year' => ['nullable', 'integer', 'min:1000', 'max:'.now()->year],
            'founder_name' => ['nullable', 'string', 'max:255'],
            'founder_background' => ['nullable', 'string', 'max:5000'],
            'current_owner_name' => ['nullable', 'string', 'max:255'],
            'current_owner_details' => ['nullable', 'string', 'max:5000'],
            'heritage_story' => ['nullable', 'string', 'max:10000'],
            'operating_hours' => ['nullable', 'string', 'max:2000'],
            'food_items' => ['nullable', 'array', 'max:50'],

            'food_items.*.name' => ['nullable', 'string', 'max:255'],
            'food_items.*.price' => ['nullable', 'string', 'max:255'],
            'food_items.*.desc' => ['nullable', 'string', 'max:1000'],
            'contact_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]*$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'source_url' => [
                'nullable',
                'url',
                'max:500',
                Rule::unique('heritage_shops', 'source_url')->ignore($this->route('heritageShop') instanceof HeritageShop ? $this->route('heritageShop')->getKey() : null),
            ],
            'publish_status' => ['nullable', Rule::in(HeritageShop::ADMIN_STATUSES)],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('heritage_shop.max_image_kb', 1024)],
            'replace_images' => ['nullable', 'array'],
            'replace_images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('heritage_shop.max_image_kb', 1024)],

            'existing_images' => ['nullable', 'array'],
            'existing_images.*' => ['integer'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
            'menu_items_json' => ['nullable', 'string'],
            'crawler_images' => ['nullable', 'array'],
            'crawler_images.*' => [
                'string',
                'max:500',
                'starts_with:heritage-shops/',
            ],

        ];
    }
}
