<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Start Trail | {{ config('app.name', 'Warisan Makan') }}</title>
    @vite(['resources/css/app.css', 'resources/js/food-trails.js'])
</head>

<body class="bg-[#f8f3ed] text-[#1f1b19] min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-6 lg:px-8">
        <header class="mb-8 rounded-[32px] bg-white p-6 shadow-[0_18px_40px_rgba(62,44,23,0.08)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-[0.32em] text-[#B8874A]">Trail in progress</p>
                    <h1 class="mt-3 text-3xl font-semibold text-[#1F1B19]">Start your food trail</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[#6B5B4B]">Follow the route, keep track of visited
                        stops, and finish your heritage food adventure.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="/foodtrails"
                        class="inline-flex items-center rounded-full border border-[#D8B58F] bg-white px-4 py-2 text-sm font-semibold text-[#7A5F3A] shadow-sm hover:bg-[#F6EFE3]">Back
                        to Trail</a>
                    <a href="/login"
                        class="inline-flex items-center rounded-full bg-[#B8874A] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#9c6f33]">Login</a>
                </div>
            </div>
        </header>

        <section class="grid gap-6 lg:grid-cols-[2fr_1fr]">
            <div class="space-y-6">
                <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">Live Map</p>
                            <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">Your current route</h2>
                        </div>
                        <span id="trailProgressBadge"
                            class="rounded-full bg-[#F7E4C1] px-3 py-1 text-sm font-semibold text-[#8A5A24]">0%
                            complete</span>
                    </div>
                    <div id="startMapContainer"
                        class="mt-6 aspect-[16/9] overflow-hidden rounded-[28px] border border-[#E8D4BE] bg-[#FBF6F1] shadow-inner relative">
                        <div
                            class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,210,146,0.35),_transparent_40%),radial-gradient(circle_at_bottom_right,_rgba(222,164,95,0.16),_transparent_35%)] pointer-events-none">
                        </div>
                        <div id="routeMapFrame" class="h-full w-full"></div>
                    </div>
                </div>

                <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">Route detail</p>
                            <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">Selected restaurants</h2>
                        </div>
                        <p id="estimateTimeText" class="text-sm text-[#6B5B4B]">Estimated time: 0 min</p>
                    </div>
                    <div id="selectedTrailList" class="mt-6 space-y-4"></div>
                    <div class="mt-6 flex justify-end">
                        <button id="completeAllButton"
                            class="rounded-full bg-[#7DA34D] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#6d8a42]">Complete
                            all stops</button>
                    </div>
                </div>
            </div>

            <div class="space-y-6">

                <div class="rounded-[32px] bg-white p-6 shadow-[0_12px_30px_rgba(46,32,16,0.08)]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.2em] text-[#B08B59]">Trail actions</p>
                            <h2 class="mt-2 text-xl font-semibold text-[#1F1B19]">Complete your route</h2>
                        </div>
                        <span id="favoriteStatus"
                            class="rounded-full bg-[#F7E4C1] px-3 py-1 text-sm font-semibold text-[#8A5A24]">Not
                            saved</span>
                    </div>
                    <div class="mt-6 grid gap-3">
                        <button id="whatsappShareButton"
                            class="rounded-full bg-[#25D366] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1da851]">Share
                            on WhatsApp</button>
                        <button id="copyLinkButton"
                            class="rounded-full bg-[#D8B58F] px-5 py-3 text-sm font-semibold text-[#1F1B19] transition hover:bg-[#c3a76e]">Copy
                            link</button>
                        <button id="openMapsButton"
                            class="rounded-full bg-[#4285F4] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#3367d6]">Open
                            in Google Maps</button>
                        <button id="addFavoriteButton"
                            class="rounded-full bg-[#B8874A] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#9c6f33]">Add
                            to favourite</button>
                        <button id="exitTrailButton"
                            class="rounded-full border border-[#D8B58F] bg-white px-5 py-3 text-sm font-semibold text-[#6B553F] transition hover:bg-[#f8efe5]">Exit</button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        const currentRouteKey = 'foodtrail-current-route';
        const favoritesKey = 'foodtrails-favorites';

        const getElement = (id) => document.getElementById(id);

        const formatTags = (tags = []) => tags.map((tag) => `<span class="inline-flex items-center rounded-full bg-[#F7E4C1] px-3 py-1 text-[11px] font-semibold text-[#8A5A24]">${tag}</span>`).join(' ');

        let routeData = [];
        let visitedCount = 0;
        let estimatedTime = 0;

        const selectedTrailList = getElement('selectedTrailList');
        const trailProgressBadge = getElement('trailProgressBadge');
        const estimateTimeText = getElement('estimateTimeText');
        const favoriteStatus = getElement('favoriteStatus');

        const updateRouteData = () => {
            routeData = JSON.parse(localStorage.getItem(currentRouteKey) || '[]');
            visitedCount = routeData.filter((item) => item.visited).length;
            estimatedTime = routeData.reduce((total, item) => total + Math.max(8, Math.round(item.distance * 7)), 0);
        };

        const updateHeaderStatus = () => {
            if (trailProgressBadge) trailProgressBadge.innerText = `${routeData.length ? Math.round((visitedCount / routeData.length) * 100) : 0}% complete`;
            if (estimateTimeText) estimateTimeText.innerText = `Estimated time: ${estimatedTime} min`;
        };

        const renderRouteItems = () => {
            if (!selectedTrailList) return;
            selectedTrailList.innerHTML = '';

            if (!routeData.length) {
                selectedTrailList.innerHTML = `<div class="rounded-[28px] border border-[#E9D7BF] bg-[#FEFBF7] p-6 text-sm text-[#6B5B4E]">No route selected. Go back to the food trails page and add restaurants to your trail.</div>`;
                return;
            }

            routeData.forEach((item, index) => {
                const card = document.createElement('article');
                card.className = 'rounded-[28px] border border-[#E9D7BF] bg-[#FEFBF7] p-5 shadow-sm';
                card.innerHTML = `
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-[#1F1B19]">${index + 1}. ${item.name}</p>
                            <p class="mt-1 text-xs text-[#6B5B4E]">${item.location} · ${item.distance} km · ${item.price}</p>
                        </div>
                        <span class="rounded-full ${item.visited ? 'bg-[#D3E9C4] text-[#4A6B31]' : 'bg-[#F7E4C1] text-[#8A5A24]'} px-3 py-1 text-xs font-semibold">${item.visited ? 'Visited' : 'Pending'}</span>
                    </div>
                    <p class="mt-3 text-sm text-[#6B5B4E]">${item.description}</p>
                    <div class="mt-3 flex flex-wrap gap-2">${formatTags(item.tags)}</div>
                `;
                selectedTrailList.appendChild(card);
            });
        };

        const getRouteShareText = () => {
            if (!routeData.length) {
                return 'I am planning a food trail with Warisan Makan.';
            }
            return `My food trail includes ${routeData.length} stops and ${estimatedTime} min estimated time. Stops: ${routeData.map((item) => item.name).join(', ')}.`;
        };

        const shareWhatsapp = () => {
            const text = encodeURIComponent(getRouteShareText());
            window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank');
        };

        const copyLink = async () => {
            const shareText = `${getRouteShareText()}\n${window.location.href}`;
            try {
                await navigator.clipboard.writeText(shareText);
                alert('Route link copied to clipboard.');
            } catch (error) {
                console.error(error);
                alert('Unable to copy link.');
            }
        };

        const openGoogleMaps = () => {
            if (!routeData.length) {
                alert('Add restaurants to your trail first.');
                return;
            }
            const origin = encodeURIComponent(routeData[0].location);
            const destinations = routeData.slice(1).map((item) => encodeURIComponent(item.location)).join('%7C');
            const mapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${encodeURIComponent(routeData[routeData.length - 1].location)}&travelmode=walking&waypoints=${destinations}`;
            window.open(mapsUrl, '_blank');
        };

        const saveFavoriteTrail = () => {
            const name = prompt('Name this favorite trail', 'My heritage food trail');
            if (!name) return;

            const existingFavorites = JSON.parse(localStorage.getItem(favoritesKey) || '[]');
            const favorite = {
                id: `fav-${Date.now()}`,
                title: name,
                location: routeData[0]?.location || 'Selected trail',
                description: `Saved trail with ${routeData.length} stops and ${estimatedTime} min estimated time.`,
                stops: routeData.length,
            };
            existingFavorites.push(favorite);
            localStorage.setItem(favoritesKey, JSON.stringify(existingFavorites));
            if (favoriteStatus) favoriteStatus.innerText = 'Saved';
            alert('Trail saved to favorites.');
        };

        const toggleCompleteTrail = () => {
            if (!routeData.length) return;
            const allVisited = routeData.every((item) => item.visited);
            routeData = routeData.map((item) => ({ ...item, visited: !allVisited }));
            localStorage.setItem(currentRouteKey, JSON.stringify(routeData));
            updateRouteData();
            renderRouteItems();
            updateHeaderStatus();
            const message = allVisited ? 'Trail completion reset.' : 'Trail marked as complete.';
            alert(message);
        };

        const exitTrail = () => {
            window.location.href = '/foodtrails';
        };

        document.addEventListener('DOMContentLoaded', () => {
            updateRouteData();
            renderRouteItems();
            updateHeaderStatus();

            getElement('whatsappShareButton')?.addEventListener('click', shareWhatsapp);
            getElement('copyLinkButton')?.addEventListener('click', copyLink);
            getElement('openMapsButton')?.addEventListener('click', openGoogleMaps);
            getElement('addFavoriteButton')?.addEventListener('click', saveFavoriteTrail);
            getElement('completeTrailButton')?.addEventListener('click', toggleCompleteTrail);
            getElement('completeAllButton')?.addEventListener('click', toggleCompleteTrail);
            getElement('exitTrailButton')?.addEventListener('click', exitTrail);
        });
    </script>
</body>

</html>
