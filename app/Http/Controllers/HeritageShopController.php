<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HeritageShopController extends Controller
{
    // Satisfies FR 2.1.1 and FR 2.1.2
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $category = trim((string) $request->input('category', ''));

        $shops = $this->searchShops($search, $category);

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
            ],
            [
                'name' => 'Satay House Batu Pahat',
                'location' => 'Johor Bahru',
                'category' => 'Street Food',
                'description' => 'A heritage satay stall that has been serving locals since the 1970s.',
                'participating_since' => '2015',
                'highlight' => 'Signature satay and charcoal-grilled skewers.',
            ],
            [
                'name' => 'Kampung Desserts Hub',
                'location' => 'Penang',
                'category' => 'Desserts',
                'description' => 'A dessert boutique featuring traditional kuih and syrup recipes from the village.',
                'participating_since' => '2021',
                'highlight' => 'Popular for kuih lapis and gula melaka treats.',
            ],
            [
                'name' => 'Makan Tradisi Kuantan',
                'location' => 'Pahang',
                'category' => 'Rice Dishes',
                'description' => 'A heritage rice shop serving classic dishes and old-fashioned hospitality.',
                'participating_since' => '2017',
                'highlight' => 'Famous for nasi dagang and slow-cooked curries.',
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
            $shopCategory = mb_strtolower($shop['category']);

            $matchesSearch = $search === ''
                || str_contains($name, $search)
                || str_contains($location, $search)
                || str_contains($description, $search)
                || str_contains($shopCategory, $search);

            $matchesCategory = $category === '' || $shopCategory === $category;

            return $matchesSearch && $matchesCategory;
        }));
    }
}
