<?php

namespace App\Http\Controllers;

use App\Models\FoodTrailSuggestion;
use App\Models\HeritageShop;
use Illuminate\Support\Collection;

class FoodTrailController extends Controller
{
    public function index()
    {
        // Only published restaurants are available to users in Food Trails.
        $shops = HeritageShop::published()
            ->with('images')
            ->latest('updated_at')
            ->get();

        $restaurants = $shops
            ->map(fn (HeritageShop $shop): array => $this->restaurant($shop))
            ->groupBy('trailLocation')
            ->map(fn (Collection $items): array => $items->map(function (array $item): array {
                unset($item['trailLocation']);

                return $item;
            })->values()->all())
            ->all();

        $locations = array_keys($restaurants);
        $categories = $shops->pluck('primary_food_category')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return view('foodtrails', [
            'restaurants' => $restaurants,
            'locations' => $locations,
            'categories' => $categories,
            'curatedSuggestions' => FoodTrailSuggestion::query()
                ->where('is_published', true)
                ->with('restaurants.images')->latest()->get()
                ->map(function (FoodTrailSuggestion $trail): array { return ['id'=>$trail->id,'title'=>$trail->title,'subtitle'=>$trail->subtitle ?: $trail->summary,'summary'=>$trail->summary,'category'=>$trail->category,'location'=>$trail->location,'restaurants'=>$trail->restaurants->map(fn (HeritageShop $shop) => $this->restaurant($shop))->values()->all()]; }),
        ]);
    }

    private function restaurant(HeritageShop $shop): array
    {
        $primaryImage = $shop->images->first();
        $location = implode(', ', array_filter([$shop->address, $shop->city, $shop->state]));

        return [
            'id' => 'shop-'.$shop->id,
            'name' => $shop->shop_name,
            'category' => $shop->primary_food_category ?: 'Heritage Food',
            'location' => $location ?: 'Location details not listed',
            'trailLocation' => $this->trailLocation($shop),
            'rating' => 0,
            'reviewCount' => 0,
            'price' => 'Price not listed',
            'distance' => 0,
            'waitTime' => 'Not listed',
            'tags' => array_values(array_filter([$shop->primary_food_category, $shop->establishment_year ? 'Since '.$shop->establishment_year : null])),
            'description' => $shop->heritage_story ?: $shop->highlight ?: $shop->current_owner_details ?: 'Explore this heritage food destination.',
            'picture' => $primaryImage
                ? route('heritage-shops.images.show', ['heritageShop' => $shop->id, 'image' => $primaryImage->id])
                : 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80',
            'coordinates' => [
                'lat' => $shop->latitude,
                'lng' => $shop->longitude,
            ],
        ];
    }

    /**
     * Keep location search city-based. Imported records use several labels for
     * Kuala Lumpur (and sometimes put the postcode in the city column).
     */
    private function trailLocation(HeritageShop $shop): string
    {
        $locationData = implode(' ', array_filter([
            $shop->address,
            $shop->city,
            $shop->state,
            $shop->postal_code,
        ]));
        $normalized = mb_strtolower($locationData);

        if (
            str_contains($normalized, 'kuala lumpur')
            || str_contains($normalized, 'wilayah persekutuan')
            || str_contains($normalized, 'federal territory of kuala lumpur')
            || preg_match('/\b(?:5[0-9]{4}|60[0-9]{3})\b/', $locationData)
        ) {
            return 'Kuala Lumpur';
        }

        $city = trim((string) $shop->city);

        return $city !== '' && mb_strtolower($city) !== 'malaysia'
            ? $city
            : 'Other locations';
    }
}
