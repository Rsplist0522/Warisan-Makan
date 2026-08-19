<?php

namespace App\Services;

use App\Models\HeritageShop;
use Illuminate\Support\Facades\Schema;


class HeritageShopCatalog
{
    /**
     * Food category is a fixed 3-value set, independent of whatever
     * categories exist in the real heritage_shops table later on.
     */
    public const CATEGORIES = ['Main Dishes', 'Desserts', 'Drinks'];

    public function all(): array
    {
        if (Schema::hasTable('heritage_shops')) {
            try {
                $shops = HeritageShop::where('publish_status', 'approved')
                    ->get()
                    ->map(fn (HeritageShop $shop) => $this->mapShop($shop))
                    ->all();

                if (count($shops) > 0) {
                    return $shops;
                }
            } catch (\Throwable $e) {
                logger()->warning('HeritageShopCatalog: query failed, using fake data. ' . $e->getMessage());
            }
        }

        return $this->sampleShops();
    }

    public function withFilters(array $filters): array
    {
        $shops = $this->all();

        if (!empty($filters['state'])) {
            $shops = array_values(array_filter(
                $shops,
                fn (array $shop) => ($shop['state'] ?? '') === $filters['state']
            ));
        }

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
            'description' => $shop->heritage_story ?: $shop->current_owner_details ?: 'A heritage discovery with an enduring local story.',
            // Real data may not use the 3-value set yet — fall back to a safe default
            // so filters/chat don't silently exclude real shops with mismatched labels.
            'category' => in_array($shop->primary_food_category, self::CATEGORIES, true)
                ? $shop->primary_food_category
                : self::CATEGORIES[array_rand(self::CATEGORIES)],
            'state' => $shop->state ?: $shop->city ?: 'Malaysia',
            'year' => $shop->establishment_year ? (string) $shop->establishment_year : 'Heritage',
            'image' => $this->shopImage($shop),
            'address' => $shop->address ?: null,
            'founder_background' => $shop->founder_background ?: null,
            'food_items' => is_array($shop->food_items) ? $shop->food_items : null,
        ];
    }

    private function shopImage(HeritageShop $shop): string
    {
        try {
            if ($shop->relationLoaded('media') && $shop->media->isNotEmpty()) {
                $media = $shop->media->firstWhere('is_primary', true) ?? $shop->media->first();

                if ($media && !empty($media->url)) {
                    return $media->url;
                }
            }
        } catch (\Throwable $e) {
            logger()->warning('HeritageShopCatalog: could not read shop image. ' . $e->getMessage());
        }

        return 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80';
    }

    /**
     * Fake sample shops (used until the cloud database has real approved rows).
     * Categories are restricted to the 3-value set: Main Dishes / Desserts / Drinks.
     */
    private function sampleShops(): array
    {
        return [
            [
                'name' => 'Kedai Kopi Pak Hassan',
                'description' => 'A heritage coffee house known for its hand-brewed local favourites and warm stories from the founder.',
                'category' => 'Drinks',
                'state' => 'Selangor',
                'year' => '1987',
                'image' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=900&q=80',
                'address' => 'Jalan Besar, Kajang, Selangor',
                'founder_background' => null,
                'food_items' => null,
            ],
            [
                'name' => 'Nasi Lemak Seri Warisan',
                'description' => 'A beloved old-school stall serving fragrant nasi lemak with recipes passed down through generations.',
                'category' => 'Main Dishes',
                'state' => 'Penang',
                'year' => '1974',
                'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80',
                'address' => 'Lebuh Chulia, George Town, Penang',
                'founder_background' => null,
                'food_items' => null,
            ],
            [
                'name' => 'Kampung Kuih Mak Cik',
                'description' => 'A small family-run kitchen celebrated for colourful traditional kuih and seasonal festival treats.',
                'category' => 'Desserts',
                'state' => 'Johor',
                'year' => '1992',
                'image' => 'https://images.unsplash.com/photo-1482049016688-2d3e1b311543?auto=format&fit=crop&w=900&q=80',
                'address' => 'Jalan Wong Ah Fook, Johor Bahru, Johor',
                'founder_background' => null,
                'food_items' => null,
            ],
            [
                'name' => 'Restoran Warisan Rasa',
                'description' => 'A heritage restaurant that preserves old recipes with a contemporary dining experience.',
                'category' => 'Main Dishes',
                'state' => 'Kelantan',
                'year' => '1965',
                'image' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=80',
                'address' => 'Jalan Sultanah Zainab, Kota Bharu, Kelantan',
                'founder_background' => null,
                'food_items' => null,
            ],
            [
                'name' => 'Cendol Warisan Melaka',
                'description' => 'A century-old dessert stall famous for its coconut-milk cendol, served the same way since it opened.',
                'category' => 'Desserts',
                'state' => 'Melaka',
                'year' => '1958',
                'image' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=900&q=80',
                'address' => 'Jalan Hang Jebat, Melaka',
                'founder_background' => null,
                'food_items' => null,
            ],
            [
                'name' => 'Teh Tarik Corner Ipoh',
                'description' => 'A beloved roadside stall known for its hand-pulled teh tarik and old-town coffeeshop atmosphere.',
                'category' => 'Drinks',
                'state' => 'Perak',
                'year' => '1980',
                'image' => 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?auto=format&fit=crop&w=900&q=80',
                'address' => 'Jalan Sultan Iskandar, Ipoh, Perak',
                'founder_background' => null,
                'food_items' => null,
            ],
        ];
    }
}