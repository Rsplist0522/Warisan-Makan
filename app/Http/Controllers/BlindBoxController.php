<?php


namespace App\Http\Controllers;


use App\Models\BlindBoxDraw;
use App\Models\HeritageShop;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;


class BlindBoxController extends Controller
{
    public function index(Request $request)
    {
        $period = $this->currentPeriod();
        $shops = $this->loadShops();
        $categories = collect($shops)->pluck('category')->filter()->unique()->values()->all();
        $selectedCategory = trim((string) $request->query('category', ''));


        if ($selectedCategory !== '') {
            $shops = array_values(array_filter($shops, fn (array $shop): bool => $shop['category'] === $selectedCategory));
        }


        $alreadyDrew = $this->hasDrawnThisPeriod($request, $period);
        $currentDraw = $request->session()->get("blind_box_draws.{$period}");


        return view('blind-box.index', compact(
            'shops',
            'categories',
            'selectedCategory',
            'period',
            'alreadyDrew',
            'currentDraw'
        ));
    }


    public function draw(Request $request)
    {
        $period = $this->currentPeriod();


        if ($this->hasDrawnThisPeriod($request, $period)) {
            return response()->json([
                'error' => 'You can only draw once per ' . $period . '. Come back next period for another surprise.',
            ], 429);
        }


        $shops = $this->loadShops(trim((string) $request->query('category', '')));


        if (count($shops) === 0) {
            return response()->json([
                'error' => 'No surprise shops are available for the selected category.',
            ], 422);
        }


        $shop = $shops[array_rand($shops)];


        $drawData = [
            'period' => $period,
            'shop_name' => $shop['name'],
            'category' => $shop['category'] ?? null,
            'state' => $shop['state'] ?? null,
            'year' => $shop['year'] ?? null,
            'description' => $shop['description'] ?? null,
            'image' => $shop['image'] ?? null,
        ];


        // Only write to the DB if the table actually exists yet.
        if (Auth::check() && Schema::hasTable('blind_box_draws')) {
            BlindBoxDraw::create(array_merge($drawData, [
                'user_id' => Auth::id(),
            ]));
        }


        // Session fallback always runs, so guests (and logged-in users when the
        // table is missing) still get "already drew today" behaviour and history.
        $request->session()->put("blind_box_draws.{$period}", array_merge($drawData, [
            'drawn_at' => Carbon::now()->toDateTimeString(),
        ]));


        return response()->json([
            'shop' => $shop,
            'period' => $period,
            'history_url' => route('blind-box.history'),
        ]);
    }


    public function history(Request $request)
    {
        if (Auth::check() && Schema::hasTable('blind_box_draws')) {
            $drawHistory = BlindBoxDraw::where('user_id', Auth::id())
                ->latest()
                ->get()
                ->map(fn (BlindBoxDraw $draw) => [
                    'period' => $draw->period,
                    'shop_name' => $draw->shop_name,
                    'category' => $draw->category,
                    'state' => $draw->state,
                    'year' => $draw->year,
                    'description' => $draw->description,
                    'image' => $draw->image,
                    'drawn_at' => $draw->created_at->toDateTimeString(),
                ])
                ->all();


            // Still empty (e.g. brand new account)? Show dummy data so the UI isn't blank.
            if (empty($drawHistory)) {
                $drawHistory = $this->dummyDrawHistory();
            }
        } elseif (Auth::check()) {
            // Table missing but user is logged in: show dummy data rather than crash.
            $drawHistory = $this->dummyDrawHistory();
        } else {
            $drawHistory = collect($request->session()->get('blind_box_draws', []))
                ->map(fn (array $draw) => [
                    'period' => $draw['period'] ?? '',
                    'shop_name' => $draw['shop_name'] ?? '',
                    'category' => $draw['category'] ?? '',
                    'state' => $draw['state'] ?? '',
                    'year' => $draw['year'] ?? '',
                    'description' => $draw['description'] ?? '',
                    'image' => $draw['image'] ?? '',
                    'drawn_at' => $draw['drawn_at'] ?? '',
                ])
                ->sortByDesc('drawn_at')
                ->values()
                ->all();


            if (empty($drawHistory)) {
                $drawHistory = $this->dummyDrawHistory();
            }
        }


        return view('blind-box.history', [
            'drawHistory' => $drawHistory,
        ]);
    }


    private function dummyDrawHistory(): array
    {
        $now = Carbon::now();


        return [
            [
                'period' => 'morning',
                'shop_name' => 'Kedai Kopi Pak Hassan',
                'category' => 'Coffee & Breakfast',
                'state' => 'Selangor',
                'year' => '1987',
                'description' => 'A heritage coffee house known for its hand-brewed local favourites and warm stories from the founder.',
                'image' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=900&q=80',
                'drawn_at' => $now->copy()->subDay()->setTime(9, 15)->toDateTimeString(),
            ],
            [
                'period' => 'afternoon',
                'shop_name' => 'Nasi Lemak Seri Warisan',
                'category' => 'Main Course',
                'state' => 'Penang',
                'year' => '1974',
                'description' => 'A beloved old-school stall serving fragrant nasi lemak with recipes passed down through generations.',
                'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80',
                'drawn_at' => $now->copy()->subDay()->setTime(13, 40)->toDateTimeString(),
            ],
            [
                'period' => 'evening',
                'shop_name' => 'Kampung Kuih Mak Cik',
                'category' => 'Dessert',
                'state' => 'Johor',
                'year' => '1992',
                'description' => 'A small family-run kitchen celebrated for colourful traditional kuih and seasonal festival treats.',
                'image' => 'https://images.unsplash.com/photo-1482049016688-2d3e1b311543?auto=format&fit=crop&w=900&q=80',
                'drawn_at' => $now->copy()->subDays(2)->setTime(18, 5)->toDateTimeString(),
            ],
        ];
    }


    private function loadShops(string $category = ''): array
    {
        if (!Schema::hasTable('heritage_shops')) {
            return $this->sampleShops();
        }


        $shops = HeritageShop::where('publish_status', 'approved')
            ->with('media')
            ->get()
            ->map(fn (HeritageShop $shop) => [
                'name' => $shop->shop_name,
                'description' => $shop->heritage_story ?: $shop->current_owner_details ?: 'A heritage discovery with an enduring local story.',
                'category' => $shop->primary_food_category ?: 'Heritage',
                'state' => $shop->state ?: $shop->city ?: 'Malaysia',
                'year' => $shop->establishment_year ? (string) $shop->establishment_year : 'Heritage',
                'image' => $this->shopImage($shop),
            ])
            ->all();


        if (empty($shops)) {
            $shops = $this->sampleShops();
        }


        if ($category !== '') {
            $shops = array_values(array_filter($shops, fn (array $shop): bool => $shop['category'] === $category));
        }


        return $shops;
    }


    private function shopImage(HeritageShop $shop): string
    {
        $media = $shop->media->firstWhere('is_primary', true) ?? $shop->media->first();

        if ($media) {
            return $media->url;
        }

        return 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80';
    }


    private function sampleShops(): array
    {
        return [
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
    }


    private function currentPeriod(): string
    {
        $hour = Carbon::now()->hour;


        if ($hour >= 5 && $hour < 11) {
            return 'morning';
        }


        if ($hour >= 11 && $hour < 17) {
            return 'afternoon';
        }


        if ($hour >= 17 && $hour < 21) {
            return 'evening';
        }


        return 'night';
    }


    private function hasDrawnThisPeriod(Request $request, string $period): bool
    {
        if (Auth::check() && Schema::hasTable('blind_box_draws')) {
            return BlindBoxDraw::where('user_id', Auth::id())
                ->where('period', $period)
                ->whereDate('created_at', Carbon::today())
                ->exists();
        }


        $draw = $request->session()->get("blind_box_draws.{$period}");


        if (!is_array($draw)) {
            return false;
        }


        return isset($draw['drawn_at']) && Carbon::parse($draw['drawn_at'])->isToday();
    }
}
