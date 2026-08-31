<style>
    .cc-food-hours-grid {
        display: grid;
        grid-template-columns: minmax(0, 3fr) minmax(220px, 2fr);
        gap: 22px;
        align-items: start;
        margin-top: 16px;
    }

    .cc-food-column,
    .cc-hours-column {
        min-width: 0;
    }

    .cc-food-column h3,
    .cc-hours-column h3 {
        margin: 0 0 10px;
        color: var(--wm-muted, var(--muted));
        font-size: .76rem;
        font-family: inherit;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .cc-food-list {
        display: grid;
        gap: 12px;
    }

    .cc-food-item {
        display: grid;
        grid-template-columns: 100px minmax(0, 1fr);
        gap: 14px;
        align-items: start;
        min-width: 0;
    }

    .cc-food-item.no-image {
        grid-template-columns: minmax(0, 1fr);
    }

    .cc-food-item img {
        width: 100px;
        height: 100px;
        border: 1px solid var(--wm-border, var(--line));
        border-radius: 10px;
        background: #f1e5d7;
        object-fit: cover;
    }

    .cc-food-item strong {
        display: block;
        color: var(--wm-text, var(--ink));
    }

    .cc-food-item p {
        margin: 5px 0 0;
        color: var(--wm-muted, var(--muted));
        font-size: .9rem;
        line-height: 1.5;
    }

    .cc-food-price {
        display: inline-flex;
        width: fit-content;
        margin-top: 6px;
        padding: 4px 8px;
        border-radius: 999px;
        color: #3f2a0d;
        background: rgba(200, 148, 50, .2);
        font-size: .78rem;
        font-weight: 850;
    }

    .cc-hours-list {
        display: grid;
        gap: 6px;
    }

    .cc-hour-row {
        display: grid;
        grid-template-columns: minmax(96px, .9fr) minmax(0, 1.1fr);
        gap: 12px;
        align-items: center;
        padding: 7px 0;
        border-bottom: 1px solid var(--wm-border, var(--line));
    }

    .cc-hour-row:last-child {
        border-bottom: 0;
    }

    .cc-hour-day {
        color: var(--wm-text, var(--ink));
        font-weight: 800;
    }

    .cc-hour-time {
        color: var(--wm-muted, var(--muted));
        text-align: right;
    }

    .cc-hour-time.is-closed {
        justify-self: end;
        border-radius: 999px;
        padding: 3px 8px;
        color: #72520d;
        background: rgba(199, 154, 40, .16);
        font-size: .78rem;
        font-weight: 850;
    }

    @media (max-width: 780px) {
        .cc-food-hours-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 520px) {
        .cc-food-item {
            grid-template-columns: 82px minmax(0, 1fr);
        }

        .cc-food-item img {
            width: 82px;
            height: 82px;
        }

        .cc-hour-row {
            grid-template-columns: minmax(88px, .8fr) minmax(0, 1.2fr);
        }
    }
</style>

<section class="panel">
    <h2>Food items and operating hours</h2>
    <div class="cc-food-hours-grid">
        <div class="cc-food-column">
            <h3>Food items</h3>
            <div class="cc-food-list">
                @forelse ($contribution->food_items ?? [] as $item)
                    @php($foodItemImageUrl = $contribution->foodItemImageUrl($item['image_path'] ?? null))
                    <article class="cc-food-item {{ $foodItemImageUrl ? '' : 'no-image' }}">
                        @if ($foodItemImageUrl)
                            <img src="{{ $foodItemImageUrl }}" alt="{{ $item['name'] ?? 'Food item image' }}">
                        @endif
                        <div>
                            <strong>{{ $item['name'] ?: 'Unnamed item' }}</strong>
                            @if (filled($item['price'] ?? null))
                                <span class="cc-food-price">{{ $item['price'] }}</span>
                            @endif
                            @if (filled($item['desc'] ?? null))
                                <p>{{ $item['desc'] }}</p>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="muted">Not provided</p>
                @endforelse
            </div>
        </div>

        <div class="cc-hours-column">
            <h3>Operating hours</h3>
            <div class="cc-hours-list">
                @forelse ($contribution->operating_hours ?? [] as $schedule)
                    @php($isClosed = filter_var($schedule['closed'] ?? false, FILTER_VALIDATE_BOOLEAN))
                    <div class="cc-hour-row">
                        <span class="cc-hour-day">{{ $schedule['day'] ?? 'Day' }}</span>
                        <span class="cc-hour-time {{ $isClosed ? 'is-closed' : '' }}">
                            {{ $isClosed ? 'Closed' : (($schedule['open'] ?: '-').' - '.($schedule['close'] ?: '-')) }}
                        </span>
                    </div>
                @empty
                    <p class="muted">Not provided</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
