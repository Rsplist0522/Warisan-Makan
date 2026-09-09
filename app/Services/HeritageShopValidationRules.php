<?php

namespace App\Services;

use App\Models\HeritageShop;
use App\Rules\MalaysianPhoneNumber;
use Illuminate\Validation\Rule;

class HeritageShopValidationRules
{
    /**
     * Rules shared by manual administrator writes and reviewed crawler imports.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function fields(?int $ignoreShopId = null, bool $sourceUrlRequired = false): array
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
            'food_items.*.id' => ['nullable', 'integer'],
            'food_items.*.name' => ['nullable', 'string', 'max:255'],
            'food_items.*.price' => ['nullable', 'string', 'max:80'],
            'food_items.*.desc' => ['nullable', 'string', 'max:1000'],
            'food_items.*.description' => ['nullable', 'string', 'max:1000'],
            'food_items.*.category' => ['nullable', 'string', 'max:120'],
            'food_items.*.heritage_significance' => ['nullable', 'string', 'max:3000'],
            'food_items.*.availability' => ['nullable', 'string', 'max:120'],
            'food_items.*.image_path' => ['nullable', 'string', 'max:500', 'starts_with:heritage-shops/'],
            'food_items.*.is_active' => ['nullable', 'boolean'],
            'contact_number' => ['nullable', 'string', 'max:30', new MalaysianPhoneNumber],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'regex:/^[0-9]{5}$/'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'source_url' => [
                $sourceUrlRequired ? 'required' : 'nullable',
                'url:http,https',
                'max:500',
                Rule::unique('heritage_shops', 'source_url')->ignore($ignoreShopId),
            ],
            'publish_status' => ['nullable', Rule::in(HeritageShop::ADMIN_STATUSES)],
        ];
    }
}
