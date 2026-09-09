<?php

namespace App\Services;

use App\Models\HeritageFoodItem;
use App\Models\HeritageShop;

class HeritageFoodItemSyncService
{
    /**
     * Synchronize the deliberately simple shop-form editor into the
     * authoritative normalized catalog. Omitted rich fields are preserved;
     * omitted quick items are deactivated rather than destroyed.
     */
    public function syncQuickItems(HeritageShop $shop, array $items): void
    {
        $retainedIds = [];

        foreach (array_values($items) as $order => $item) {
            if (! is_array($item) || blank($item['name'] ?? null)) {
                continue;
            }

            $payload = [
                'name' => trim((string) $item['name']),
                'description' => filled($item['description'] ?? null) ? trim((string) $item['description']) : (filled($item['desc'] ?? null) ? trim((string) $item['desc']) : null),
                'price' => filled($item['price'] ?? null) ? trim((string) $item['price']) : null,
                'display_order' => $order,
                'is_active' => true,
            ];

            $existing = is_numeric($item['id'] ?? null)
                ? $shop->foodItems()->find((int) $item['id'])
                : $shop->foodItems()->where('normalized_name', HeritageFoodItem::normalizeName($payload['name']))->orderBy('id')->first();

            if ($existing) {
                $existing->update($payload);
            } else {
                $existing = $shop->foodItems()->create([
                    ...$payload,
                    'category' => filled($item['category'] ?? null) ? trim((string) $item['category']) : null,
                    'heritage_significance' => filled($item['heritage_significance'] ?? null) ? trim((string) $item['heritage_significance']) : null,
                    'availability' => filled($item['availability'] ?? null) ? trim((string) $item['availability']) : null,
                    'image_path' => filled($item['image_path'] ?? null) ? trim((string) $item['image_path']) : null,
                ]);
            }

            $retainedIds[] = $existing->getKey();
        }

        $shop->foodItems()
            ->when($retainedIds !== [], fn ($query) => $query->whereNotIn('id', $retainedIds))
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->syncLegacyJson($shop);
    }

    /**
     * Keep the legacy JSON field as a compatibility projection only.
     */
    public function syncLegacyJson(HeritageShop $shop): void
    {
        $shop->setAttribute('food_items', $shop->foodItems()->get()->map(fn (HeritageFoodItem $item): array => array_filter([
            'id' => $item->id,
            'name' => $item->name,
            'price' => $item->price,
            'desc' => $item->description,
            'description' => $item->description,
            'category' => $item->category,
            'heritage_significance' => $item->heritage_significance,
            'availability' => $item->availability,
            'image_path' => $item->image_path,
            'is_active' => $item->is_active,
        ], fn ($value) => filled($value) || $value === false))->values()->all());
        $shop->save();
    }
}
