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
                $shops = HeritageShop::query()
                    ->with(['images']) 
                    ->published()
                    ->get()
                    ->map(fn (HeritageShop $shop) => $this->mapShop($shop))
                    ->all();

                if ($shops !== []) {
                    return $shops;
                }
            } catch (\Throwable $e) {
                logger()->error('HeritageShopCatalog: Database query failed. ' . $e->getMessage());
            }
        }
        return $this->sampleShops();
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

    private function sampleShops(): array
    {
        return [
            ['name' => 'Kedai Kopi Pak Hassan', 'description' => 'A heritage coffee house known for hand-brewed local favourites.', 'category' => 'Drinks', 'state' => 'Selangor', 'year' => '1987', 'image' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=900&q=80', 'address' => 'Jalan Besar, Kajang, Selangor'],
            ['name' => 'Nasi Lemak Seri Warisan', 'description' => 'An old-school nasi lemak stall with recipes passed down through generations.', 'category' => 'Main Dishes', 'state' => 'Penang', 'year' => '1974', 'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80', 'address' => 'Lebuh Chulia, George Town, Penang'],
            ['name' => 'Kampung Kuih Mak Cik', 'description' => 'A family kitchen celebrated for traditional kuih and festival treats.', 'category' => 'Desserts', 'state' => 'Johor', 'year' => '1992', 'image' => 'https://images.unsplash.com/photo-1482049016688-2d3e1b311543?auto=format&fit=crop&w=900&q=80', 'address' => 'Jalan Wong Ah Fook, Johor Bahru, Johor'],
        ];
    }
}
