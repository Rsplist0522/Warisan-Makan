const foodTrailApp = (() => {
    const appData = window.foodTrailApp || {};
    const translations = appData.translations || {};
    const translate = (key, fallback) => translations[key] || fallback;
    const favoritesKey = appData.favoritesKey || 'foodtrails-favorites';
    const likesKey = appData.likedRestaurantsKey || 'foodtrail-liked-restaurants';
    const currentRouteKey = 'foodtrail-current-route';
    const selectors = {
        locationInput: 'locationInput',
        categorySelect: 'categorySelect',
        searchKeyword: 'searchKeyword',
        generateButton: 'generateTrailButton',
        resultsPanel: 'resultsPanel',
        initialPanel: 'initialPanel',
        resultsCount: 'resultsCount',
        categoryFilter: 'categoryFilter',
        distanceFilter: 'distanceFilter',
        priceFilter: 'priceFilter',
        reviewFilter: 'reviewFilter',
        resetFiltersButton: 'resetFiltersButton',
        restaurantList: 'restaurantList',
        googleMap: 'googleMap',
        mapMarkerCount: 'mapMarkerCount',
        routeCompletion: 'routeCompletion',
        routeSummary: 'routeSummary',
        selectedRestaurantDetails: 'selectedRestaurantDetails',
        restaurantDetailDrawer: 'restaurantDetailDrawer',
        restaurantDetailsContent: 'restaurantDetailsContent',
        selectedTrailSummary: 'selectedTrailSummary',
        favoritesList: 'favoritesList',
        clearFavoritesButton: 'clearFavoritesButton',
        curatedTrailCards: 'curatedTrailCards',
        startNowButton: 'startNowButton',
        closeRestaurantDrawer: 'closeRestaurantDrawer',
    };
    const state = {
        activeRestaurants: [],
        filteredRestaurants: [],
        routeRestaurants: [],
        selectedRestaurant: null,
        favorites: [],
        likedRestaurantIds: [],
    };
    const googleApiKey = window.googleMapsApiKey || '';
    let googleMap = null;
    let googleGeocoder = null;
    let googleMapMarkers = [];
    const geocodeCache = new Map();
    const initGoogleMapHelpers = () => {
        if (!googleApiKey || !window.google?.maps) return;
        const mapEl = getElement(selectors.googleMap);
        if (!mapEl || googleMap) return;
        googleGeocoder = new google.maps.Geocoder();
        googleMap = new google.maps.Map(mapEl, {
            center: { lat: 3.1390, lng: 101.6869 },
            zoom: 12,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        });
    };
    const geocodeAddress = (address) => {
        if (!address || !googleGeocoder) return Promise.reject(new Error('Geocoder unavailable'));
        if (geocodeCache.has(address)) return Promise.resolve(geocodeCache.get(address));
        return new Promise((resolve, reject) => {
            googleGeocoder.geocode({ address }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    const position = {
                        lat: results[0].geometry.location.lat(),
                        lng: results[0].geometry.location.lng(),
                    };
                    geocodeCache.set(address, position);
                    resolve(position);
                } else {
                    reject(new Error(`Geocode failed for "${address}": ${status}`));
                }
            });
        });
    };
    const clearGoogleMarkers = () => {
        googleMapMarkers.forEach((marker) => marker.setMap(null));
        googleMapMarkers = [];
    };
    const fallbackPosition = (restaurant) => {
        const latitude = Number(restaurant.coordinates?.lat);
        const longitude = Number(restaurant.coordinates?.lng);
        if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
            return { lat: latitude, lng: longitude };
        }

        const x = Number(restaurant.coordinates?.x ?? 50);
        const y = Number(restaurant.coordinates?.y ?? 50);
        return {
            lat: 3.1390 + ((y - 50) * 0.002),
            lng: 101.6869 + ((x - 50) * 0.002),
        };
    };
    const renderMapMarkers = () => {
        const markerCount = getElement(selectors.mapMarkerCount);
        const restaurantsToDisplay = state.routeRestaurants.length
            ? state.routeRestaurants
            : (state.selectedRestaurant ? [state.selectedRestaurant] : []);
        if (markerCount) markerCount.innerText = `${restaurantsToDisplay.length} ${translate(restaurantsToDisplay.length === 1 ? 'marker' : 'markers', restaurantsToDisplay.length === 1 ? 'marker' : 'markers')}`;
        if (!googleMap) return;
        clearGoogleMarkers();
        const bounds = new google.maps.LatLngBounds();
        if (!restaurantsToDisplay.length) {
            googleMap.setCenter({ lat: 3.1390, lng: 101.6869 });
            googleMap.setZoom(12);
            return;
        }
        restaurantsToDisplay.forEach((restaurant, index) => {
            const address = `${restaurant.name}, ${restaurant.location}`.trim();
            geocodeAddress(address).catch(() => fallbackPosition(restaurant)).then((position) => {
                const isSelected = state.selectedRestaurant?.id === restaurant.id;
                const marker = new google.maps.Marker({
                    position,
                    map: googleMap,
                    title: restaurant.name,
                    label: {
                        text: `${index + 1}`,
                        color: '#ffffff',
                        fontSize: '12px',
                        fontWeight: '700',
                    },
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        fillColor: restaurant.visited ? '#4CAF50' : (isSelected ? '#D98F4F' : '#B8874A'),
                        fillOpacity: 1,
                        strokeColor: '#ffffff',
                        strokeWeight: 2,
                        scale: isSelected ? 16 : 12,
                    },
                });
                marker.addListener('click', () => selectRestaurant(restaurant.id));
                googleMapMarkers.push(marker);
                if (isSelected) {
                    googleMap.panTo(position);
                    googleMap.setZoom(15);
                    return;
                }
                bounds.extend(position);
                if (restaurantsToDisplay.length > 1) {
                    googleMap.fitBounds(bounds, 80);
                } else {
                    googleMap.setCenter(position);
                    googleMap.setZoom(14);
                }
            });
        });
    };
    window.initFoodTrailMap = () => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                initGoogleMapHelpers();
                if (state.routeRestaurants.length) renderMapMarkers();
            });
            return;
        }
        initGoogleMapHelpers();
        if (state.routeRestaurants.length) renderMapMarkers();
    };
    const getElement = (id) => document.getElementById(id);
    const showGoogleMapMessage = (message) => {
        const messageEl = getElement('googleMapMessage');
        if (!messageEl) return;
        messageEl.textContent = message;
        messageEl.classList.remove('hidden');
    };
    const loadFavorites = () => {
        try {
            state.favorites = JSON.parse(localStorage.getItem(favoritesKey) || '[]');
        } catch (error) {
            state.favorites = [];
        }
        if (!state.favorites.length && Array.isArray(appData.defaultFavorites)) {
            state.favorites = appData.defaultFavorites.slice();
        }
    };
    const saveFavorites = () => {
        localStorage.setItem(favoritesKey, JSON.stringify(state.favorites));
    };
    const loadLikes = () => {
        try {
            state.likedRestaurantIds = JSON.parse(localStorage.getItem(likesKey) || '[]');
        } catch (error) {
            state.likedRestaurantIds = [];
        }
    };
    const saveLikes = () => {
        localStorage.setItem(likesKey, JSON.stringify(state.likedRestaurantIds));
    };
    const saveCurrentRoute = () => {
        localStorage.setItem(currentRouteKey, JSON.stringify(state.routeRestaurants));
    };
    const loadCurrentRoute = () => {
        try {
            const savedRoute = JSON.parse(localStorage.getItem(currentRouteKey) || '[]') || [];
            const databaseRestaurantIds = new Set(
                Object.values(appData.restaurants || {}).flat().map((restaurant) => restaurant.id),
            );

            // Remove routes saved while Food Trails still used its demo data.
            // A route can only contain records supplied by the database now.
            state.routeRestaurants = Array.isArray(savedRoute)
                ? savedRoute.filter((restaurant) => databaseRestaurantIds.has(restaurant.id))
                : [];
            saveCurrentRoute();
        } catch (error) {
            state.routeRestaurants = [];
            localStorage.removeItem(currentRouteKey);
        }
    };
    const setPanelVisibility = (showResults) => {
        const resultsPanel = getElement(selectors.resultsPanel);
        const initialPanel = getElement(selectors.initialPanel);
        if (resultsPanel && initialPanel) {
            resultsPanel.classList.toggle('hidden', !showResults);
            initialPanel.classList.toggle('hidden', showResults);
        }
    };
    const formatTags = (tags = []) => tags.map((tag) => `<span class="inline-flex items-center rounded-full bg-[#F7E4C1] px-3 py-1 text-[11px] font-semibold text-[#8A5A24]">${tag}</span>`).join(' ');
    const renderFavorites = () => {
        const container = getElement(selectors.favoritesList);
        if (!container) return;
        container.innerHTML = '';
        if (!state.favorites.length) {
            container.innerHTML = `<p class="text-sm leading-6 text-[#6B5B4E]">${translate('noSavedTrails', 'No saved food trails yet. Generate a trail and save it as a favorite.')}</p>`;
            return;
        }
        state.favorites.forEach((item) => {
            const card = document.createElement('div');
            card.className = 'rounded-[28px] border border-[#E9D7BF] bg-[#FEFBF7] p-4 shadow-sm';
            card.innerHTML = `
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-[#1F1B19]">${item.title}</p>
                        <p class="text-sm text-[#6B5B4E]">${item.location} · ${item.stops} stops</p>
                    </div>
                    <button data-favorite-id="${item.id}" class="rounded-full bg-[#8A5A24] px-3 py-1 text-xs font-semibold text-white hover:bg-[#9e6d34]">${translate('open', 'Open')}</button>
                </div>
                <p class="mt-3 text-sm text-[#7B6B5F]">${item.description}</p>
            `;
            card.querySelector('button')?.addEventListener('click', () => {
                getElement(selectors.locationInput).value = item.location;
                getElement(selectors.categorySelect).value = 'all';
                handleGenerateTrail();
            });
            container.appendChild(card);
        });
    };
    const renderCuratedTrails = () => {
        const container = getElement(selectors.curatedTrailCards);
        if (!container) return;
        container.innerHTML = '';
        appData.curated?.forEach((item) => {
            const card = document.createElement('article');
            card.className = 'rounded-[28px] border border-[#E9D7BF] bg-[#FEFBF7] p-5 shadow-sm';
            card.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">${item.category}</p>
                        <h3 class="mt-2 text-lg font-semibold text-[#1F1B19]">${item.title}</h3>
                    </div>
                    <button data-curated-id="${item.id}" class="rounded-full border border-[#D8B58F] bg-white px-3 py-1 text-xs font-semibold text-[#6B553F] hover:bg-[#f8efe5]">${translate('try', 'Try')}</button>
                </div>
                <p class="mt-3 text-sm leading-6 text-[#6B5B4E]">${item.subtitle}</p>
                <p class="mt-4 text-sm text-[#7B6B5F]">${item.summary}</p>
            `;
            card.querySelector('button')?.addEventListener('click', () => {
                state.routeRestaurants = (item.restaurants || []).map((restaurant) => ({ ...restaurant, visited: false }));
                state.activeRestaurants = state.routeRestaurants.slice(); state.filteredRestaurants = state.routeRestaurants.slice();
                state.selectedRestaurant = state.routeRestaurants[0] || null; saveCurrentRoute(); setPanelVisibility(true); renderRestaurantList(); renderRouteSummary(); updateRouteCompletion(); renderSelectedRestaurantDetails();
            });
            container.appendChild(card);
        });
    };
    const normalizeLocation = (location) => {
        const normalized = String(location || '').trim().toLowerCase();
        if (
            normalized.includes('kuala lumpur')
            || normalized.includes('wilayah persekutuan')
            || normalized.includes('federal territory of kuala lumpur')
            || /\b(?:5\d{4}|60\d{3})\b/.test(normalized)
        ) {
            return 'Kuala Lumpur';
        }

        return location?.trim() || '';
    };
    const getRestaurantsForLocation = (location) => {
        return appData.restaurants?.[normalizeLocation(location)] || [];
    };
    const updateResultsCount = () => {
        const countBadge = getElement(selectors.resultsCount);
        if (!countBadge) return;
        countBadge.innerText = `${state.filteredRestaurants.length} ${translate('restaurants', 'restaurants')}`;
    };
    const renderFilterOptions = () => {
        const categoryFilter = getElement(selectors.categoryFilter);
        if (!categoryFilter) return;
        const categories = ['all', ...new Set(state.activeRestaurants.map((item) => item.category))];
        categoryFilter.innerHTML = categories.map((category) => `
            <option value="${category}">${category === 'all' ? translate('allCategories', 'All categories') : category}</option>
        `).join('');
    };
    const applyFilters = () => {
        const categoryValue = getElement(selectors.categoryFilter)?.value || 'all';
        const distanceValue = getElement(selectors.distanceFilter)?.value || 'all';
        const priceValue = getElement(selectors.priceFilter)?.value || 'all';
        const reviewValue = getElement(selectors.reviewFilter)?.value || 'all';
        const keywordValue = getElement(selectors.searchKeyword)?.value.trim().toLowerCase();
        state.filteredRestaurants = state.activeRestaurants.filter((restaurant) => {
            const inCategory = categoryValue === 'all' || restaurant.category === categoryValue;
            const inDistance = distanceValue === 'all' || restaurant.distance <= Number(distanceValue);
            const inPrice = priceValue === 'all' || restaurant.price === priceValue;
            const inReview = reviewValue === 'all' || restaurant.rating >= Number(reviewValue);
            const inKeyword = !keywordValue || restaurant.name.toLowerCase().includes(keywordValue) || restaurant.description.toLowerCase().includes(keywordValue) || restaurant.location.toLowerCase().includes(keywordValue);
            return inCategory && inDistance && inPrice && inReview && inKeyword;
        });
        renderRestaurantList();
        renderMapMarkers();
        updateResultsCount();
    };
    const renderRestaurantList = () => {
        const container = getElement(selectors.restaurantList);
        if (!container) return;
        container.innerHTML = '';
        if (!state.filteredRestaurants.length) {
            container.innerHTML = `<div class="rounded-[28px] border border-[#E9D7BF] bg-[#FEFBF7] p-6 text-sm text-[#6B5B4E]">${translate('noRestaurantMatches', 'No restaurants match the selected filters. Try another filter or location.')}</div>`;
            return;
        }
        state.filteredRestaurants.forEach((restaurant) => {
            const isAdded = state.routeRestaurants.some((item) => item.id === restaurant.id);
            const isLiked = state.likedRestaurantIds.includes(restaurant.id);
            const card = document.createElement('article');
            card.className = 'rounded-[28px] border border-[#E9D7BF] bg-[#FEFBF7] shadow-sm hover:shadow-md';
            card.innerHTML = `
                <div class="grid gap-4 lg:grid-cols-[120px_1fr] p-5">
                    <img src="${restaurant.picture}" alt="${restaurant.name}" class="h-28 w-full rounded-[24px] object-cover lg:h-full" />
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-[#1F1B19]">${restaurant.name}</p>
                                <p class="mt-1 text-xs text-[#6B5B4E]">${restaurant.location}</p>
                            </div>
                            <button data-like-id="${restaurant.id}" class="text-sm font-semibold text-[#B4542A]">${isLiked ? '♥' : '♡'}</button>
                        </div>
                        <div class="flex flex-wrap gap-2 text-[11px] text-[#5A5047]">
                            <span class="rounded-full bg-[#F3E2C7] px-3 py-1">${restaurant.category}</span>
                            <span class="rounded-full bg-[#F3E2C7] px-3 py-1">${restaurant.price}</span>
                            <span class="rounded-full bg-[#F3E2C7] px-3 py-1">${restaurant.rating} ★</span>
                            <span class="rounded-full bg-[#F3E2C7] px-3 py-1">${restaurant.waitTime}</span>
                        </div>
                        <p class="text-sm leading-6 text-[#6B5B4E]">${restaurant.description}</p>
                        <div class="flex flex-wrap gap-2 text-sm text-[#6B5B4E]">${formatTags(restaurant.tags)}</div>
                        <div class="flex items-center justify-between gap-3">
                            <button data-add-id="${restaurant.id}" class="rounded-full bg-[#B8874A] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#9c6f33]">${isAdded ? translate('added', 'Added') : translate('add', '+ Add')}</button>
                        </div>
                    </div>
                </div>
            `;
            const addButton = card.querySelector('[data-add-id]');
            const likeButton = card.querySelector('[data-like-id]');
            addButton?.addEventListener('click', (event) => {
                event.stopPropagation();
                toggleRestaurantInRoute(restaurant.id);
            });
            likeButton?.addEventListener('click', (event) => {
                event.stopPropagation();
                toggleRestaurantLike(restaurant.id);
            });
            card.addEventListener('click', () => selectRestaurant(restaurant.id));
            container.appendChild(card);
        });
    };
    const selectRestaurant = (restaurantId) => {
        state.selectedRestaurant = state.filteredRestaurants.find((item) => item.id === restaurantId) || state.routeRestaurants.find((item) => item.id === restaurantId) || null;
        renderSelectedRestaurantDetails();
        renderMapMarkers();
    };
    const renderSelectedRestaurantDetails = () => {
        const content = getElement(selectors.restaurantDetailsContent);
        const drawer = getElement(selectors.restaurantDetailDrawer);
        if (!content || !drawer) return;
        if (!state.selectedRestaurant) {
            content.innerHTML = `
                <p class="font-semibold text-[#1F1B19]">${translate('pickRestaurant', 'Pick a restaurant')}</p>
                <p class="mt-2 text-sm leading-6 text-[#6B5B4E]">${translate('pickRestaurantHelp', 'Select a restaurant from the list to see details, add to your trail, or mark it as visited.')}</p>
            `;
            toggleRestaurantDrawer(false);
            return;
        }
        const isAdded = state.routeRestaurants.some((item) => item.id === state.selectedRestaurant.id);
        const isLiked = state.likedRestaurantIds.includes(state.selectedRestaurant.id);
        const foundRoute = state.routeRestaurants.find((item) => item.id === state.selectedRestaurant.id);
        const visited = foundRoute?.visited || false;
        content.innerHTML = `
            <div class="space-y-5">
                <img src="${state.selectedRestaurant.picture}" alt="${state.selectedRestaurant.name}" class="h-56 w-full rounded-[28px] object-cover" />
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <p class="text-sm uppercase tracking-[0.18em] text-[#B08B59]">${state.selectedRestaurant.location}</p>
                        <h3 class="mt-2 text-2xl font-semibold text-[#1F1B19]">${state.selectedRestaurant.name}</h3>
                    </div>
                    <div class="text-right sm:text-left">
                        <p class="text-sm text-[#6B5B4E]">${state.selectedRestaurant.category} · ${state.selectedRestaurant.price}</p>
                        <p class="mt-1 text-sm text-[#6B5B4E]">${state.selectedRestaurant.rating} ★ · ${state.selectedRestaurant.reviewCount} reviews</p>
                        <p class="mt-1 text-sm text-[#6B5B4E]">Wait time: ${state.selectedRestaurant.waitTime}</p>
                    </div>
                </div>
                <p class="text-sm leading-6 text-[#6B5B4E]">${state.selectedRestaurant.description}</p>
                <div class="flex flex-wrap gap-2 text-sm text-[#6B5B4E]">${formatTags(state.selectedRestaurant.tags)}</div>
                <div class="flex flex-wrap gap-3">
                    <button id="addRouteButton" class="rounded-full ${isAdded ? 'bg-[#A2A296] hover:bg-[#8e8c7c]' : 'bg-[#B8874A] hover:bg-[#9c6f33]'} px-5 py-3 text-sm font-semibold text-white">${isAdded ? 'Remove from trail' : '+ Add to trail'}</button>
                    <button id="markVisitedButton" class="rounded-full ${visited ? 'bg-[#7DA34D] hover:bg-[#6d8a42]' : 'bg-[#D8B58F] hover:bg-[#c3a76e]'} px-5 py-3 text-sm font-semibold text-[#1F1B19]">${visited ? 'Visited' : 'Mark visited'}</button>
                    <button id="loveRestaurantButton" class="rounded-full ${isLiked ? 'bg-[#F8D4D0] hover:bg-[#efc2ba]' : 'bg-[#F8E0D4] hover:bg-[#f2d2ba]'} px-5 py-3 text-sm font-semibold text-[#B4542A]">${isLiked ? '♥ Liked' : '♡ Love'}</button>
                </div>
            </div>
        `;
        const addButton = content.querySelector('#addRouteButton');
        const visitedButton = content.querySelector('#markVisitedButton');
        const loveButton = content.querySelector('#loveRestaurantButton');
        addButton?.addEventListener('click', () => toggleRestaurantInRoute(state.selectedRestaurant.id));
        visitedButton?.addEventListener('click', () => toggleRestaurantVisited(state.selectedRestaurant.id));
        loveButton?.addEventListener('click', () => toggleRestaurantLike(state.selectedRestaurant.id));
        toggleRestaurantDrawer(true);
    };
    const toggleRestaurantDrawer = (show) => {
        const drawer = getElement(selectors.restaurantDetailDrawer);
        if (!drawer) return;
        drawer.style.maxHeight = show ? '1200px' : '0';
        drawer.style.opacity = show ? '1' : '0';
        drawer.style.paddingTop = show ? '1rem' : '0';
        drawer.style.paddingBottom = show ? '1rem' : '0';
    };
    const removeRouteRestaurant = (restaurantId) => {
        const index = state.routeRestaurants.findIndex((item) => item.id === restaurantId);
        if (index >= 0) {
            state.routeRestaurants.splice(index, 1);
            saveCurrentRoute();
            renderRestaurantList();
            renderRouteSummary();
            updateRouteCompletion();
            renderSelectedRestaurantDetails();
            renderMapMarkers();
        }
    };
    const moveRouteRestaurant = (fromId, toId) => {
        const fromIndex = state.routeRestaurants.findIndex((item) => item.id === fromId);
        const toIndex = state.routeRestaurants.findIndex((item) => item.id === toId);
        if (fromIndex < 0 || toIndex < 0 || fromIndex === toIndex) return;
        const [moved] = state.routeRestaurants.splice(fromIndex, 1);
        state.routeRestaurants.splice(toIndex, 0, moved);
        saveCurrentRoute();
        renderRouteSummary();
        renderRestaurantList();
        renderMapMarkers();
    };
    const toggleRestaurantInRoute = (restaurantId) => {
        const index = state.routeRestaurants.findIndex((item) => item.id === restaurantId);
        if (index >= 0) {
            state.routeRestaurants.splice(index, 1);
        } else {
            const restaurant = state.activeRestaurants.find((item) => item.id === restaurantId);
            if (!restaurant) return;
            state.routeRestaurants.push({ ...restaurant, visited: false });
        }
        saveCurrentRoute();
        renderRestaurantList();
        renderRouteSummary();
        updateRouteCompletion();
        renderSelectedRestaurantDetails();
        renderMapMarkers();
    };
    const toggleRestaurantVisited = (restaurantId) => {
        const routeItem = state.routeRestaurants.find((item) => item.id === restaurantId);
        if (!routeItem) return;
        routeItem.visited = !routeItem.visited;
        saveCurrentRoute();
        renderRouteSummary();
        updateRouteCompletion();
        renderSelectedRestaurantDetails();
        renderMapMarkers();
    };
    const toggleRestaurantLike = (restaurantId) => {
        const index = state.likedRestaurantIds.indexOf(restaurantId);
        if (index >= 0) {
            state.likedRestaurantIds.splice(index, 1);
        } else {
            state.likedRestaurantIds.push(restaurantId);
        }
        saveLikes();
        renderRestaurantList();
        renderSelectedRestaurantDetails();
    };
    const calculateRouteTime = () => {
        return state.routeRestaurants.reduce((total, item) => total + Math.max(8, Math.round(item.distance * 7)), 0);
    };
    const updateRouteCompletion = () => {
        const badge = getElement(selectors.routeCompletion);
        if (!badge) return;
        if (!state.routeRestaurants.length) {
            badge.innerText = `0% ${translate('complete', 'complete')}`;
            return;
        }
        const visitedCount = state.routeRestaurants.filter((item) => item.visited).length;
        const percent = Math.round((visitedCount / state.routeRestaurants.length) * 100);
        badge.innerText = `${percent}% ${translate('complete', 'complete')}`;
    };
    const renderRouteSummary = () => {
        const container = getElement(selectors.routeSummary);
        if (!container) return;
        container.innerHTML = '';
        if (!state.routeRestaurants.length) {
            container.innerHTML = `<p class="text-sm text-[#6B5B4B]">${translate('addRestaurantsToTrail', 'Add restaurants to your food trail and get a simple route plan with estimated travel time.')}</p>`;
            return;
        }
        const estimatedTime = calculateRouteTime();
        const summaryHeader = document.createElement('div');
        summaryHeader.className = 'rounded-[24px] bg-[#FFFBF7] p-4 border border-[#E7D7C0]';
        summaryHeader.innerHTML = `
            <p class="text-sm text-[#6B5B4B]">${translate('totalEstimatedJourneyTime', 'Total estimated journey time')}</p>
            <p class="mt-1 text-base font-semibold text-[#1F1B19]">${estimatedTime} min</p>
        `;
        container.appendChild(summaryHeader);
        state.routeRestaurants.forEach((item, index) => {
            const routeItem = document.createElement('div');
            routeItem.className = 'rounded-[24px] border border-[#E9D7BF] bg-[#FEFBF7] p-4';
            routeItem.innerHTML = `
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-[#1F1B19]">${index + 1}. ${item.name}</p>
                        <p class="mt-1 text-xs text-[#6B5B4E]">${item.location} · ${item.distance} km · ${item.price}</p>
                    </div>
                    <span class="rounded-full ${item.visited ? 'bg-[#D3E9C4] text-[#4A6B31]' : 'bg-[#F7E4C1] text-[#8A5A24]'} px-3 py-1 text-xs font-semibold">${item.visited ? 'Visited' : 'Pending'}</span>
                </div>
                <p class="mt-3 text-sm text-[#6B5B4E]">Next travel time: ${Math.max(8, Math.round(item.distance * 7))} min</p>
            `;
            container.appendChild(routeItem);
        });
    };
    const handleGenerateTrail = () => {
        const locationValue = getElement(selectors.locationInput)?.value?.trim();
        const categoryValue = getElement(selectors.categorySelect)?.value || 'all';
        const keywordValue = getElement(selectors.searchKeyword)?.value.trim().toLowerCase();
        if (!locationValue) {
            getElement(selectors.selectedTrailSummary).innerText = translate('enterLocation', 'Please enter a location before generating.');
            return;
        }
        const allRestaurants = getRestaurantsForLocation(locationValue);
        if (!allRestaurants.length) {
            getElement(selectors.selectedTrailSummary).innerText = translate('noLocationRestaurants', 'No restaurants found for this location. Try another city.');
            return;
        }
        const filteredByCategory = categoryValue === 'all'
            ? allRestaurants
            : allRestaurants.filter((restaurant) => restaurant.category === categoryValue);
        const keywordFiltered = keywordValue
            ? filteredByCategory.filter((restaurant) => {
                const value = `${restaurant.name} ${restaurant.description} ${restaurant.location}`.toLowerCase();
                return value.includes(keywordValue);
            })
            : filteredByCategory;
        state.activeRestaurants = keywordFiltered;
        state.filteredRestaurants = keywordFiltered;
        state.routeRestaurants = [];
        state.selectedRestaurant = keywordFiltered[0] || null;
        if (window.google?.maps) {
            initGoogleMapHelpers();
        }
        setPanelVisibility(true);
        renderFilterOptions();
        renderRestaurantList();
        renderMapMarkers();
        renderSelectedRestaurantDetails();
        renderRouteSummary();
        updateRouteCompletion();
        updateResultsCount();
        getElement(selectors.selectedTrailSummary).innerText = keywordFiltered.length
            ? `Showing ${keywordFiltered.length} restaurants in ${locationValue}. Use filters to refine the list.`
            : 'No restaurants match your criteria. Adjust the filters to see more results.';
    };
    const handleResetFilters = () => {
        getElement(selectors.categoryFilter).value = 'all';
        getElement(selectors.distanceFilter).value = 'all';
        getElement(selectors.priceFilter).value = 'all';
        getElement(selectors.reviewFilter).value = 'all';
        getElement(selectors.searchKeyword).value = '';
        applyFilters();
    };
    const handleStartNow = () => {
        localStorage.setItem(currentRouteKey, JSON.stringify(state.routeRestaurants));
        window.location.href = '/start_trail';
    };
    const handleCloseRestaurantDrawer = () => {
        toggleRestaurantDrawer(false);
    };
    const wireEvents = () => {
        getElement(selectors.generateButton)?.addEventListener('click', handleGenerateTrail);
        getElement(selectors.categoryFilter)?.addEventListener('change', applyFilters);
        getElement(selectors.distanceFilter)?.addEventListener('change', applyFilters);
        getElement(selectors.priceFilter)?.addEventListener('change', applyFilters);
        getElement(selectors.reviewFilter)?.addEventListener('change', applyFilters);
        getElement(selectors.resetFiltersButton)?.addEventListener('click', handleResetFilters);
        getElement(selectors.searchKeyword)?.addEventListener('input', applyFilters);
        getElement(selectors.clearFavoritesButton)?.addEventListener('click', () => {
            if (!confirm(translate('clearSavedTrails', 'Clear all saved favourite trails?'))) return;
            state.favorites = [];
            saveFavorites();
            renderFavorites();
        });
        getElement(selectors.startNowButton)?.addEventListener('click', handleStartNow);
        getElement(selectors.closeRestaurantDrawer)?.addEventListener('click', handleCloseRestaurantDrawer);
    };
    const init = () => {
        loadFavorites();
        loadLikes();
        loadCurrentRoute();
        renderFavorites();
        renderCuratedTrails();
        setPanelVisibility(state.routeRestaurants.length > 0);
        if (state.routeRestaurants.length) {
            state.filteredRestaurants = state.routeRestaurants.slice();
            state.activeRestaurants = state.routeRestaurants.slice();
            state.selectedRestaurant = state.routeRestaurants[0] || null;
        }
        wireEvents();
        renderRestaurantList();
        renderRouteSummary();
        updateRouteCompletion();
        renderSelectedRestaurantDetails();
        if (window.google?.maps) {
            initGoogleMapHelpers();
            if (state.routeRestaurants.length) renderMapMarkers();
        } else if (!googleApiKey) {
            showGoogleMapMessage(translate('mapNotConfigured', 'Google Maps is not configured. Add GOOGLE_MAPS_API_KEY to your .env file and reload.'));
        }
    };
    return {
        init,
    };
})();
window.addEventListener('DOMContentLoaded', () => {
    foodTrailApp.init();
});
window.addEventListener('googleMapsError', (event) => {
    const messageEl = document.getElementById('googleMapMessage');
    if (messageEl) {
        messageEl.textContent = event.detail || (window.foodTrailApp?.translations?.mapLoadFailed || 'Google Maps could not be loaded.');
        messageEl.classList.remove('hidden');
    }
});
