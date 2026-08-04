<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BlindBoxController extends Controller
{
    public function index()
    {
        $shops = [
            [
                'name' => 'Kedai Kopi Pak Hassan',
                'description' => 'A heritage coffee house known for its hand-brewed local favourites and warm stories from the founder.',
                'category' => 'Coffee & Breakfast',
                'state' => 'Selangor',
                'year' => '1987',
                'image' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'name' => 'Nasi Lemak Seri Warisan',
                'description' => 'A beloved old-school stall serving fragrant nasi lemak with recipes passed down through generations.',
                'category' => 'Main Course',
                'state' => 'Penang',
                'year' => '1974',
                'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'name' => 'Kampung Kuih Mak Cik',
                'description' => 'A small family-run kitchen celebrated for colourful traditional kuih and seasonal festival treats.',
                'category' => 'Dessert',
                'state' => 'Johor',
                'year' => '1992',
                'image' => 'https://images.unsplash.com/photo-1482049016688-2d3e1b311543?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'name' => 'Restoran Warisan Rasa',
                'description' => 'A heritage restaurant that preserves old recipes with a contemporary dining experience.',
                'category' => 'Cuisine',
                'state' => 'Kelantan',
                'year' => '1965',
                'image' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=80',
            ],
        ];

        return view('blind-box.index', compact('shops'));
    }

    public function draw(Request $request)
    {
        $shops = [
            [
                'name' => 'Kedai Kopi Pak Hassan',
                'description' => 'A heritage coffee house known for its hand-brewed local favourites and warm stories from the founder.',
                'category' => 'Coffee & Breakfast',
                'state' => 'Selangor',
                'year' => '1987',
                'image' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'name' => 'Nasi Lemak Seri Warisan',
                'description' => 'A beloved old-school stall serving fragrant nasi lemak with recipes passed down through generations.',
                'category' => 'Main Course',
                'state' => 'Penang',
                'year' => '1974',
                'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'name' => 'Kampung Kuih Mak Cik',
                'description' => 'A small family-run kitchen celebrated for colourful traditional kuih and seasonal festival treats.',
                'category' => 'Dessert',
                'state' => 'Johor',
                'year' => '1992',
                'image' => 'https://images.unsplash.com/photo-1482049016688-2d3e1b311543?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'name' => 'Restoran Warisan Rasa',
                'description' => 'A heritage restaurant that preserves old recipes with a contemporary dining experience.',
                'category' => 'Cuisine',
                'state' => 'Kelantan',
                'year' => '1965',
                'image' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=80',
            ],
        ];

        $shop = $shops[array_rand($shops)];

        return response()->json([
            'shop' => $shop,
        ]);
    }

    public function history()
    {
        return view('blind-box.history');
    }
}
