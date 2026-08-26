<?php

namespace App\Services;

use App\Models\HeritageShop;
use Illuminate\Support\Facades\Schema;

class HeritageShopCatalog
{
    public const CATEGORIES = ['Main Dishes', 'Desserts', 'Drinks'];

    public function all(): array
    {
        if (Schema::hasTable('heritage_shops')) {
            try {
                // Now correctly finds "published" shops from your registry
                $shops = HeritageShop::published()
                    ->get()
                    ->map(fn (HeritageShop $shop) => $this->mapShop($shop))
                    ->all();

                return $shops;
            } catch (\Throwable $e) {
                logger()->error('HeritageShopCatalog: Database query failed. ' . $e->getMessage());
            }
        }
        return [];
    }

    public function withFilters(array $filters): array
    {
        $shops = $this->all();
        if (!empty($filters['state'])) {
            $shops = array_values(array_filter($shops, fn (array $shop) => ($shop['state'] ?? '') === $filters['state']));
        }
        if (!empty($filters['category'])) {
            $shops = array_values(array_filter($shops, fn (array $shop) => ($shop['category'] ?? '') === $filters['category']));
        }
        return $shops;
    }

    private function mapShop(HeritageShop $shop): array
    {
        return [
            'name' => $shop->shop_name ?? 'Unknown Heritage Shop',
            'description' => $shop->heritage_story ?: $shop->current_owner_details ?: 'A heritage discovery with an enduring local story.',
            'category' => in_array($shop->primary_food_category, self::CATEGORIES, true) ? $shop->primary_food_category : self::CATEGORIES[0],
            'state' => $shop->state ?: $shop->city ?: 'Malaysia',
            'year' => $shop->establishment_year ? (string) $shop->establishment_year : 'Heritage',
            'image' => $this->shopImage($shop),
            'address' => $shop->address ?: null,
            'founder_background' => $shop->founder_background ?: null,
            'food_items' => is_array($shop->food_items) ? $shop->food_items : null,
            'source_id' => $shop->id, 
        ];
    }

    private function shopImage(HeritageShop $shop): string
    {
        try {
            if ($shop->relationLoaded('media') && $shop->media->isNotEmpty()) {
                $media = $shop->media->firstWhere('is_primary', true) ?? $shop->media->first();
                if ($media && !empty($media->url)) { return $media->url; }
            }
        } catch (\Throwable $e) { logger()->warning('HeritageShopCatalog: could not read shop image.'); }
        return 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80';
    }
}
