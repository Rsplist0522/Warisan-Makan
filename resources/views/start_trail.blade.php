<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Start Trail | {{ config('app.name', 'Warisan Makan') }}</title>
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
        .trail-stop-card {
            display: grid;
            grid-template-columns: auto 72px minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            border: 1px solid #E9D7BF;
            border-radius: 24px;
            background: #FEFBF7;
            padding: 14px;
            box-shadow: 0 10px 24px rgba(62, 44, 23, 0.06);
            transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
        }

        .trail-stop-card:hover,
        .trail-stop-card.is-drop-target {
            border-color: #D8B58F;
            box-shadow: 0 14px 28px rgba(62, 44, 23, 0.1);
        }

        .trail-stop-card.is-dragging {
            opacity: 0.65;
            transform: scale(0.99);
        }

        .trail-drag-handle {
            display: inline-flex;
            width: 32px;
            height: 32px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid #E9D7BF;
            background: #FFFFFF;
            color: #B8874A;
            font-size: 16px;
            cursor: grab;
        }

        .trail-stop-image {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            object-fit: cover;
            border: 1px solid #F0D6C4;
            background: #FFF4E7;
        }

        .trail-stop-content {
            min-width: 0;
        }

        .trail-stop-title-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .trail-stop-title {
            min-width: 0;
            color: #1F1B19;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.35;
        }

        .trail-stop-meta {
            margin-top: 5px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            color: #6B5B4B;
            font-size: 12px;
            line-height: 1.4;
        }

        .trail-stop-number,
        .trail-next-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .trail-stop-number {
            width: 30px;
            height: 30px;
            background: #B8874A;
            color: #FFFFFF;
        }

        .trail-stop-number.is-visited {
            background: #4A6B31;
        }

        .trail-next-badge {
            background: #FFF0D9;
            color: #B8874A;
            padding: 5px 9px;
        }

        .trail-stop-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: flex-end;
        }

        .trail-visit-button,
        .trail-remove-button {
            display: inline-flex;
            height: 36px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            transition: background-color 160ms ease, color 160ms ease;
        }

        .trail-visit-button {
            min-width: 92px;
            background: #FFF0D9;
            color: #8A5A24;
            padding: 0 12px;
        }

        .trail-visit-button.is-visited {
            background: #DFF2DF;
            color: #4A6B31;
        }

        .trail-remove-button {
            min-width: 76px;
            border: 1px solid #E9D7BF;
            background: #FFFFFF;
            color: #6B553F;
            padding: 0 12px;
        }

        @media (max-width: 640px) {
            .trail-stop-card {
                grid-template-columns: auto minmax(0, 1fr);
            }

            .trail-stop-image {
                grid-column: 1;
                grid-row: 2;
                width: 64px;
                height: 64px;
            }

            .trail-stop-content {
                grid-column: 2;
                grid-row: 1 / span 2;
            }

            .trail-stop-actions {
                grid-column: 1 / -1;
                justify-content: space-between;
            }
        }

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

        .trail-directions-list {
            margin-top: 12px;
            display: grid;
            gap: 10px;
        }

        .trail-direction-step {
            border: 1px solid #F0D6C4;
            border-radius: 18px;
            background: #FFFFFF;
            padding: 12px;
        }

        .trail-direction-step p {
            margin: 0;
        }

        .trail-complete-modal {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(31, 27, 25, 0.42);
            padding: 20px;
        }

        .trail-complete-modal.is-hidden {
            display: none;
        }
    </style>
</head>

<body class="start-trail-page bg-[#FBF5EC] text-[#1F1B19] min-h-screen">
    @include('partials.foodtrail-nav')

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="trail-page-header mb-6 rounded-[32px] bg-white p-6 shadow-[0_18px_40px_rgba(62,44,23,0.08)]">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4">
                    <a href="/foodtrails"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-[#E9D7BF] bg-[#FEF8EE] text-[#B8874A] hover:bg-[#FFF1DC]">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15 18l-6-6 6-6" stroke="#B8874A" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-3xl font-semibold text-[#1F1B19]">Start Trail</h1>
                        <p class="mt-2 text-sm text-[#6B5B4B]">Plan your perfect food adventure</p>
                    </div>
                </div>
                <div
                    class="flex items-center gap-4 rounded-[24px] border border-[#F0D9B6] bg-[#FFF3E4] px-4 py-3 shadow-sm">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[18px] bg-[#FDE7CA]">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M12 2C8.686 2 6 4.686 6 8c0 4.418 5.04 10.263 5.348 10.6a1 1 0 0 0 1.304 0C12.96 18.263 18 12.418 18 8c0-3.314-2.686-6-6-6Z"
                                fill="#D98F4F" />
                            <path d="M12 10.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" fill="#FFF5EC" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.22em] text-[#B08B59]">Food Trail</p>
                        <p class="mt-1 font-semibold text-[#1F1B19]">Kuala Lumpur</p>
                    </div>
                    <div class="ml-auto text-[#B8874A]">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18l6-6-6-6" stroke="#B8874A" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="trail-layout grid gap-6 xl:grid-cols-[1.8fr_1fr]">
            <div class="space-y-6">
                <section class="trail-map-card rounded-[32px] bg-white p-6 shadow-[0_18px_40px_rgba(62,44,23,0.08)]">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div
                            class="flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.22em] text-[#B8874A]">
                            <span class="inline-flex h-3.5 w-3.5 rounded-full bg-[#D98F4F]"></span>
                            LIVE MAP
                        </div>
                        <span id="trailProgressBadge"
                            class="rounded-full bg-[#FFF0D9] px-3 py-1 text-xs font-semibold text-[#B8874A]">0%
                            complete</span>
                    </div>
                    <h2 class="mt-4 text-2xl font-semibold text-[#1F1B19]">Your current route</h2>
                    <div id="startMapContainer"
                        class="mt-5 h-[500px] overflow-hidden rounded-[28px] border border-[#E8D4BE] bg-[#FBF6F1] shadow-inner relative">
                        <div
                            class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,210,146,0.35),_transparent_40%),radial-gradient(circle_at_bottom_right,_rgba(222,164,95,0.16),_transparent_35%)] pointer-events-none">
                        </div>
                        <div id="routeMapFrame" class="h-full w-full"></div>
                        <div id="trailMapMessage" class="absolute inset-x-4 bottom-4 hidden rounded-2xl bg-white/95 px-4 py-3 text-sm text-[#6B5B4B] shadow-lg"></div>
                    </div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-[24px] border border-[#F0D6C4] bg-[#FEFBF8] p-4">
                            <div
                                class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[#FDE7CA] text-[#D98F4F]">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 20s-6-5.333-6-10a6 6 0 0 1 12 0c0 4.667-6 10-6 10Z" stroke="#D98F4F"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M12 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" fill="#D98F4F" />
                                </svg>
                            </div>
                            <p class="mt-4 text-[11px] uppercase tracking-[0.24em] text-[#B08B59]">Total stops</p>
                            <p class="mt-2 text-2xl font-semibold text-[#1F1B19]" data-summary="total-stops">0</p>
                        </div>
                        <div class="rounded-[24px] border border-[#D7E8D0] bg-[#F5FCF5] p-4">
                            <div
                                class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[#DFF2DF] text-[#4A6B31]">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 6L9 17l-5-5" stroke="#4A6B31" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </div>
                            <p class="mt-4 text-[11px] uppercase tracking-[0.24em] text-[#B08B59]">Visited</p>
                            <p class="mt-2 text-2xl font-semibold text-[#1F1B19]" data-summary="visited">0</p>
                        </div>
                        <div class="rounded-[24px] border border-[#E6D4F1] bg-[#FBF2FF] p-4">
                            <div
                                class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[#F1E3FF] text-[#8C56D9]">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 20s-6-5.333-6-10a6 6 0 0 1 12 0c0 4.667-6 10-6 10Z" stroke="#8C56D9"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M12 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" fill="#8C56D9" />
                                </svg>
                            </div>
                            <p class="mt-4 text-[11px] uppercase tracking-[0.24em] text-[#B08B59]">Next stop</p>
                            <p class="mt-2 text-2xl font-semibold text-[#1F1B19]" data-summary="next-stop">None yet</p>
                        </div>
                    </div>
                </section>

                <section class="trail-route-card rounded-[32px] bg-white p-6 shadow-[0_18px_40px_rgba(62,44,23,0.08)]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-[#D98F4F]">Route detail</p>
                            <h2 class="mt-3 text-xl font-semibold text-[#1F1B19]">Selected restaurants</h2>
                        </div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full bg-[#FFF4E7] px-3 py-2 text-sm font-semibold text-[#B8874A]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 8v4l3 3" stroke="#B8874A" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <circle cx="12" cy="12" r="8" stroke="#B8874A" stroke-width="2" />
                            </svg>
                            <span id="estimateTimeText">Estimated time: 0 min</span>
                        </div>
                    </div>
                    <div id="selectedTrailList" class="mt-6 space-y-4"></div>
                    <div
                        class="mt-6 flex flex-col gap-3 rounded-[24px] border border-[#F0D6C4] bg-[#FFFBF7] p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3 text-sm text-[#6B5B4B]">
                            <span
                                class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-[#FDE7CA] text-[#D98F4F]">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 12h14" stroke="#D98F4F" stroke-width="2" stroke-linecap="round" />
                                    <path d="M12 5l7 7-7 7" stroke="#D98F4F" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="font-semibold text-[#1F1B19]">Drag and drop to reorder your stops</span>
                        </div>
                        <button id="clearTrailButton"
                            class="inline-flex h-12 items-center justify-center rounded-full border border-[#E9D7BF] bg-white px-5 text-sm font-semibold text-[#6B553F] shadow-sm transition hover:bg-[#FBF2E4]">Clear
                            Trail</button>
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="trail-actions-card rounded-[32px] bg-white p-6 shadow-[0_18px_40px_rgba(62,44,23,0.08)]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-[#B08B59]">Trail actions</p>
                            <h2 class="mt-3 text-xl font-semibold text-[#1F1B19]">Complete your route</h2>
                        </div>
                        <span id="favoriteStatus"
                            class="rounded-full bg-[#FFF0D9] px-3 py-1 text-sm font-semibold text-[#B8874A]">Not
                            saved</span>
                    </div>
                    <div class="mt-5 space-y-3">
                        <div class="rounded-[20px] border border-[#F0D6C4] bg-[#FEFBF8] p-4">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-[#B08B59]">Total stops</p>
                            <p id="routeTotalStops" class="mt-2 text-2xl font-semibold text-[#1F1B19]">0</p>
                        </div>
                        <div class="rounded-[20px] border border-[#F0D6C4] bg-[#FEFBF8] p-4">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-[#B08B59]">Visited</p>
                            <p id="routeVisitedStops" class="mt-2 text-2xl font-semibold text-[#1F1B19]">0</p>
                        </div>
                        <div class="rounded-[20px] border border-[#F0D6C4] bg-[#FEFBF8] p-4">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-[#B08B59]">Next stop</p>
                            <p id="routeNextStop" class="mt-2 text-2xl font-semibold text-[#1F1B19]">None yet</p>
                        </div>
                    </div>
                    <button id="showAllStopsButton"
                        class="mt-6 flex h-12 w-full items-center justify-center gap-2 rounded-full border border-transparent bg-[#B8874A] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#9c6f33]">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 12h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            <path d="M14 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        Show All Stops
                    </button>
                    <button id="completeAllButton"
                        class="mt-3 flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[#B8874A] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#9c6f33]">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Mark all as complete
                    </button>
                    <button id="closeTrailButton"
                        class="mt-3 hidden h-12 w-full items-center justify-center gap-2 rounded-full bg-[#3D6F55] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#315A45]">
                        Complete Trail
                    </button>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <button id="showPreviousRouteButton"
                            class="inline-flex h-12 items-center justify-center gap-2 rounded-full border border-[#E9D7BF] bg-white px-4 text-sm font-semibold text-[#1F1B19] transition hover:bg-[#F8F0E6]">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 12H4" stroke="#1F1B19" stroke-width="2" stroke-linecap="round" />
                                <path d="M10 18l-6-6 6-6" stroke="#1F1B19" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            Previous Route
                        </button>
                        <button id="showCurrentRouteButton"
                            class="inline-flex h-12 items-center justify-center gap-2 rounded-full border border-[#E9D7BF] bg-white px-4 text-sm font-semibold text-[#1F1B19] transition hover:bg-[#F8F0E6]">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 12h16" stroke="#1F1B19" stroke-width="2" stroke-linecap="round" />
                                <path d="M14 6l6 6-6 6" stroke="#1F1B19" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            Current Route
                        </button>
                    </div>
                    <button id="whatsappShareButton"
                        class="mt-4 flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[#25D366] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1da851]">
                        <span
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-[#25D366]">W</span>
                        Share on WhatsApp
                    </button>
                    <button id="copyLinkButton"
                        class="mt-3 flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[#F7E4C1] px-4 text-sm font-semibold text-[#1F1B19] shadow-sm transition hover:bg-[#E6D2A1]">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8 8h8M8 12h8M8 16h5" stroke="#B8874A" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        Copy Link
                    </button>
                    <button id="openMapsButton"
                        class="mt-3 flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[#4285F4] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#3367d6]">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 3h12l3 3v15l-3 3H6l-3-3V6l3-3Z" stroke="white" stroke-width="2"
                                stroke-linejoin="round" />
                            <path d="M9 13h6" stroke="white" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        Open in Google Maps
                    </button>
                    <button id="addFavoriteButton"
                        class="mt-3 flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[#B8874A] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#9c6f33]">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 20l-7.5 4 2-8.5L1 9.5l8.75-1.3L12 1l2.25 7.2L23 9.5l-5.5 6.1 2 8.5L12 20Z"
                                fill="white" />
                        </svg>
                        Add to Favourite
                    </button>
                    <div class="mt-4 rounded-[20px] border border-[#E9D7BF] bg-white px-4 py-4">
                        <label for="travelModeSelect"
                            class="block text-[11px] uppercase tracking-[0.22em] text-[#B08B59]">Travel mode</label>
                        <select id="travelModeSelect" class="mt-2 w-full bg-transparent text-sm outline-none">
                            <option value="WALKING">Walking</option>
                            <option value="DRIVING">Driving</option>
                            <option value="TRANSIT">Public Transport</option>
                            <option value="BICYCLING">Cycling</option>
                        </select>
                    </div>
                    <button id="exitTrailButton"
                        class="mt-4 flex h-12 w-full items-center justify-center gap-2 rounded-full border border-[#E9D7BF] bg-white px-4 text-sm font-semibold text-[#6B553F] shadow-sm transition hover:bg-[#FBF2E4]">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18l6-6-6-6" stroke="#6B553F" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path d="M15 12H4" stroke="#6B553F" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        Exit
                    </button>
                    <div id="trailDirectionsPanel"
                        class="mt-4 rounded-[24px] border border-[#F0D6C4] bg-[#FFF9F1] px-4 py-4 text-sm text-[#6B5B4B]">
                        <p class="font-semibold text-[#1F1B19]">Showing all destinations in your food trail.</p>
                        <p class="mt-1">Directions will appear after the route is loaded.</p>
                    </div>
                </section>
            </aside>
        </div>

        <div class="trail-tip mt-6 rounded-[24px] border border-[#EADCC8] bg-[#FFFAF5] p-4 text-sm text-[#6B5B4B] shadow-sm sm:flex sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-[#FDE7CA] text-[#D98F4F]">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5v14M5 12h14" stroke="#D98F4F" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </span>
                <p class="font-semibold text-[#1F1B19]">Tip: You can reorder your stops by dragging and dropping.</p>
            </div>
            <a href="#"
                class="mt-3 inline-flex items-center gap-2 font-semibold text-[#B8874A] hover:text-[#9c6f33] sm:mt-0">
                <span>Need help?</span>
                <span class="text-[#B8874A]">View guide</span>
            </a>
        </div>
    </main>
    </div>
    <div id="trailToast"
        class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-[28px] border border-[#D8B58F] bg-white px-5 py-4 text-sm shadow-[0_16px_30px_rgba(62,44,23,0.12)]">
        <p id="trailToastText" class="text-[#1F1B19]"></p>
    </div>
    <div id="trailCompleteModal" class="trail-complete-modal is-hidden" role="dialog" aria-modal="true" aria-labelledby="trailCompleteTitle">
        <div class="w-full max-w-md rounded-[28px] bg-white p-6 shadow-[0_24px_60px_rgba(31,27,25,0.22)]">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#B08B59]">Trail complete</p>
            <h2 id="trailCompleteTitle" class="mt-3 text-2xl font-semibold text-[#1F1B19]">Would you like to save this food trail as a favourite?</h2>
            <p class="mt-3 text-sm leading-6 text-[#6B5B4B]">You finished all selected destinations. Save this route so you can open it again from Food Trails.</p>
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <button id="saveCompletedTrailButton" class="h-12 rounded-full bg-[#B8874A] px-4 text-sm font-semibold text-white hover:bg-[#9c6f33]">Save as Favourite</button>
                <button id="discardCompletedTrailButton" class="h-12 rounded-full border border-[#E9D7BF] bg-white px-4 text-sm font-semibold text-[#6B553F] hover:bg-[#FBF2E4]">Not Now</button>
            </div>
        </div>
    </div>

    <script>
        const currentRouteKey = 'foodtrail-current-route';
        const favoritesKey = 'foodtrails-favorites';
        const completedTrailsKey = 'foodtrail-completed-trails';
        window.foodTrailAuth = {
            isAuthenticated: @json(auth()->check()),
            loginUrl: @json(route('login')),
        };

        const getElement = (id) => document.getElementById(id);

        let trailGoogleMap = null;
        let trailGoogleGeocoder = null;
        let trailGoogleMarkers = [];
        let routeStopMarkers = [];
        let currentLocationMarker = null;
        let trailRoutePolyline = null;
        let directionsService = null;
        let directionsRenderer = null;
        let directionsRendererPrev = null;
        let infoWindow = null;
        let selectedMarkerId = null;
        let routeDisplayMode = 'all';
        let travelMode = 'WALKING';
        let currentLocation = null;
        let hasOptimizedRouteOrder = false;
        const geocodeCache = new Map();

        const initStartTrailMapHelpers = () => {
            if (!window.google?.maps) return;
            const mapEl = getElement('routeMapFrame');
            if (!mapEl || trailGoogleMap) return;

            trailGoogleGeocoder = new google.maps.Geocoder();
            directionsService = new google.maps.DirectionsService();
            trailGoogleMap = new google.maps.Map(mapEl, {
                center: { lat: 3.1390, lng: 101.6869 },
                zoom: 12,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
            });
            directionsRenderer = new google.maps.DirectionsRenderer({
                map: trailGoogleMap,
                suppressMarkers: true,
                preserveViewport: true,
                polylineOptions: {
                    strokeColor: '#D98F4F',
                    strokeOpacity: 0.9,
                    strokeWeight: 6,
                },
            });
            // previous-route renderer (distinct color)
            directionsRendererPrev = new google.maps.DirectionsRenderer({
                map: trailGoogleMap,
                suppressMarkers: true,
                preserveViewport: true,
                polylineOptions: {
                    strokeColor: '#6C757D',
                    strokeOpacity: 0.9,
                    strokeWeight: 6,
                    strokeDasharray: '4 2',
                },
            });
            infoWindow = new google.maps.InfoWindow();
        };

        const showTrailMessage = (text) => {
            const message = getElement('trailMapMessage');
            if (!message) return;
            if (!text) {
                message.classList.add('hidden');
                message.innerText = '';
                return;
            }
            message.classList.remove('hidden');
            message.innerText = text;
        };

        window.addEventListener('googleMapsError', (event) => {
            showTrailMessage(event.detail || 'Google Maps could not be loaded.');
        });

        const showToast = (text) => {
            const toast = getElement('trailToast');
            const toastText = getElement('trailToastText');
            if (!toast || !toastText) return;
            toastText.innerText = text;
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3200);
        };

        const getNextStopLabel = () => {
            if (!routeData.length) return 'None yet';
            const next = routeData.find((item) => !item.visited);
            return next ? next.name : 'All done';
        };

        const updateRouteStats = () => {
            const stopsEl = getElement('routeTotalStops');
            const visitedEl = getElement('routeVisitedStops');
            const nextStopEl = getElement('routeNextStop');
            const closeTrailButton = getElement('closeTrailButton');
            if (stopsEl) stopsEl.innerText = `${routeData.length}`;
            if (visitedEl) visitedEl.innerText = `${visitedCount}`;
            if (nextStopEl) nextStopEl.innerText = getNextStopLabel();
            if (closeTrailButton) {
                const canClose = routeData.length > 0 && visitedCount === routeData.length;
                closeTrailButton.classList.toggle('hidden', !canClose);
                closeTrailButton.classList.toggle('flex', canClose);
            }
        };

        const clearTrailMarkers = () => {
            trailGoogleMarkers.forEach((marker) => marker.setMap(null));
            trailGoogleMarkers = [];
            routeStopMarkers.forEach((marker) => marker.setMap(null));
            routeStopMarkers = [];
            if (currentLocationMarker) {
                currentLocationMarker.setMap(null);
                currentLocationMarker = null;
            }
            if (trailRoutePolyline) {
                trailRoutePolyline.setMap(null);
                trailRoutePolyline = null;
            }
            if (directionsRenderer) {
                directionsRenderer.setDirections({ routes: [] });
            }
            if (directionsRendererPrev) {
                directionsRendererPrev.setDirections({ routes: [] });
            }
        };

        const getRouteStops = (mode = routeDisplayMode) => {
            if (mode === 'all') return routeData.slice();
            if (mode === 'current' || mode === 'next') return routeData.filter((item) => !item.visited);
            if (mode === 'previous') return routeData.filter((item) => item.visited);
            return routeData.slice();
        };

        const setRouteDisplayMode = (mode) => {
            routeDisplayMode = mode;
            updateRouteModeButtons();
            renderTrailMap();
        };

        const setTravelMode = (mode) => {
            travelMode = mode;
            renderTrailMap();
            showTrailMessage(`Travel mode set to ${mode.toLowerCase()}.`);
        };

        const updateRouteModeButtons = () => {
            const baseClasses = 'rounded-full border px-4 py-2 text-sm font-semibold whitespace-nowrap flex-1 min-w-[120px] text-center transition duration-200';
            const activeClasses = 'bg-[#B8874A] text-white border-transparent shadow-[0_8px_20px_rgba(46,32,16,0.12)] hover:bg-[#986f34]';
            const inactiveClasses = 'bg-[#FCF6EA] text-[#6B553F] border-[#D8B58F] hover:bg-[#F3E7D0]';

            const setButtonState = (btn, isActive) => {
                if (!btn) return;
                btn.className = `${baseClasses} ${isActive ? activeClasses : inactiveClasses}`;
            };

            setButtonState(getElement('showAllStopsButton'), routeDisplayMode === 'all');
            setButtonState(getElement('showCurrentRouteButton'), routeDisplayMode === 'current' || routeDisplayMode === 'next');
            setButtonState(getElement('showPreviousRouteButton'), routeDisplayMode === 'previous');
        };

        const getCurrentPosition = () => {
            if (!navigator.geolocation) return Promise.resolve(null);
            return new Promise((resolve) => {
                navigator.geolocation.getCurrentPosition((position) => {
                    resolve({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    });
                }, () => {
                    resolve(null);
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000,
                });
            });
        };

        const getCurrentPositionForRoute = async (showStatus = false) => {
            const position = await getCurrentPosition();
            if (position) {
                currentLocation = position;
                return position;
            }
            if (showStatus) {
                showToast('Current location is unavailable. The route will start from the first stop.');
                showTrailMessage('Location permission is unavailable, so the trail starts from the first restaurant.');
            }
            return null;
        };

        const geocodeRouteItem = (item, locationHint = '') => {
            if (!trailGoogleGeocoder) return Promise.resolve(null);
            const address = `${item.name}, ${item.location}${locationHint ? `, ${locationHint}` : ''}`.trim();
            if (geocodeCache.has(address)) {
                return Promise.resolve({ item, position: geocodeCache.get(address) });
            }
            return new Promise((resolve) => {
                trailGoogleGeocoder.geocode({ address }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        const position = {
                            lat: results[0].geometry.location.lat(),
                            lng: results[0].geometry.location.lng(),
                        };
                        geocodeCache.set(address, position);
                        resolve({ item, position });
                    } else {
                        resolve(null);
                    }
                });
            });
        };

        const getStopLabel = (item) => `${item.name}, ${item.location}`;

        const distanceBetween = (from, to) => {
            const toRad = (value) => value * Math.PI / 180;
            const earthRadiusKm = 6371;
            const dLat = toRad(to.lat - from.lat);
            const dLng = toRad(to.lng - from.lng);
            const lat1 = toRad(from.lat);
            const lat2 = toRad(to.lat);
            const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
            return 2 * earthRadiusKm * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        };

        const orderStopsByNearestNeighbor = (stops, origin, geocodedStops = []) => {
            if (!origin || stops.length < 2) return stops.slice();
            const positionsById = new Map(
                geocodedStops
                    .filter((entry) => entry?.item?.id && entry.position)
                    .map((entry) => [entry.item.id, entry.position])
            );
            const remaining = stops.slice();
            const ordered = [];
            let cursor = origin;

            while (remaining.length) {
                let nearestIndex = 0;
                let nearestDistance = Number.POSITIVE_INFINITY;
                remaining.forEach((item, index) => {
                    const position = positionsById.get(item.id);
                    if (!position) return;
                    const distance = distanceBetween(cursor, position);
                    if (distance < nearestDistance) {
                        nearestDistance = distance;
                        nearestIndex = index;
                    }
                });
                const [next] = remaining.splice(nearestIndex, 1);
                ordered.push(next);
                cursor = positionsById.get(next.id) || cursor;
            }

            return ordered;
        };

        const saveRouteOrder = (orderedStops) => {
            if (!orderedStops.length) return;
            const orderedIds = orderedStops.map((item) => item.id).join('|');
            const currentIds = routeData.map((item) => item.id).join('|');
            if (orderedIds === currentIds) return;
            routeData = orderedStops;
            localStorage.setItem(currentRouteKey, JSON.stringify(routeData));
            updateRouteData();
            renderRouteItems();
            updateHeaderStatus();
        };

        const stripHtml = (value = '') => {
            const div = document.createElement('div');
            div.innerHTML = value;
            return div.textContent || div.innerText || '';
        };

        const renderRouteDirections = (result, routeStops = []) => {
            const panel = getElement('trailDirectionsPanel');
            if (!panel) return;
            const route = result?.routes?.[0];
            if (!route?.legs?.length) {
                panel.innerHTML = `
                    <p class="font-semibold text-[#1F1B19]">Showing all destinations in your food trail.</p>
                    <p class="mt-1">Directions are unavailable right now. The map still shows your destination markers.</p>
                `;
                return;
            }

            const departureStamp = new Date().toLocaleString([], {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
            });
            const transitSteps = [];
            const legHtml = route.legs.map((leg, legIndex) => {
                const destination = routeStops[legIndex] || routeStops[Math.min(legIndex, routeStops.length - 1)];
                const stepHtml = leg.steps.slice(0, 8).map((step) => {
                    const transit = step.transit;
                    if (transit) transitSteps.push(transit);
                    const instruction = stripHtml(step.instructions) || (transit ? 'Use public transport' : 'Continue');
                    const transitMeta = transit ? `
                        <p class="mt-2 text-xs text-[#4A6B31]">
                            ${transit.line?.short_name || transit.line?.name || 'Transit service'}
                            ${transit.departure_stop?.name ? ` from ${transit.departure_stop.name}` : ''}
                            ${transit.arrival_stop?.name ? ` to ${transit.arrival_stop.name}` : ''}
                            ${transit.departure_time?.text ? `, departs ${transit.departure_time.text}` : ''}
                        </p>
                    ` : '';
                    return `
                        <div class="trail-direction-step">
                            <p class="font-semibold text-[#1F1B19]">${step.distance?.text || 'Short distance'} - ${instruction}</p>
                            <p class="mt-1 text-xs text-[#6B5B4B]">${step.duration?.text || 'Duration unavailable'}</p>
                            ${transitMeta}
                        </div>
                    `;
                }).join('');

                return `
                    <div class="rounded-[20px] border border-[#F0D6C4] bg-[#FEFBF8] p-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#B08B59]">Leg ${legIndex + 1}</p>
                        <p class="mt-1 font-semibold text-[#1F1B19]">To ${destination?.name || leg.end_address}</p>
                        <p class="mt-1 text-xs text-[#6B5B4B]">${leg.distance?.text || 'Distance unavailable'} - ${leg.duration?.text || 'Duration unavailable'}</p>
                        <div class="trail-directions-list">${stepHtml}</div>
                    </div>
                `;
            }).join('');

            const transitSummary = transitSteps.length
                ? transitSteps.map((transit) => `
                    <div class="trail-direction-step">
                        <p class="font-semibold text-[#1F1B19]">${transit.line?.short_name || transit.line?.name || 'Transit service'}</p>
                        <p class="mt-1 text-xs text-[#6B5B4B]">Board: ${transit.departure_stop?.name || 'Stop unavailable'}${transit.departure_time?.text ? ` at ${transit.departure_time.text}` : ''}</p>
                        <p class="mt-1 text-xs text-[#6B5B4B]">Get off: ${transit.arrival_stop?.name || 'Stop unavailable'}${transit.arrival_time?.text ? ` at ${transit.arrival_time.text}` : ''}</p>
                    </div>
                `).join('')
                : '<p class="mt-2 text-xs text-[#6B5B4B]">No bus or train segment was returned for this route. Try Public Transport mode to check available scheduled transit options.</p>';

            panel.innerHTML = `
                <p class="font-semibold text-[#1F1B19]">Showing all destinations in your food trail.</p>
                <p class="mt-1 text-xs text-[#6B5B4B]">Route order starts from your current location when GPS is available. Directions generated ${departureStamp}.</p>
                <div class="mt-3 grid gap-3">${legHtml}</div>
                <div class="mt-3 rounded-[20px] border border-[#D7E8D0] bg-[#F5FCF5] p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#4A6B31]">Public transport guidance</p>
                    ${transitSummary}
                    <p class="mt-2 text-xs text-[#6B5B4B]">Transit times are scheduled values returned by Google Maps Directions at request time unless real-time data is available from Google.</p>
                </div>
            `;
        };

        const optimizeRouteOrder = async (origin) => {
            if (!origin || routeData.length < 2 || !directionsService) return routeData.slice();

            const destination = getStopLabel(routeData[routeData.length - 1]);
            const waypoints = routeData.slice(0, -1).map((item) => ({
                location: getStopLabel(item),
                stopover: true,
            }));

            return new Promise((resolve) => {
                directionsService.route({
                    origin,
                    destination,
                    travelMode: google.maps.TravelMode[travelMode],
                    waypoints,
                    optimizeWaypoints: true,
                    transitOptions: travelMode === 'TRANSIT' ? { departureTime: new Date() } : undefined,
                }, (result, status) => {
                    if (status === 'OK' && result?.routes?.[0]) {
                        const order = result.routes[0].waypoint_order || [];
                        const waypointStops = routeData.slice(0, -1);
                        const optimized = order.map((index) => waypointStops[index]);
                        optimized.push(routeData[routeData.length - 1]);
                        resolve(optimized.filter(Boolean));
                        return;
                    }
                    resolve(routeData.slice());
                });
            });
        };

        const placeCurrentLocationMarker = (position) => {
            if (!trailGoogleMap || !position) return;
            if (currentLocationMarker) {
                currentLocationMarker.setMap(null);
            }
            currentLocationMarker = new google.maps.Marker({
                position,
                map: trailGoogleMap,
                title: 'Your location',
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: '#1E88E5',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                    scale: 10,
                },
            });
        };

        const highlightSelectedMarker = (restaurantId, position) => {
            selectedMarkerId = restaurantId;
            routeStopMarkers.forEach((m) => {
                try {
                    const isSelected = m._restaurantId === restaurantId;
                    const scale = isSelected ? 18 : 14;
                    const fill = m._restaurantId && routeData.find(r => r.id === m._restaurantId && r.visited) ? '#4CAF50' : '#4285F4';
                    m.setIcon({
                        path: google.maps.SymbolPath.CIRCLE,
                        fillColor: fill,
                        fillOpacity: 1,
                        strokeColor: '#ffffff',
                        strokeWeight: 3,
                        scale,
                    });
                    if (isSelected && position) {
                        trailGoogleMap.panTo(position);
                        trailGoogleMap.setZoom(Math.max(trailGoogleMap.getZoom(), 15));
                    }
                } catch (e) {
                    // ignore
                }
            });
        };

        const placeStopMarkers = async (stops) => {
            const bounds = new google.maps.LatLngBounds();
            const geocoded = await Promise.all(stops.map((item) => geocodeRouteItem(item, item.location)));
            const positions = [];

            geocoded.forEach((entry, index) => {
                if (!entry || !entry.position) return;
                const position = new google.maps.LatLng(entry.position.lat, entry.position.lng);
                const marker = new google.maps.Marker({
                    position,
                    map: trailGoogleMap,
                    title: entry.item.name,
                    label: {
                        text: `${index + 1}`,
                        color: '#ffffff',
                        fontSize: '13px',
                        fontWeight: '700',
                    },
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        fillColor: entry.item.visited ? '#4CAF50' : '#4285F4',
                        fillOpacity: 1,
                        strokeColor: '#ffffff',
                        strokeWeight: 3,
                        scale: entry.item.id === selectedMarkerId ? 18 : 14,
                    },
                });
                // attach id for lookup
                marker._restaurantId = entry.item.id;
                marker._routeIndex = index;
                marker.addListener('click', () => {
                    const content = `
                        <div style="max-width:220px">
                            <strong>${entry.item.name}</strong>
                            <div style="margin-top:6px"><img src='${entry.item.picture}' style='width:100%;border-radius:8px;object-fit:cover' /></div>
                            <div style="margin-top:6px;font-size:13px;color:#444">${entry.item.location}</div>
                            <div style="margin-top:6px;font-size:13px;color:#666">Stop ${index + 1} • ${entry.item.visited ? 'Visited' : 'Pending'}</div>
                            <div style="margin-top:8px"><button id='infoview-${entry.item.id}' style='background:#D98F4F;border:none;color:white;padding:6px 10px;border-radius:6px;cursor:pointer'>View</button>
                            <button id='infonav-${entry.item.id}' style='margin-left:6px;background:#4285F4;border:none;color:white;padding:6px 10px;border-radius:6px;cursor:pointer'>Navigate</button></div>
                        </div>`;
                    infoWindow.setContent(content);
                    infoWindow.open(trailGoogleMap, marker);
                    highlightSelectedMarker(entry.item.id, position);
                    // attach delegated listeners after InfoWindow is added
                    window.setTimeout(() => {
                        const viewBtn = document.getElementById(`infoview-${entry.item.id}`);
                        const navBtn = document.getElementById(`infonav-${entry.item.id}`);
                        viewBtn?.addEventListener('click', () => {
                            // scroll list to item and highlight
                            const articles = document.querySelectorAll('#selectedTrailList article');
                            const art = Array.from(articles).find(a => a.querySelector('[data-visit-id]')?.getAttribute('data-visit-id') === entry.item.id || a.querySelector('[data-remove-id]')?.getAttribute('data-remove-id') === entry.item.id);
                            if (art) {
                                art.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                art.classList.add('ring-2', 'ring-[#D98F4F]');
                                setTimeout(() => art.classList.remove('ring-2', 'ring-[#D98F4F]'), 2200);
                            }
                        });
                        navBtn?.addEventListener('click', async () => {
                            const origin = await getRouteOriginParam(true);
                            let mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(getStopLabel(entry.item))}&travelmode=${travelMode.toLowerCase()}`;
                            if (origin) mapsUrl += `&origin=${origin}`;
                            window.open(mapsUrl, '_blank');
                        });
                    }, 100);
                });
                routeStopMarkers.push(marker);
                bounds.extend(position);
                positions.push(position);
            });

            if (currentLocation) {
                bounds.extend(currentLocation);
            }

            if (positions.length === 1 && !currentLocation) {
                trailGoogleMap.setCenter(positions[0]);
                trailGoogleMap.setZoom(15);
            } else if (!positions.length && currentLocation) {
                trailGoogleMap.setCenter(currentLocation);
                trailGoogleMap.setZoom(14);
            } else if (positions.length) {
                trailGoogleMap.fitBounds(bounds, 100);
            }

            return positions;
        };

        const renderTrailMap = async () => {
            if (!trailGoogleMap || !trailGoogleGeocoder) return;
            clearTrailMarkers();

            if (!routeData.length) {
                showTrailMessage('No route selected yet. Add a trail from Food Trails.');
                return;
            }
            currentLocation = await getCurrentPositionForRoute(!currentLocation);
            if (currentLocation) placeCurrentLocationMarker(currentLocation);

            if (!hasOptimizedRouteOrder && routeData.length > 1) {
                const geocodedStops = await Promise.all(routeData.map((item) => geocodeRouteItem(item, item.location)));
                saveRouteOrder(orderStopsByNearestNeighbor(routeData, currentLocation, geocodedStops));
                saveRouteOrder(await optimizeRouteOrder(currentLocation));
                hasOptimizedRouteOrder = true;
                showToast('Trail route ordered from your current location.');
            }

            // compute stops depending on selected display mode
            const prevStops = getRouteStops('previous');
            const currStops = getRouteStops('current');

            if (!prevStops.length && !currStops.length) {
                showTrailMessage('All destinations are completed. Great job!');
                placeCurrentLocationMarker(currentLocation);
                return;
            }

            const computeAndRender = (stops, renderer, opts = {}) => {
                if (!stops.length) return;
                const destination = getStopLabel(stops[stops.length - 1]);
                let origin;
                let waypoints = [];
                if (currentLocation && opts.useCurrentAsOrigin) {
                    origin = currentLocation;
                    waypoints = stops.slice(0, -1).map((item) => ({ location: getStopLabel(item), stopover: true }));
                } else {
                    origin = getStopLabel(stops[0]);
                    waypoints = stops.slice(1, -1).map((item) => ({ location: getStopLabel(item), stopover: true }));
                }

                try {
                    renderer.setOptions({ polylineOptions: opts.polylineOptions });
                    directionsService.route({
                        origin,
                        destination,
                        travelMode: google.maps.TravelMode[travelMode],
                        waypoints: waypoints.length ? waypoints : undefined,
                        optimizeWaypoints: false,
                        transitOptions: travelMode === 'TRANSIT' ? { departureTime: new Date() } : undefined,
                    }, (result, status) => {
                        if (status === 'OK') {
                            renderer.setDirections(result);
                            if (opts.showDirections) {
                                renderRouteDirections(result, stops);
                            }
                        }
                    });
                } catch (e) {
                    // ignore route failures
                }
            };

            // when viewing previous route only
            if (routeDisplayMode === 'previous') {
                if (prevStops.length) {
                    computeAndRender(prevStops, directionsRendererPrev, { useCurrentAsOrigin: false, polylineOptions: { strokeColor: '#6C757D', strokeOpacity: 0.9, strokeWeight: 6 } });
                    showTrailMessage('Showing previously completed route.');
                } else {
                    showTrailMessage('No previous route available yet.');
                }
                await placeStopMarkers(routeData);
                return;
            }

            // when viewing current route only (upcoming stops)
            if (routeDisplayMode === 'current' || routeDisplayMode === 'next') {
                if (currStops.length) {
                    computeAndRender(currStops, directionsRenderer, { useCurrentAsOrigin: true, showDirections: true, polylineOptions: { strokeColor: '#D98F4F', strokeOpacity: 0.9, strokeWeight: 6 } });
                    showTrailMessage('Showing current route to your next stops.');
                } else {
                    showTrailMessage('No upcoming stops.');
                }
                await placeStopMarkers(routeData);
                return;
            }

            // 'all' mode: render previous and current separately (if any)
            if (routeDisplayMode === 'all') {
                if (prevStops.length) computeAndRender(prevStops, directionsRendererPrev, { useCurrentAsOrigin: false, polylineOptions: { strokeColor: '#6C757D', strokeOpacity: 0.9, strokeWeight: 6 } });
                if (currStops.length) computeAndRender(currStops, directionsRenderer, { useCurrentAsOrigin: true, showDirections: true, polylineOptions: { strokeColor: '#D98F4F', strokeOpacity: 0.9, strokeWeight: 6 } });
                showTrailMessage(currentLocation ? 'Showing all destinations from your current location.' : 'Showing all destinations in your food trail.');
                await placeStopMarkers(routeData);
                return;
            }

            // fallback: show markers only
            await placeStopMarkers(routeData);
        };

        const toggleRestaurantVisited = (restaurantId) => {
            routeData = routeData.map((item) => {
                if (item.id === restaurantId) {
                    return { ...item, visited: !item.visited };
                }
                return item;
            });
            localStorage.setItem(currentRouteKey, JSON.stringify(routeData));
            updateRouteData();
            renderRouteItems();
            updateHeaderStatus();
            updateRouteStats();
            renderTrailMap();
            showToast('Stop status updated.');
        };

        window.initStartTrailMap = async () => {
            initStartTrailMapHelpers();
            updateRouteModeButtons();
            await renderTrailMap();
        };

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
            updateRouteStats();
            if (routeData.length) showTrailMessage('');
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
                card.className = 'trail-stop-card';
                card.draggable = true;
                card.dataset.restaurantId = item.id;
                card.innerHTML = `
                    <button class="trail-drag-handle" type="button" aria-label="Drag to reorder">::</button>
                    <span class="trail-stop-number ${item.visited ? 'is-visited' : `stop-${index + 1}`}">${index + 1}</span>
                    <img src="${item.picture}" alt="${item.name}" class="trail-stop-image" />
                    <div class="trail-stop-content">
                        <div class="trail-stop-title-row">
                            <p class="trail-stop-title">${item.name}</p>
                            ${index === 0 && !item.visited ? '<span class="trail-next-badge">Next stop</span>' : ''}
                        </div>
                        <div class="trail-stop-meta">
                            <span>${item.location}</span>
                            <span>${item.distance} km</span>
                            <span>${Math.max(5, Math.round(item.distance * 8))} min ${travelMode === 'WALKING' ? 'walk' : 'travel'}</span>
                        </div>
                    </div>
                    <div class="trail-stop-actions">
                        <button data-visit-id="${item.id}" class="trail-visit-button ${item.visited ? 'is-visited' : ''}">${item.visited ? 'Visited' : 'Pending'}</button>
                        <button data-remove-id="${item.id}" class="trail-remove-button" aria-label="Remove ${item.name}">x</button>
                    </div>
                `;
                const visitButton = card.querySelector('[data-visit-id]');
                visitButton?.addEventListener('click', (event) => {
                    event.stopPropagation();
                    toggleRestaurantVisited(item.id);
                });
                const removeButton = card.querySelector('[data-remove-id]');
                removeButton?.addEventListener('click', (event) => {
                    event.stopPropagation();
                    routeData = routeData.filter(r => r.id !== item.id);
                    localStorage.setItem(currentRouteKey, JSON.stringify(routeData));
                    hasOptimizedRouteOrder = true;
                    updateRouteData();
                    renderRouteItems();
                    updateHeaderStatus();
                    renderTrailMap();
                    showToast('Removed stop from route.');
                });
                card.addEventListener('dragstart', (event) => {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', item.id);
                    card.classList.add('is-dragging');
                });
                card.addEventListener('dragend', () => card.classList.remove('is-dragging'));
                card.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                    card.classList.add('is-drop-target');
                });
                card.addEventListener('dragleave', () => card.classList.remove('is-drop-target'));
                card.addEventListener('drop', (event) => {
                    event.preventDefault();
                    card.classList.remove('is-drop-target');
                    const sourceId = event.dataTransfer.getData('text/plain');
                    if (!sourceId || sourceId === item.id) return;
                    const sourceIndex = routeData.findIndex((restaurant) => restaurant.id === sourceId);
                    const targetIndex = routeData.findIndex((restaurant) => restaurant.id === item.id);
                    if (sourceIndex < 0 || targetIndex < 0) return;
                    const [movedRestaurant] = routeData.splice(sourceIndex, 1);
                    routeData.splice(targetIndex, 0, movedRestaurant);
                    localStorage.setItem(currentRouteKey, JSON.stringify(routeData));
                    hasOptimizedRouteOrder = true;
                    updateRouteData();
                    renderRouteItems();
                    updateHeaderStatus();
                    renderTrailMap();
                    showToast('Route order updated.');
                });
                selectedTrailList.appendChild(card);
            });
        };
        const getRouteShareText = () => {
            if (!routeData.length) {
                return 'I am planning a food trail with Warisan Makan.';
            }
            return `My food trail includes ${routeData.length} stops and ${estimatedTime} min estimated time. Stops: ${routeData.map((item) => item.name).join(', ')}.`;
        };

        const getRouteOriginParam = async (showStatus = false) => {
            const origin = await getCurrentPositionForRoute(showStatus);
            return origin ? encodeURIComponent(`${origin.lat},${origin.lng}`) : '';
        };

        const getGoogleMapsRouteLink = async (showStatus = false) => {
            if (!routeData.length) return '';

            const currentOrigin = await getRouteOriginParam(showStatus);
            const origin = currentOrigin || encodeURIComponent(getStopLabel(routeData[0]));
            const destination = encodeURIComponent(getStopLabel(routeData[routeData.length - 1]));
            const waypointStops = currentOrigin ? routeData.slice(0, -1) : routeData.slice(1, -1);
            const waypointList = waypointStops.map((item) => encodeURIComponent(getStopLabel(item)));
            const waypoints = waypointList.join('%7C');
            let mapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}&travelmode=${travelMode.toLowerCase()}`;
            if (waypoints) {
                mapsUrl += `&waypoints=${waypoints}`;
            }
            return mapsUrl;
        };

        const shareWhatsapp = async () => {
            if (!routeData.length) {
                showToast('Add restaurants to your trail first.');
                return;
            }
            const mapsLink = await getGoogleMapsRouteLink(true);
            const text = encodeURIComponent(`Here is my WarisanMakan Food Trail route:\n\n${routeData.map((item) => item.name).join(' -> ')}\n\nOpen the route in Google Maps:\n${mapsLink}`);
            window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank');
        };

        const copyLink = async () => {
            if (!routeData.length) {
                showToast('Nothing to copy. Add route stops first.');
                return;
            }
            const mapsLink = await getGoogleMapsRouteLink(true);
            const shareText = `My WarisanMakan route: ${routeData.map((item) => item.name).join(' -> ')}\nOpen in Google Maps: ${mapsLink}`;
            try {
                await navigator.clipboard.writeText(shareText);
                showToast('Route link copied to clipboard.');
            } catch (error) {
                console.error(error);
                showToast('Unable to copy link.');
            }
        };

        const openGoogleMaps = async () => {
            if (!routeData.length) {
                showToast('Add restaurants to your trail first.');
                return;
            }
            const mapsUrl = await getGoogleMapsRouteLink(true);
            window.open(mapsUrl, '_blank');
        };

        const saveFavorite = (name = 'My heritage food trail') => {
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
            return favorite;
        };

        const saveFavoriteTrail = () => {
            const name = prompt('Name this favorite trail', 'My heritage food trail');
            if (!name) return;

            saveFavorite(name);
            alert('Trail saved to favorites.');
        };

        const openCompleteTrailModal = () => {
            if (!routeData.length) {
                showToast('Add restaurants to your trail first.');
                return;
            }
            if (visitedCount !== routeData.length) {
                showToast('Complete every stop before closing the trail.');
                return;
            }
            getElement('trailCompleteModal')?.classList.remove('is-hidden');
        };

        const finishCompletedTrail = (saveAsFavorite) => {
            const completedTrails = JSON.parse(localStorage.getItem(completedTrailsKey) || '[]');
            completedTrails.push({
                id: `completed-${Date.now()}`,
                completedAt: new Date().toISOString(),
                stops: routeData,
            });
            localStorage.setItem(completedTrailsKey, JSON.stringify(completedTrails));

            if (saveAsFavorite) {
                saveFavorite('Completed heritage food trail');
                showToast(window.foodTrailAuth.isAuthenticated ? 'Trail saved to favourites.' : 'Trail saved to local favourites.');
            }

            localStorage.removeItem(currentRouteKey);
            getElement('trailCompleteModal')?.classList.add('is-hidden');
            window.setTimeout(() => {
                window.location.href = '/foodtrails';
            }, 650);
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
            renderTrailMap();
            showToast(message);
        };

        const clearTrail = () => {
            if (!routeData.length) {
                showToast('Your trail is already empty.');
                return;
            }
            if (!window.confirm('Clear all restaurants from this trail?')) return;
            routeData = [];
            localStorage.removeItem(currentRouteKey);
            updateRouteData();
            renderRouteItems();
            updateHeaderStatus();
            renderTrailMap();
            showToast('Trail cleared.');
        };

        const exitTrail = () => {
            window.location.href = '/foodtrails';
        };

        document.addEventListener('DOMContentLoaded', async () => {
            updateRouteData();
            if (window.google?.maps) {
                initStartTrailMapHelpers();
            }
            updateRouteData();
            renderRouteItems();
            updateHeaderStatus();
            if (window.googleMapsLoaded) {
                await window.initStartTrailMap();
            }

            getElement('whatsappShareButton')?.addEventListener('click', shareWhatsapp);
            getElement('copyLinkButton')?.addEventListener('click', copyLink);
            getElement('openMapsButton')?.addEventListener('click', openGoogleMaps);
            getElement('addFavoriteButton')?.addEventListener('click', saveFavoriteTrail);
            getElement('completeTrailButton')?.addEventListener('click', toggleCompleteTrail);
            getElement('completeAllButton')?.addEventListener('click', toggleCompleteTrail);
            getElement('closeTrailButton')?.addEventListener('click', openCompleteTrailModal);
            getElement('saveCompletedTrailButton')?.addEventListener('click', () => finishCompletedTrail(true));
            getElement('discardCompletedTrailButton')?.addEventListener('click', () => finishCompletedTrail(false));
            getElement('clearTrailButton')?.addEventListener('click', clearTrail);
            getElement('exitTrailButton')?.addEventListener('click', exitTrail);
            getElement('showAllStopsButton')?.addEventListener('click', () => setRouteDisplayMode('all'));
            getElement('showCurrentRouteButton')?.addEventListener('click', () => setRouteDisplayMode('current'));
            getElement('showPreviousRouteButton')?.addEventListener('click', () => setRouteDisplayMode('previous'));
            getElement('travelModeSelect')?.addEventListener('change', (event) => setTravelMode(event.target.value));
            updateRouteStats();
        });
    </script>
</body>

</html>
