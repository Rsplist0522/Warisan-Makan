@extends('layouts.user')

@section('title', __('Food Trails'))
@section('user-topbar-title', __('Food Trail'))
@section('user-topbar-subtitle', __('Generate and save curated routes to heritage food spots.'))
@section('body-class', 'bg-[#f8f3ed] text-[#1f1b19] min-h-screen')

@section('user-topbar-actions')
<a class="user-topbar-link" href="{{ url('/start_trail') }}">{{ __('Current Trail') }}</a>
<a class="user-topbar-link" href="{{ route('passport.index') }}">{{ __('Food Passport') }}</a>
@endsection
@push('head-scripts')
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
            window.dispatchEvent(new CustomEvent('googleMapsError', { detail: googleMapsErrorMessage }));
        };
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if (config('services.google.maps_api_key'))
        <script async defer
            src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('services.google.maps_api_key')) }}&callback=_onGoogleMapsLoaded"></script>
    @endif
@endpush

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 lg:px-8">
        @if (request('trail') === 'empty')
            <div class="mb-6 flex items-start gap-3 rounded-2xl border border-[#E5B8A5] bg-[#FFF2EA] px-5 py-4 text-sm text-[#7D2E1E] shadow-sm" role="alert">
                <span class="mt-0.5 text-base" aria-hidden="true">!</span>
                <p>{{ __('Your current trail is empty. Generate a trail and add at least one restaurant to continue.') }}</p>
            </div>
        @endif
        <header class="mb-8 rounded-[32px] bg-white p-6 shadow-[0_18px_40px_rgba(62,44,23,0.08)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-[0.32em] text-[#B8874A]">{{ __('Heritage food trails') }}</p>
                    <h1 class="mt-3 text-3xl font-semibold text-[#1F1B19]">
                        {{ __('Explore and generate your next food trail') }}
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
                        <p class="text-sm uppercase tracking-[0.35em] text-[#B8874A]">{{ __('Search your food trail') }}
                        </p>
                        <h2 class="mt-3 text-3xl font-semibold text-[#1F1B19]">
                            {{ __('Find restaurants and locations') }}
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-[#6B5B4B]">
                            {{ __('Search by restaurant name, location, or both, then choose a category to generate your food trail.') }}
                        </p>
                    </div>
                    <div class="flex gap-3"><button id="clearTrailSearchButton"
                            class="rounded-full border border-[#D8B58F] bg-white px-5 py-3 text-sm font-semibold text-[#6B553F] hover:bg-[#F8F0E6]">{{ __('Clear') }}</button><button
                            id="generateTrailButton"
                            class="rounded-full bg-[#B8874A] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#9c6f33]">{{ __('Generate Trail') }}</button>
                    </div>
                </div>

                <form id="trailSearchForm" class="relative z-10 mt-6 grid gap-4 lg:grid-cols-[1.8fr_1fr]">
                    <div>
                        <input id="searchKeyword" type="search" list="locations"
                            class="relative z-10 w-full cursor-text rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-4 text-sm text-[#1F1B19] shadow-sm outline-none"
                            placeholder="{{ __('Search restaurant or location') }}" />
                        <datalist id="locations">
                            @foreach ($locations as $location)
                                <option value="{{ $location }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <select id="categorySelect"
                            class="relative z-10 w-full cursor-pointer rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-4 text-sm text-[#1F1B19] shadow-sm outline-none">
                            <option value="all">All food categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <div class="mt-4 rounded-3xl border border-[#E6D8C4] bg-[#FBF6F1] p-4 text-sm text-[#6B5B4B]">
                    <p id="selectedTrailSummary">
                        {{ __('Search by restaurant name or location, then press Generate Trail to begin your food adventure.') }}
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
                                <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">{{ __('Search results') }}
                                </p>
                                <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">
                                    {{ __('Restaurant recommendations') }}
                                </h2>
                            </div>
                            <span id="resultsCount"
                                class="rounded-full bg-[#F7E4C1] px-3 py-1 text-sm font-semibold text-[#8A5A24]">0
                                {{ __('restaurants') }}</span>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label
                                    class="mb-2 block text-sm font-semibold text-[#5A5047]">{{ __('Category') }}</label>
                                <select id="categoryFilter"
                                    class="w-full rounded-3xl border border-[#E6D8C4] bg-[#FFFBF6] px-4 py-3 text-sm outline-none shadow-sm">
                                    <option value="all">{{ __('All categories') }}</option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="mb-2 block text-sm font-semibold text-[#5A5047]">{{ __('Distance') }}</label>
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
                                <label
                                    class="mb-2 block text-sm font-semibold text-[#5A5047]">{{ __('Reviews') }}</label>
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
                                <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">{{ __('Route summary') }}
                                </p>
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
                                <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">{{ __('Map') }}</p>
                                <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">{{ __('Restaurant route') }}</h2>
                            </div>
                            <span id="mapMarkerCount"
                                class="rounded-full bg-[#F7E4C1] px-3 py-1 text-sm font-semibold text-[#8A5A24]">0 {{ __('markers') }}</span>
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
                                {{ __('Click a restaurant card to view route details.') }}</div>
                        </div>
                    </div>

                    <div id="restaurantDetailDrawer"
                        class="transition-all duration-300 ease-in-out overflow-hidden max-h-0">
                        <div id="selectedRestaurantDetails"
                            class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">{{ __('Restaurant details') }}</p>
                                    <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">{{ __('Selected restaurant') }}</h2>
                                </div>
                                <button id="closeRestaurantDrawer"
                                    class="inline-flex items-center rounded-full border border-[#D8B58F] bg-white px-4 py-2 text-sm font-semibold text-[#6B553F] transition hover:bg-[#f8efe5]">{{ __('Close') }}</button>
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
    'removedFromTrail' => __('Removed from trail'),
    'markedVisited' => __('Marked visited'),
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
    'emptyCurrentTrail' => __('Your current trail is empty. Generate a trail and add at least one restaurant to continue.'),
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
                {
                    id: 'fav-1',
                    title: @json(__('KL Heritage Walk')),
                    location: @json(__('Kuala Lumpur')),
                    description: @json(__('A classic route for local favorites and street food.')),
                    tags: [@json(__('Street Food')), @json(__('Local'))],
                    stops: 3,
                },
                {
                    id: 'fav-2',
                    title: @json(__('Penang Sweet Tour')),
                    location: @json(__('Penang')),
                    description: @json(__('A dessert-focused trail for local heritage sweets.')),
                    tags: [@json(__('Dessert')), @json(__('Heritage'))],
                    stops: 3,
                },
            ],
            curated: @json($curatedSuggestions),
            restaurants: @json($restaurants),
            favoritesKey: 'foodtrails-favorites',
            likedRestaurantsKey: 'foodtrail-liked-restaurants',
        };
    </script>
@endsection
