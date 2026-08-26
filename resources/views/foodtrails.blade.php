<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Food Trails | {{ config('app.name', 'Warisan Makan') }}</title>
    <script>
        window.googleMapsApiKey = @json(config('services.google.maps_api_key'));
        window.googleMapsLoaded = false;
        window._onGoogleMapsLoaded = function () {
            window.googleMapsLoaded = true;
            if (typeof window.initFoodTrailMap === 'function') window.initFoodTrailMap();
            if (typeof window.initStartTrailMap === 'function') window.initStartTrailMap();
        };
        window.gm_authFailure = function () {
            window.dispatchEvent(new CustomEvent('googleMapsError', { detail: 'Google Maps rejected this API key. Check that Maps JavaScript API is enabled, billing is active, and your key restrictions allow this site.' }));
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
                    <p class="text-sm uppercase tracking-[0.32em] text-[#B8874A]">Heritage food trails</p>
                    <h1 class="mt-3 text-3xl font-semibold text-[#1F1B19]">Explore and generate your next food trail
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[#6B5B4B]">Pick a location, filter by category, and
                        then use the map and vendor cards to navigate your trail step by step.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ auth()->check() ? route('home') : url('/') }}"
                        class="inline-flex items-center rounded-full border border-[#D8B58F] bg-white px-4 py-2 text-sm font-semibold text-[#7A5F3A] shadow-sm hover:bg-[#F6EFE3]">Back
                        to Home</a>
                </div>
            </div>
        </header>

        <section class="grid gap-6">
            <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm uppercase tracking-[0.35em] text-[#B8874A]">Search your food trail</p>
                        <h2 class="mt-3 text-3xl font-semibold text-[#1F1B19]">Start by searching your location</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-[#6B5B4B]">Enter a city or heritage district,
                            choose a category, and generate a curated food trail with restaurant recommendations.</p>
                    </div>
                    <button id="generateTrailButton"
                        class="rounded-full bg-[#B8874A] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#9c6f33]">Generate
                        Trail</button>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-[1.8fr_1fr]">
                    <div>
                        <input id="locationInput" list="locations"
                            class="w-full rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-4 text-sm text-[#1F1B19] shadow-sm outline-none"
                            placeholder="Search location or heritage district" />
                        <datalist id="locations">
                            @foreach ($locations as $location)
                                <option value="{{ $location }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <select id="categorySelect"
                            class="rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-4 text-sm text-[#1F1B19] shadow-sm outline-none">
                            <option value="all">All food categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                        <input id="searchKeyword" type="search"
                            class="rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-4 text-sm text-[#1F1B19] shadow-sm outline-none"
                            placeholder="Search restaurant or menu" />
                    </div>
                </div>

                <div class="mt-4 rounded-3xl border border-[#E6D8C4] bg-[#FBF6F1] p-4 text-sm text-[#6B5B4B]">
                    <p id="selectedTrailSummary">Type a location and press Generate Trail to begin your food adventure.
                    </p>
                </div>
            </div>

            <div id="initialPanel" class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">Favorites</p>
                            <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">Saved food trails</h2>
                        </div>
                        <button id="clearFavoritesButton"
                            class="rounded-full border border-[#D8B58F] bg-white px-4 py-2 text-sm font-semibold text-[#6B553F] transition hover:bg-[#f8efe5]">Clear</button>
                    </div>
                    <div id="favoritesList" class="mt-6 space-y-4"></div>
                </div>

                <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">Curated</p>
                            <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">Curated trails</h2>
                        </div>
                        <span
                            class="rounded-full bg-[#F7E4C1] px-3 py-1 text-sm font-semibold text-[#8A5A24]">Recommended</span>
                    </div>
                    <div id="curatedTrailCards" class="mt-6 space-y-4"></div>
                </div>
            </div>

            <section id="resultsPanel" class="hidden grid gap-6 lg:grid-cols-[1.3fr_0.95fr]">
                <div class="space-y-6">
                    <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">Search results</p>
                                <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">Restaurant recommendations</h2>
                            </div>
                            <span id="resultsCount"
                                class="rounded-full bg-[#F7E4C1] px-3 py-1 text-sm font-semibold text-[#8A5A24]">0
                                restaurants</span>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#5A5047]">Category</label>
                                <select id="categoryFilter"
                                    class="w-full rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-3 text-sm outline-none shadow-sm">
                                    <option value="all">All categories</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#5A5047]">Distance</label>
                                <select id="distanceFilter"
                                    class="w-full rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-3 text-sm outline-none shadow-sm">
                                    <option value="all">Any distance</option>
                                    <option value="0.5">Under 500m</option>
                                    <option value="1">Under 1 km</option>
                                    <option value="2">Under 2 km</option>
                                    <option value="5">Under 5 km</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#5A5047]">Price</label>
                                <select id="priceFilter"
                                    class="w-full rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-3 text-sm outline-none shadow-sm">
                                    <option value="all">Any price</option>
                                    <option value="RM 8 - RM 15">RM 8 - RM 15</option>
                                    <option value="RM 16 - RM 30">RM 16 - RM 30</option>
                                    <option value="RM 31 - RM 60">RM 31 - RM 60</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#5A5047]">Reviews</label>
                                <select id="reviewFilter"
                                    class="w-full rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-3 text-sm outline-none shadow-sm">
                                    <option value="all">Any rating</option>
                                    <option value="4">4★ and up</option>
                                    <option value="4.5">4.5★ and up</option>
                                    <option value="5">5★ only</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3">
                            <button id="resetFiltersButton"
                                class="rounded-full border border-[#D8B58F] bg-white px-4 py-2 text-sm font-semibold text-[#6B553F] transition hover:bg-[#f8efe5]">Reset
                                filters</button>
                            <p class="text-sm text-[#6B5B4B]">Use the filters to narrow your food trail by distance,
                                price, category and review.</p>
                        </div>
                    </div>

                    <div id="restaurantList" class="space-y-4"></div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">Route summary</p>
                                <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">Your food trail</h2>
                            </div>
                            <span id="routeCompletion"
                                class="rounded-full bg-[#F7E4C1] px-3 py-1 text-sm font-semibold text-[#8A5A24]">0%
                                complete</span>
                        </div>
                        <div id="routeSummary" class="mt-6 space-y-4 text-sm text-[#6B5B4B]"></div>
                        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <button id="startNowButton"
                                class="rounded-full bg-[#B8874A] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#9c6f33]">Start
                                now</button>
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
            locations: @json($locations),
            categories: @json(array_merge(['all'], $categories)),
            defaultFavorites: [
                { id: 'fav-1', title: 'KL Heritage Walk', location: 'Kuala Lumpur', description: 'A classic route for local favorites and street food.', tags: ['Street Food', 'Local'], stops: 3 },
                { id: 'fav-2', title: 'Penang Sweet Tour', location: 'Penang', description: 'A dessert-focused trail for local heritage sweets.', tags: ['Dessert', 'Heritage'], stops: 3 },
            ],
            curated: @json($curatedSuggestions),
            restaurants: @json($restaurants),
            favoritesKey: 'foodtrails-favorites',
            likedRestaurantsKey: 'foodtrail-liked-restaurants',
        };
    </script>
</body>

</html>
