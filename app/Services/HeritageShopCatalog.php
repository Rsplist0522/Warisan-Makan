<?php

namespace App\Services;

use App\Models\HeritageShop;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class HeritageShopCatalog
{
    public const CATEGORIES = ['Main Dishes', 'Desserts', 'Drinks'];

    public function all(): array
    {
        if (Schema::hasTable('heritage_shops')) {
            try {
                // We eager load 'images' which is the correct relationship in your model
                return HeritageShop::query()
                    ->with(['images']) 
                    ->published()
                    ->get()
                    ->map(fn (HeritageShop $shop) => $this->mapShop($shop))
                    ->all();
            } catch (\Throwable $e) {
                logger()->error('HeritageShopCatalog: Database query failed. ' . $e->getMessage());
            }
        }
        return [];
    }

    public function withFilters(array $filters): array
    {
        $shops = $this->all();
        if (!empty($filters['category'])) {
            $shops = array_values(array_filter(
                $shops,
                fn (array $shop) => ($shop['category'] ?? '') === $filters['category']
            ));
        }
        return $shops;
    }

    private function mapShop(HeritageShop $shop): array
    {
        return [
            'name' => $shop->shop_name ?? 'Unknown Heritage Shop',
            'description' => $shop->heritage_story ?: $shop->current_owner_details ?: 'A heritage discovery.',
            'category' => in_array($shop->primary_food_category, self::CATEGORIES, true)
                ? $shop->primary_food_category
                : self::CATEGORIES[0],
            'state' => $shop->state ?: $shop->city ?: 'Malaysia',
            'year' => $shop->establishment_year ? (string) $shop->establishment_year : 'Heritage',
            'image' => $this->shopImage($shop),
            'address' => $shop->address ?: null,
            'source_id' => $shop->id,
            'food_items' => is_array($shop->food_items) ? $shop->food_items : [],
        ];
    }

    private function shopImage(HeritageShop $shop): string
    {
        try {
            // Your project uses a specific route for images: heritage-shops.images.show
            // We find the primary image and generate that specific URL
            if ($shop->images && $shop->images->count() > 0) {
                $image = $shop->images->where('is_primary', true)->first() ?: $shop->images->first();
                
                if ($image) {
                    return route('heritage-shops.images.show', [
                        'heritageShop' => $shop->id,
                        'image' => $image->id,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            logger()->warning('HeritageShopCatalog: could not generate shop image URL.');
        }

        // Professional fallback if no image is found
        return 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80';
    }
}
