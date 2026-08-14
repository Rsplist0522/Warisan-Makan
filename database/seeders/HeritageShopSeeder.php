<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HeritageShop;

class HeritageShopSeeder extends Seeder
{
    public function run(): void
    {
        $shops = [
            [
                'name' => 'Restoran Warisan Selera',
                'location' => 'Kuala Lumpur',
                'country' => 'Malaysia',
                'category' => 'Traditional Noodles',
                'description' => 'A family-run noodle house serving heirloom recipes passed through three generations.',
                'founder' => 'Ahmad Rahman',
                'establishment_year' => '1968',
                'heritage_story' => 'Started by a single-carriage hawker, recipes were preserved and taught within the family.',
                'operating_hours' => 'Tue–Sun 09:00–18:00',
                'participating_since' => '2018',
                'highlight' => 'Known for hand-pulled noodles and heritage sambal.',
                'source_url' => 'https://example.com/restoran-warisan-selera',
            ],
            [
                'name' => 'Satay House Batu Pahat',
                'location' => 'Johor Bahru',
                'country' => 'Malaysia',
                'category' => 'Street Food',
                'description' => 'A heritage satay stall that has been serving locals since the 1970s.',
                'founder' => 'Haji Musa',
                'establishment_year' => '1974',
                'heritage_story' => 'A roadside stall that grew a loyal following for its marinade and charcoal technique.',
                'operating_hours' => 'Daily 17:00–23:00',
                'participating_since' => '2015',
                'highlight' => 'Signature satay and charcoal-grilled skewers.',
                'source_url' => 'https://example.com/satay-house-batu-pahat',
            ],
            [
                'name' => 'Kampung Desserts Hub',
                'location' => 'Penang',
                'country' => 'Malaysia',
                'category' => 'Desserts',
                'description' => 'A dessert boutique featuring traditional kuih and syrup recipes from the village.',
                'founder' => 'Puan Siti',
                'establishment_year' => '1986',
                'heritage_story' => 'Recipes collected from neighbouring kampungs and adapted to a boutique setting.',
                'operating_hours' => 'Wed–Sun 10:00–16:00',
                'participating_since' => '2021',
                'highlight' => 'Popular for kuih lapis and gula melaka treats.',
                'source_url' => 'https://example.com/kampung-desserts-hub',
            ],
            [
                'name' => 'Makan Tradisi Kuantan',
                'location' => 'Pahang',
                'country' => 'Malaysia',
                'category' => 'Rice Dishes',
                'description' => 'A heritage rice shop serving classic dishes and old-fashioned hospitality.',
                'founder' => 'Liang & Family',
                'establishment_year' => '1959',
                'heritage_story' => 'A multi-ethnic family-run kitchen that blended recipes across generations.',
                'operating_hours' => 'Mon–Sat 08:00–15:00',
                'participating_since' => '2017',
                'highlight' => 'Famous for nasi dagang and slow-cooked curries.',
                'source_url' => 'https://example.com/makan-tradisi-kuantan',
            ],
            [
                'name' => 'Melaka Heritage Kopitiam',
                'location' => 'Melaka',
                'country' => 'Malaysia',
                'category' => 'Coffee & Cakes',
                'description' => 'A kopitiam preserving old recipes and colonial-style kopi culture.',
                'founder' => 'Mr. Tan',
                'establishment_year' => '1978',
                'heritage_story' => 'Founded to keep traditional coffee shop customs alive in the modern city.',
                'operating_hours' => 'Daily 08:00–20:00',
                'participating_since' => '2014',
                'highlight' => 'Authentic charcoal-brewed coffee and kaya toast.',
                'source_url' => 'https://example.com/melaka-heritage-kopitiam',
            ],
        ];

        foreach ($shops as $shop) {
            HeritageShop::updateOrCreate([
                'source_url' => $shop['source_url'],
            ], $shop);
        }
    }
}
