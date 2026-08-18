<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HeritageShop;

class HeritageShopController extends Controller
{
    // Satisfies FR 2.1.1 and FR 2.1.2
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $category = trim((string) $request->input('category', ''));

        $shops = HeritageShop::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shop_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('state', 'like', "%{$search}%")
                        ->orWhere('heritage_story', 'like', "%{$search}%")
                        ->orWhere('primary_food_category', 'like', "%{$search}%");
                });
            })
            ->when($category, function ($query) use ($category) {
                $query->where('primary_food_category', 'like', "%{$category}%");
            })
            ->orderBy('shop_name')
            ->get();

        return view('heritage.shops', compact('shops', 'search', 'category'));
    }

    private function allShops(): array
    {
        return [
            [
                'name' => 'Restoran Warisan Selera',
                'location' => 'Kuala Lumpur',
                'category' => 'Traditional Noodles',
                'description' => 'A family-run noodle house serving heirloom recipes passed through three generations.',
                'participating_since' => '2018',
                'highlight' => 'Known for hand-pulled noodles and heritage sambal.',
                'founder' => 'Ahmad Rahman',
                'establishment_year' => '1968',
                'heritage_story' => 'Started by a single-carriage hawker, recipes were preserved and taught within the family.',
                'operating_hours' => 'Tue–Sun 09:00–18:00',
            ],
            [
                'name' => 'Satay House Batu Pahat',
                'location' => 'Johor Bahru',
                'category' => 'Street Food',
                'description' => 'A heritage satay stall that has been serving locals since the 1970s.',
                'participating_since' => '2015',
                'highlight' => 'Signature satay and charcoal-grilled skewers.',
                'founder' => 'Haji Musa',
                'establishment_year' => '1974',
                'heritage_story' => 'A roadside stall that grew a loyal following for its marinade and charcoal technique.',
                'operating_hours' => 'Daily 17:00–23:00',
            ],
            [
                'name' => 'Kampung Desserts Hub',
                'location' => 'Penang',
                'category' => 'Desserts',
                'description' => 'A dessert boutique featuring traditional kuih and syrup recipes from the village.',
                'participating_since' => '2021',
                'highlight' => 'Popular for kuih lapis and gula melaka treats.',
                'founder' => 'Puan Siti',
                'establishment_year' => '1986',
                'heritage_story' => 'Recipes collected from neighbouring kampungs and adapted to a boutique setting.',
                'operating_hours' => 'Wed–Sun 10:00–16:00',
            ],
            [
                'name' => 'Makan Tradisi Kuantan',
                'location' => 'Pahang',
                'category' => 'Rice Dishes',
                'description' => 'A heritage rice shop serving classic dishes and old-fashioned hospitality.',
                'participating_since' => '2017',
                'highlight' => 'Famous for nasi dagang and slow-cooked curries.',
                'founder' => 'Liang & Family',
                'establishment_year' => '1959',
                'heritage_story' => 'A multi-ethnic family-run kitchen that blended recipes across generations.',
                'operating_hours' => 'Mon–Sat 08:00–15:00',
            ],
        ];
    }

    private function searchShops(?string $search = null, ?string $category = null): array
    {
        $shops = $this->allShops();
        $search = mb_strtolower(trim((string) $search));
        $category = mb_strtolower(trim((string) $category));

        return array_values(array_filter($shops, function (array $shop) use ($search, $category): bool {
            $name = mb_strtolower($shop['name']);
            $location = mb_strtolower($shop['location']);
            $description = mb_strtolower($shop['description']);
            $heritageStory = mb_strtolower($shop['heritage_story'] ?? '');
            $shopCategory = mb_strtolower($shop['category']);

            $matchesSearch = $search === ''
                || str_contains($name, $search)
                || str_contains($location, $search)
                || str_contains($description, $search)
                || str_contains($heritageStory, $search)
                || str_contains($shopCategory, $search);

            $matchesCategory = $category === '' || $shopCategory === $category;

            return $matchesSearch && $matchesCategory;
        }));
    }

    // Show a single shop detail by DB id
    public function show(string $id)
    {
        $shop = HeritageShop::query()->with('images')->findOrFail($id);
        $shops = HeritageShop::query()->with('images')->orderBy('shop_name')->get();
        $menuItems = $this->resolveMenuItems($shop);

        $search = '';
        $category = '';

        return view('heritage.shops', compact('shop', 'shops', 'search', 'category', 'menuItems'));
    }

    private function resolveMenuItems(HeritageShop $shop): array
    {
        if (! empty($shop->food_items) && is_array($shop->food_items)) {
            return $shop->food_items;
        }

        $category = strtolower((string) ($shop->primary_food_category ?? ''));
        $name = strtolower((string) ($shop->shop_name ?? ''));

        $fallbackMenus = [
            'nasi' => [
                ['name' => 'Nasi Kandar', 'price' => 'RM 18.00', 'desc' => 'Fragrant rice served with curries, vegetables and a choice of classic house sides.'],
                ['name' => 'Ayam Goreng', 'price' => 'RM 14.00', 'desc' => 'Crisp fried chicken with a heritage spice blend.'],
                ['name' => 'Teh Tarik', 'price' => 'RM 4.50', 'desc' => 'Pulled milk tea, a staple pairing for the meal.'],
            ],
            'kopitiam' => [
                ['name' => 'Kaya Toast', 'price' => 'RM 7.50', 'desc' => 'Classic toasted bread with coconut jam and butter.'],
                ['name' => 'Hainanese Chicken Chop', 'price' => 'RM 22.00', 'desc' => 'Traditional chicken chop with a rich house gravy.'],
                ['name' => 'Ipoh White Coffee', 'price' => 'RM 6.00', 'desc' => 'Smooth local coffee known for its light, aromatic finish.'],
            ],
            'dessert' => [
                ['name' => 'Kuih Lapis', 'price' => 'RM 8.00', 'desc' => 'Layered steamed cake with a soft, fragrant texture.'],
                ['name' => 'Cendol', 'price' => 'RM 7.00', 'desc' => 'Shaved ice dessert with coconut milk and palm sugar.'],
                ['name' => 'Gula Melaka Pancake', 'price' => 'RM 9.50', 'desc' => 'A sweet local favourite with toasted caramel notes.'],
            ],
            'cantonese' => [
                ['name' => 'Pipa Duck', 'price' => 'RM 38.00', 'desc' => 'A signature Cantonese roast with a deep, savoury flavour.'],
                ['name' => 'Eight Treasure Duck', 'price' => 'RM 46.00', 'desc' => 'Festive banquet-style dish with a layered heritage profile.'],
                ['name' => 'Wok-Fried Greens', 'price' => 'RM 16.00', 'desc' => 'Traditional vegetable preparation with garlic and oyster sauce.'],
            ],
            'satay' => [
                ['name' => 'Chicken Satay', 'price' => 'RM 16.00', 'desc' => 'Charcoal-grilled skewers with a classic peanut dip.'],
                ['name' => 'Beef Satay', 'price' => 'RM 18.00', 'desc' => 'Tender beef skewers with aromatic spice and smoky char.'],
                ['name' => 'Nasi Impit', 'price' => 'RM 5.00', 'desc' => 'Compressed rice cakes served with satay and sauce.'],
            ],
        ];

        foreach ($fallbackMenus as $keyword => $items) {
            if (str_contains($category, $keyword) || str_contains($name, $keyword)) {
                return $items;
            }
        }

        return [
            ['name' => 'Signature Heritage Dish', 'price' => 'RM 20.00', 'desc' => 'A house-special plate highlighting the shop’s long-running recipes.'],
            ['name' => 'Chef’s Recommendation', 'price' => 'RM 24.00', 'desc' => 'A prepared favourite chosen for tradition, flavour, and local character.'],
            ['name' => 'House Beverage', 'price' => 'RM 5.50', 'desc' => 'A classic accompaniment that complements the meal experience.'],
        ];
    }
}
