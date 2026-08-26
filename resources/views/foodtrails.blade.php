<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ __('Food Trails') }} | {{ config('app.name', 'Warisan Makan') }}</title>
    <script>
        const googleMapsErrorMessage = @json(__('Google Maps rejected this API key. Check that Maps JavaScript API is enabled, billing is active, and your key restrictions allow this site.')); 

        window.googleMapsApiKey = @json(config('services.google.maps_api_key'));
        window.googleMapsLoaded = false;
        window._onGoogleMapsLoaded = function () {
            window.googleMapsLoaded = true;
            if (typeof window.initFoodTrailMap === 'function') window.initFoodTrailMap();
            if (typeof window.initStartTrailMap === 'function') window.initStartTrailMap();
        };
        window.gm_authFailure = function () {
            window.dispatchEvent(new CustomEvent('googleMapsError', { detail: googleMapsErrorMessage}));
        };
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if (config('services.google.maps_api_key'))
        <script async defer
            src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('services.google.maps_api_key')) }}&callback=_onGoogleMapsLoaded"></script>
    @endif
    <style>
        .wm-foodtrail-nav {
            position: sticky;
            top: 0;
            z-index: 30;
            display: flex;
            min-height: 68px;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 1px solid #E9D7BF;
            background: rgba(255, 255, 255, 0.88);
            padding: 12px clamp(16px, 4vw, 32px);
            backdrop-filter: blur(12px);
        }

        .wm-foodtrail-brand,
        .wm-foodtrail-links,
        .wm-foodtrail-links form {
            display: flex;
            align-items: center;
        }

        .wm-foodtrail-brand {
            gap: 12px;
            color: #B8874A;
            font-size: 18px;
            font-weight: 800;
            text-decoration: none;
        }

        .wm-foodtrail-brand-mark {
            display: inline-flex;
            width: 42px;
            height: 42px;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #FDE7CA;
            color: #B8874A;
        }

        .wm-foodtrail-links {
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .wm-foodtrail-links a,
        .wm-foodtrail-links button {
            border: 1px solid #E9D7BF;
            border-radius: 999px;
            background: #FFFFFF;
            color: #6B553F;
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: background-color 160ms ease, color 160ms ease;
        }

        .wm-foodtrail-links a.active,
        .wm-foodtrail-links a:hover,
        .wm-foodtrail-links button:hover {
            background: #B8874A;
            color: #FFFFFF;
        }
    </style>
</head>

<body class="bg-[#f8f3ed] text-[#1f1b19] min-h-screen">
    @include('partials.foodtrail-nav')
    <div class="max-w-6xl mx-auto px-4 py-6 lg:px-8">
        <header class="mb-8 rounded-[32px] bg-white p-6 shadow-[0_18px_40px_rgba(62,44,23,0.08)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-[0.32em] text-[#B8874A]">{{ __('Heritage food trails') }}</p>
                    <h1 class="mt-3 text-3xl font-semibold text-[#1F1B19]">{{ __('Explore and generate your next food trail') }}
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[#6B5B4B]">
                        {{ __('Pick a location, filter by category, and then use the map and vendor cards to navigate your trail step by step.') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ auth()->check() ? route('home') : url('/') }}"
                        class="inline-flex items-center rounded-full border border-[#D8B58F] bg-white px-4 py-2 text-sm font-semibold text-[#7A5F3A] shadow-sm hover:bg-[#F6EFE3]">{{ __('Back to Home') }}</a>
                </div>
            </div>
        </header>

        <section class="grid gap-6">
            <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm uppercase tracking-[0.35em] text-[#B8874A]">{{ __('Search your food trail') }}</p>
                        <h2 class="mt-3 text-3xl font-semibold text-[#1F1B19]">{{ __('Start by searching your location') }}</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-[#6B5B4B]">
                            {{ __('Enter a city or heritage district, choose a category, and generate a curated food trail with restaurant recommendations.') }}
                        </p>
                    </div>
                    <button id="generateTrailButton"
                        class="rounded-full bg-[#B8874A] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#9c6f33]">{{ __('Generate Trail') }} 
                    </button>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-[1.8fr_1fr]">
                    <div>
                        <input id="locationInput" list="locations"
                            class="w-full rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-4 text-sm text-[#1F1B19] shadow-sm outline-none"
                            placeholder="{{ __('Search location or heritage district') }}" />
                        <datalist id="locations">
                            <option value="Kuala Lumpur"></option>
                            <option value="Penang"></option>
                            <option value="Melaka"></option>
                            <option value="Johor Bahru"></option>
                            <option value="Ipoh"></option>
                        </datalist>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <select id="categorySelect"
                            class="rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-4 text-sm text-[#1F1B19] shadow-sm outline-none">
                            <option value="all">{{ __('All food categories') }}</option>
                            <option value="Street Food">{{ __('Street Food') }}</option>
                            <option value="Dessert">{{ __('Dessert') }}</option>
                            <option value="Seafood">{{ __('Seafood') }}</option>
                            <option value="Snacks">{{ __('Snacks') }}</option>
                        </select>
                        <input id="searchKeyword" type="search"
                            class="rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-4 text-sm text-[#1F1B19] shadow-sm outline-none"
                            placeholder="{{ __('Search restaurant or menu') }}" />
                    </div>
                </div>

                <div class="mt-4 rounded-3xl border border-[#E6D8C4] bg-[#FBF6F1] p-4 text-sm text-[#6B5B4B]">
                    <p id="selectedTrailSummary">
                        {{ __('Type a location and press Generate Trail to begin your food adventure.') }}
                    </p>
                </div>
            </div>

            <div id="initialPanel" class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">{{ __('Favorites') }}</p>
                            <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">{{ __('Saved food trails') }}</h2>
                        </div>
                        <button id="clearFavoritesButton"
                            class="rounded-full border border-[#D8B58F] bg-white px-4 py-2 text-sm font-semibold text-[#6B553F] transition hover:bg-[#f8efe5]">{{ __('Clear') }}</button>
                    </div>
                    <div id="favoritesList" class="mt-6 space-y-4"></div>
                </div>

                <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">{{ __('Curated') }}</p>
                            <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">{{ __('Curated trails') }}</h2>
                        </div>
                        <span
                            class="rounded-full bg-[#F7E4C1] px-3 py-1 text-sm font-semibold text-[#8A5A24]">{{ __('Recommended') }}</span>
                    </div>
                    <div id="curatedTrailCards" class="mt-6 space-y-4"></div>
                </div>
            </div>

            <section id="resultsPanel" class="hidden grid gap-6 lg:grid-cols-[1.3fr_0.95fr]">
                <div class="space-y-6">
                    <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">{{ __('Search results') }}</p>
                                <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">{{ __('Restaurant recommendations') }}</h2>
                            </div>
                            <span id="resultsCount"
                                class="rounded-full bg-[#F7E4C1] px-3 py-1 text-sm font-semibold text-[#8A5A24]">0
                                {{ __('restaurants') }}</span>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#5A5047]">{{ __('Category') }}</label>
                                <select id="categoryFilter"
                                    class="w-full rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-3 text-sm outline-none shadow-sm">
                                    <option value="all">{{ __('All categories') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#5A5047]">{{ __('Distance') }}</label>
                                <select id="distanceFilter"
                                    class="w-full rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-3 text-sm outline-none shadow-sm">
                                    <option value="all">{{ __('Any distance') }}</option>
                                    <option value="0.5">{{ __('Under 500m') }}</option>
                                    <option value="1">{{ __('Under 1 km') }}</option>
                                    <option value="2">{{ __('Under 2 km') }}</option>
                                    <option value="5">{{ __('Under 5 km') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#5A5047]">{{ __('Price') }}</label>
                                <select id="priceFilter"
                                    class="w-full rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-3 text-sm outline-none shadow-sm">
                                    <option value="all">{{ __('Any price') }}</option>
                                    <option value="RM 8 - RM 15">RM 8 - RM 15</option>
                                    <option value="RM 16 - RM 30">RM 16 - RM 30</option>
                                    <option value="RM 31 - RM 60">RM 31 - RM 60</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#5A5047]">{{ __('Reviews') }}</label>
                                <select id="reviewFilter"
                                    class="w-full rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-3 text-sm outline-none shadow-sm">
                                    <option value="all">{{ __('Any rating') }}</option>
                                    <option value="4">{{ __('4★ and up') }}</option>
                                    <option value="4.5">{{ __('4.5★ and up') }}</option>
                                    <option value="5">{{ __('5★ only') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3">
                            <button id="resetFiltersButton"
                                class="rounded-full border border-[#D8B58F] bg-white px-4 py-2 text-sm font-semibold text-[#6B553F] transition hover:bg-[#f8efe5]">{{ __('Reset filters') }}</button>
                            <p class="text-sm text-[#6B5B4B]">
                                {{ __('Use the filters to narrow your food trail by distance, price, category and review.') }}
                            </p>
                        </div>
                    </div>

                    <div id="restaurantList" class="space-y-4"></div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">{{ __('Route summary') }}</p>
                                <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">{{ __('Your food trail') }}</h2>
                            </div>
                            <span id="routeCompletion"
                                class="rounded-full bg-[#F7E4C1] px-3 py-1 text-sm font-semibold text-[#8A5A24]">0%
                                {{ __('complete') }}
                            </span>
                        </div>
                        <div id="routeSummary" class="mt-6 space-y-4 text-sm text-[#6B5B4B]"></div>
                        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <button id="startNowButton"
                                class="rounded-full bg-[#B8874A] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#9c6f33]">{{ __('Start now') }}</button>
                        </div>
                    </div>

                    <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">Map</p>
                                <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">Restaurant route</h2>
                            </div>
                            <span id="mapMarkerCount"
                                class="rounded-full bg-[#F7E4C1] px-3 py-1 text-sm font-semibold text-[#8A5A24]">0
                                markers</span>
                        </div>
                        <div id="mapContainer"
                            class="relative mt-6 aspect-[4/3] overflow-hidden rounded-[28px] border border-[#E8D4BE] bg-[#FBF6F1] shadow-inner">
                            <div
                                class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,210,146,0.35),_transparent_40%),radial-gradient(circle_at_bottom_right,_rgba(222,164,95,0.16),_transparent_35%)] pointer-events-none">
                            </div>
                            <div id="googleMap" class="absolute inset-0"></div>
                            <div id="googleMapMessage"
                                class="absolute inset-x-4 bottom-14 hidden rounded-2xl bg-white/95 px-4 py-3 text-sm text-[#6B5B4B] shadow-lg">
                            </div>
                            <div
                                class="absolute bottom-4 left-4 rounded-3xl bg-black/10 px-4 py-2 text-xs text-white backdrop-blur-sm">
                                Click a restaurant card to view route details.</div>
                        </div>
                    </div>

                    <div id="restaurantDetailDrawer"
                        class="transition-all duration-300 ease-in-out overflow-hidden max-h-0">
                        <div id="selectedRestaurantDetails"
                            class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">Restaurant details</p>
                                    <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">Selected restaurant</h2>
                                </div>
                                <button id="closeRestaurantDrawer"
                                    class="inline-flex items-center rounded-full border border-[#D8B58F] bg-white px-4 py-2 text-sm font-semibold text-[#6B553F] transition hover:bg-[#f8efe5]">Close</button>
                            </div>
                            <div id="restaurantDetailsContent" class="mt-6 text-sm text-[#6B5B4B]"></div>
                        </div>
                    </div>
                </div>
            </section>
        </section>
    </div>

    <script>
        window.foodTrailApp = {
            translations: {{ \Illuminate\Support\Js::from([
                'noSavedTrails' => __('No saved food trails yet. Generate a trail and save it as a favorite.'),
                'open' => __('Open'),
                'try' => __('Try'),
                'restaurants' => __('restaurants'),
                'allCategories' => __('All categories'),
                'noRestaurantMatches' => __('No restaurants match the selected filters. Try another filter or location.'),
                'added' => __('Added'),
                'add' => __('+ Add'),
                'pickRestaurant' => __('Pick a restaurant'),
                'pickRestaurantHelp' => __('Select a restaurant from the list to see details, add to your trail, or mark it as visited.'),
                'removeFromTrail' => __('Remove from trail'),
                'addToTrail' => __('+ Add to trail'),
                'visited' => __('Visited'),
                'markVisited' => __('Mark visited'),
                'liked' => __('Liked'),
                'love' => __('Love'),
                'complete' => __('complete'),
                'addRestaurantsToTrail' => __('Add restaurants to your food trail and get a simple route plan with estimated travel time.'),
                'totalEstimatedJourneyTime' => __('Total estimated journey time'),
                'pending' => __('Pending'),
                'nextTravelTime' => __('Next travel time: :value min'),
                'enterLocation' => __('Please enter a location before generating.'),
                'noLocationRestaurants' => __('No restaurants found for this location. Try another city.'),
                'showingRestaurants' => __('Showing :count restaurants in :location. Use filters to refine the list.'),
                'noCriteriaRestaurants' => __('No restaurants match your criteria. Adjust the filters to see more results.'),
                'clearSavedTrails' => __('Clear all saved favourite trails?'),
                'mapNotConfigured' => __('Google Maps is not configured. Add GOOGLE_MAPS_API_KEY to your .env file and reload.'),
                'mapLoadFailed' => __('Google Maps could not be loaded.'),
            ]) }},
            locations: [
                'Kuala Lumpur',
                'Penang',
                'Melaka',
                'Johor Bahru',
                'Ipoh',
            ],
            categories: ['all', 'Street Food', 'Dessert', 'Seafood', 'Snacks'],
            defaultFavorites: [
                { id: 'fav-1', title: 'KL Heritage Walk', location: 'Kuala Lumpur', description: 'A classic route for local favorites and street food.', tags: ['Street Food', 'Local'], stops: 3 },
                { id: 'fav-2', title: 'Penang Sweet Tour', location: 'Penang', description: 'A dessert-focused trail for local heritage sweets.', tags: ['Dessert', 'Heritage'], stops: 3 },
            ],
            curated: [
                { id: 'curated-1', title: 'Weekend Food Trail', subtitle: 'Quick heritage route', summary: 'Perfect for a short heritage food adventure with top local vendors.', category: 'All', location: 'Kuala Lumpur' },
                { id: 'curated-2', title: 'Seafood & Street Bites', subtitle: 'Best of the shore', summary: 'A curated set of the best seafood and street snack options.', category: 'Seafood', location: 'Melaka' },
            ],
            restaurants: {
                'Kuala Lumpur': [
                    { id: 'kl-1', name: 'Nasi Lemak Warisan', category: 'Street Food', location: 'Bukit Bintang', rating: 4.7, reviewCount: 218, price: 'RM 8 - RM 15', distance: 0.4, waitTime: '15 min', tags: ['Local', 'Spicy'], description: 'Iconic coconut rice with sambal, chicken, and crispy anchovies.', picture: 'https://images.unsplash.com/photo-1543353071-873f17a7a088?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 18, y: 28 } },
                    { id: 'kl-2', name: 'Satay Stall Jaya', category: 'Street Food', location: 'Jalan Alor', rating: 4.8, reviewCount: 312, price: 'RM 16 - RM 30', distance: 0.9, waitTime: '20 min', tags: ['Grilled', 'Night Market'], description: 'Charcoal satay with rich peanut sauce and local rice cakes.', picture: 'https://images.unsplash.com/photo-1542219550-c1f36a97a991?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 48, y: 38 } },
                    { id: 'kl-3', name: 'Roti Canai Corner', category: 'Snacks', location: 'Imbi', rating: 4.5, reviewCount: 184, price: 'RM 8 - RM 15', distance: 1.2, waitTime: '10 min', tags: ['Comfort Food', 'Quick'], description: 'Crispy roti canai served with dhal and curry on the side.', picture: 'https://images.unsplash.com/photo-1534939561126-855b8675edd7?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 68, y: 22 } },
                    { id: 'kl-4', name: 'Cendol Heritage', category: 'Dessert', location: 'Chinatown', rating: 4.9, reviewCount: 259, price: 'RM 8 - RM 15', distance: 1.6, waitTime: '12 min', tags: ['Sweet', 'Traditional'], description: 'Classic shaved ice dessert with gula Melaka and pandan jelly.', picture: 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 27, y: 64 } },
                ],
                Penang: [
                    { id: 'pg-1', name: 'Char Koay Teow Master', category: 'Street Food', location: 'Lebuh Cintra', rating: 4.7, reviewCount: 198, price: 'RM 16 - RM 30', distance: 0.3, waitTime: '18 min', tags: ['Fiery', 'Popular'], description: 'Stir-fried flat rice noodles prepared over high heat with smoky flavor.', picture: 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 20, y: 18 } },
                    { id: 'pg-2', name: 'Penang Laksa Corner', category: 'Seafood', location: 'Lebuh Keng Kwee', rating: 4.6, reviewCount: 154, price: 'RM 8 - RM 15', distance: 0.8, waitTime: '15 min', tags: ['Heritage', 'Sour'], description: 'Asam laksa with tangy fish broth, noodles and fresh herbs.', picture: 'https://images.unsplash.com/photo-1553621042-f6e147245754?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 45, y: 42 } },
                    { id: 'pg-3', name: 'Cendol House', category: 'Dessert', location: 'Lebuh Armenian', rating: 4.5, reviewCount: 212, price: 'RM 8 - RM 15', distance: 1.0, waitTime: '10 min', tags: ['Chilled', 'Sweet'], description: 'Refreshing cendol topped with coconut milk and palm sugar.', picture: 'https://images.unsplash.com/photo-1544511916-0148ccdeb877?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 62, y: 28 } },
                ],
                Melaka: [
                    { id: 'mk-1', name: 'Nyonya Laksa Stall', category: 'Seafood', location: 'Jonker Walk', rating: 4.8, reviewCount: 173, price: 'RM 16 - RM 30', distance: 0.5, waitTime: '22 min', tags: ['Spicy', 'Heritage'], description: 'Rich coconut laksa with local noodles and herbs.', picture: 'https://images.unsplash.com/photo-1478145046317-39f10e56b5e9?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 22, y: 30 } },
                    { id: 'mk-2', name: 'Putu Piring Cart', category: 'Dessert', location: 'Jonker Walk', rating: 4.6, reviewCount: 139, price: 'RM 8 - RM 15', distance: 0.6, waitTime: '8 min', tags: ['Sweet', 'Local'], description: 'Steamed rice cakes with palm sugar and grated coconut.', picture: 'https://images.unsplash.com/photo-1498654896293-37aacf113fd9?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 52, y: 33 } },
                    { id: 'mk-3', name: 'Satay Celup Booth', category: 'Street Food', location: 'A Famosa', rating: 4.4, reviewCount: 121, price: 'RM 16 - RM 30', distance: 2.2, waitTime: '20 min', tags: ['Unique', 'Shared'], description: 'Skewers dipped into flavorful peanut broth to cook at the table.', picture: 'https://images.unsplash.com/photo-1458642849426-cfb724f15ef7?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 70, y: 60 } },
                ],
                'Johor Bahru': [
                    { id: 'jb-1', name: 'Laksa Johor Corner', category: 'Street Food', location: 'Stulang', rating: 4.6, reviewCount: 142, price: 'RM 16 - RM 30', distance: 0.7, waitTime: '16 min', tags: ['Spicy', 'Local'], description: 'Rich fish laksa with coconut and local noodles.', picture: 'https://images.unsplash.com/photo-1543353071-873f17a7a088?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 24, y: 34 } },
                    { id: 'jb-2', name: 'Kueh Tutu Stand', category: 'Snacks', location: 'City Square', rating: 4.5, reviewCount: 98, price: 'RM 8 - RM 15', distance: 1.1, waitTime: '12 min', tags: ['Sweet', 'Bite-size'], description: 'Steamed rice cakes filled with coconut and peanut.', picture: 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 42, y: 58 } },
                    { id: 'jb-3', name: 'BBQ Seafood Walk', category: 'Seafood', location: 'Permas Jaya', rating: 4.7, reviewCount: 176, price: 'RM 16 - RM 30', distance: 2.8, waitTime: '25 min', tags: ['Grilled', 'Ocean'], description: 'Fresh seafood grilled over charcoal with local sauces.', picture: 'https://images.unsplash.com/photo-1553621042-f6e147245754?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 68, y: 24 } },
                ],
                Ipoh: [
                    { id: 'ip-1', name: 'Bean Sprout Chicken', category: 'Street Food', location: 'Old Town', rating: 4.8, reviewCount: 204, price: 'RM 16 - RM 30', distance: 0.3, waitTime: '18 min', tags: ['Classic', 'Savory'], description: 'Poached chicken and rice with crunchy bean sprouts.', picture: 'https://images.unsplash.com/photo-1478145046317-39f10e56b5e9?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 18, y: 26 } },
                    { id: 'ip-2', name: 'White Coffee Cafe', category: 'Snacks', location: 'Jalan Bandar Timah', rating: 4.5, reviewCount: 168, price: 'RM 8 - RM 15', distance: 0.9, waitTime: '10 min', tags: ['Coffee', 'Relaxed'], description: 'Smooth local white coffee served with kaya toast.', picture: 'https://images.unsplash.com/photo-1544511916-0148ccdeb877?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 45, y: 48 } },
                    { id: 'ip-3', name: 'Tau Fu Fah Corner', category: 'Dessert', location: 'Jalan Sultan Iskandar', rating: 4.4, reviewCount: 130, price: 'RM 8 - RM 15', distance: 1.7, waitTime: '9 min', tags: ['Soft', 'Sweet'], description: 'Silky tofu pudding with sweet ginger syrup.', picture: 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&w=1200&q=80', coordinates: { x: 73, y: 32 } },
                ],
            },
            favoritesKey: 'foodtrails-favorites',
            likedRestaurantsKey: 'foodtrail-liked-restaurants',
        };
    </script>
</body>

</html>
