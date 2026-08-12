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
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('heritage_story', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($category, function ($query) use ($category) {
                $query->where('category', 'like', "%{$category}%");
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
        $shop = HeritageShop::findOrFail($id);
        $shops = HeritageShop::orderBy('name')->get();

        // Provide listing-related variables so the merged view's search form
        // and listing logic do not trigger undefined variable errors.
        $search = '';
        $category = '';

        return view('heritage.shops', compact('shop', 'shops', 'search', 'category'));
    }
}
