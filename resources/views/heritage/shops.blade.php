<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heritage Shop Listing</title>
    <style>
        :root {
            --red: #8c1f1f;
            --red-deep: #6f1717;
            --cream: #f7efe5;
            --gold: #c9971d;
            --text: #3d2b21;
            --muted: #6b4d3d;
            --line: #e4d0b7;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--cream);
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px;
        }

        .hero {
            background: linear-gradient(135deg, #fbf5eb 0%, #f1dfc4 100%);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 10px 26px rgba(70, 30, 10, 0.06);
        }

        .eyebrow {
            display: inline-block;
            background: rgba(201,151,29,0.16);
            color: var(--red-deep);
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
        }

        h1, h2, h3 {
            margin: 0 0 8px;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--red-deep);
        }

        h1 { font-size: clamp(24px, 3.2vw, 34px); }
        h2 { font-size: 20px; }
        p { margin: 0 0 10px; color: var(--muted); }

        .search-box {
            background: rgba(255,255,255,0.7);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 16px;
            margin: 18px 0 20px;
        }

        form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: end;
        }

        label { font-size: 13px; font-weight: 700; color: var(--red-deep); }
        input, select, button {
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--line);
            font-size: 14px;
        }

        input, select { background: #fffdfa; width: 220px; }
        button {
            background: var(--gold);
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 700;
        }

        .reset-link {
            color: var(--red-deep);
            text-decoration: none;
            font-weight: 700;
        }

        .shop-list { display: grid; gap: 14px; margin-top: 16px; }
        .shop-item {
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 16px;
            background: #fffdfa;
        }

        .shop-item h3 { margin-bottom: 6px; color: var(--red-deep); }
        .meta {
            color: var(--gold);
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .empty {
            border: 1px dashed var(--line);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            background: rgba(255,255,255,0.5);
        }

        @media (max-width: 640px) {
            .container { padding: 16px; }
            input, select { width: 100%; }
            form { flex-direction: column; align-items: stretch; }
            button { width: 100%; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="hero">
        <div class="eyebrow">WarisanMakan • Heritage Shop Module</div>
        <h1>Discover participating heritage food shops</h1>
        <p>Browse traditional vendors, search by keyword, and explore the stories behind Malaysia's heritage food culture.</p>

        <div class="search-box">
            <form action="{{ route('heritage-shops.index') }}" method="GET">
                <div>
                    <label for="search">Search</label><br>
                    <input id="search" name="search" type="text" value="{{ old('search', $search) }}" placeholder="Search shops">
                </div>
                <div>
                    <label for="category">Category</label><br>
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        <option value="traditional noodles" {{ strtolower($category) === 'traditional noodles' ? 'selected' : '' }}>Traditional Noodles</option>
                        <option value="street food" {{ strtolower($category) === 'street food' ? 'selected' : '' }}>Street Food</option>
                        <option value="desserts" {{ strtolower($category) === 'desserts' ? 'selected' : '' }}>Desserts</option>
                        <option value="rice dishes" {{ strtolower($category) === 'rice dishes' ? 'selected' : '' }}>Rice Dishes</option>
                    </select>
                </div>
                <button type="submit">Search</button>
                <a class="reset-link" href="{{ route('heritage-shops.index') }}">Reset</a>
            </form>
        </div>

        @if (isset($shop))
            <div class="card-detail" style="margin-top:16px;">
                <a class="back" href="{{ route('heritage-shops.index') }}" style="color:var(--red-deep);font-weight:700;text-decoration:none;">← Back to list</a>
                <h1 style="margin-top:12px">{{ $shop['name'] }}</h1>
                <div class="meta">{{ $shop['location'] }} · {{ $shop['category'] }}</div>

                <p>{{ $shop['description'] }}</p>

                @if (!empty($shop['founder']) || !empty($shop['establishment_year']))
                    <p>
                        @if (!empty($shop['founder']))
                            <strong>Founder:</strong> {{ $shop['founder'] }}
                        @endif
                        @if (!empty($shop['founder']) && !empty($shop['establishment_year']))
                            ·
                        @endif
                        @if (!empty($shop['establishment_year']))
                            <strong>Established:</strong> {{ $shop['establishment_year'] }}
                        @endif
                    </p>
                @endif

                @if (!empty($shop['operating_hours']))
                    <p><strong>Operating hours:</strong> {{ $shop['operating_hours'] }}</p>
                @endif

                @if (!empty($shop['participating_since']))
                    <p><strong>Participating since:</strong> {{ $shop['participating_since'] }}</p>
                @endif

                @if (!empty($shop['highlight']))
                    <p><strong>Highlight:</strong> {{ $shop['highlight'] }}</p>
                @endif

                @if (!empty($shop['country']))
                    <p><strong>Country:</strong> {{ ucfirst($shop['country']) }}</p>
                @endif

                @if (!empty($shop['source_url']))
                    <p><strong>Source:</strong> <a href="{{ $shop['source_url'] }}" target="_blank" rel="noopener noreferrer">View original</a></p>
                @endif

                <h2 style="margin-top:16px">About</h2>
                <p>{{ $shop['description'] ?? 'No description available.' }}</p>

                <h2 style="margin-top:16px">Heritage story</h2>
                <p>{{ $shop['heritage_story'] ?? 'No story available.' }}</p>
            </div>
        @else
            @if (count($shops) === 0)
                <div class="empty">
                    <strong>No participating shops found.</strong>
                    <p>Try a different keyword or category to explore the heritage food network.</p>
                </div>
            @else
                <div class="shop-list">
                    @foreach ($shops as $shop)
                        <div class="shop-item">
                            <h3>{{ $shop['name'] }}</h3>
                            <div class="meta">{{ $shop['location'] }} · {{ $shop['category'] }}</div>
                            <p>{{ $shop['description'] }}</p>
                            <p><strong>Participating since:</strong> {{ $shop['participating_since'] }}</p>
                            <p><strong>Highlight:</strong> {{ $shop['highlight'] }}</p>

                            <p><a href="{{ route('heritage-shops.show', ['id' => $shop->id]) }}">View details</a></p>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</div>
</body>
</html>
