@extends('admin.layout')

@section('title', 'Heritage Shops')
@section('page-title', 'Heritage Shops')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Registry</p>
            <h1>Heritage Shop records</h1>
            <p>Review and manage heritage food businesses, images, locations, and public cultural profiles.</p>
        </div>
        <div class="actions">
            <a class="button primary small" href="{{ route('admin.heritage-shops.create') }}">Add shop</a>
        </div>
    </header>

    @if (session('success'))
        <div class="status-banner success">{{ session('success') }}</div>
    @endif

    <section class="panel" style="margin-bottom:18px;">
        <form method="GET" action="{{ route('admin.heritage-shops.index') }}">
            <div class="filters four" style="margin-bottom:0;">
                <div class="field">
                    <label for="search">Search</label>
                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Name, address, category, story">
                </div>
                <div class="field">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        @foreach ($categories as $option)
                            <option value="{{ $option }}" @selected(strtolower($category) === strtolower($option))>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="state">State / region</label>
                    <select id="state" name="state">
                        <option value="">All states</option>
                        @foreach ($states as $option)
                            <option value="{{ $option }}" @selected(strtolower($state) === strtolower($option))>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="sort">Sort by</label>
                    <select id="sort" name="sort">
                        <option value="name_asc" @selected($sort === 'name_asc')>Name A–Z</option>
                        <option value="name_desc" @selected($sort === 'name_desc')>Name Z–A</option>
                        <option value="newest" @selected($sort === 'newest')>Newest</option>
                        <option value="oldest" @selected($sort === 'oldest')>Oldest</option>
                    </select>
                </div>
                <div class="actions filter-action">
                    <button class="button primary small" type="submit">Apply filters</button>
                    <a class="button secondary small" href="{{ route('admin.heritage-shops.index') }}">Reset</a>
                </div>
            </div>
        </form>
    </section>

    <section class="panel">
        @if ($shops->isEmpty())
            <div class="empty-state">
                <h2>No heritage shops found</h2>
                <p>{{ $search || $category || $state ? 'No records match the current search or filters. Try a broader keyword or clear the filters.' : 'Create the first Heritage Shop record to begin building the public registry.' }}</p>
                @if ($search || $category || $state)
                    <a class="button secondary" href="{{ route('admin.heritage-shops.index') }}">Clear search and filters</a>
                @else
                    <a class="button primary" href="{{ route('admin.heritage-shops.create') }}">Create shop</a>
                @endif
            </div>
        @else
            <div class="record-list">
                @foreach ($shops as $shop)
                    @php
                        $primaryImage = $shop->images->first();
                        $primaryImageUrl = $primaryImage ? $imageService->url($primaryImage) : null;
                    @endphp
                    <article class="record-card">
                        <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                            @if ($primaryImageUrl)
                                <img src="{{ $primaryImageUrl }}" alt="{{ $shop->shop_name }}" loading="lazy" style="width:74px;height:74px;flex:0 0 auto;object-fit:cover;border:1px solid var(--line);border-radius:10px;background:#f1e5d7;" onerror="this.remove()">
                            @else
                                <div aria-label="No image available" style="width:74px;height:74px;flex:0 0 auto;display:grid;place-items:center;border:1px dashed var(--line);border-radius:10px;color:var(--muted);background:#f8efe5;font-size:.68rem;text-align:center;">No image</div>
                            @endif
                            <div style="min-width:0;">
                                <strong style="display:block; font-size:1.05rem; margin-bottom:6px;">{{ $shop->shop_name }}</strong>
                                <p>{{ $shop->location ?: 'No address provided' }}</p>
                                <div class="record-meta">
                                    <span>{{ $shop->primary_food_category ?: 'Uncategorized' }}</span>
                                    <span>{{ $shop->state ?: 'State not provided' }}</span>
                                    <span>{{ $shop->images->count() }} image{{ $shop->images->count() === 1 ? '' : 's' }}</span>
                                    <span>{{ $shop->publish_status ?: 'draft' }}</span>
                                    <span>{{ optional($shop->created_at)->format('d M Y') ?: 'Unknown date' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="record-actions">
                            <a class="button secondary small" href="{{ route('heritage-shops.show', $shop) }}" target="_blank" rel="noopener noreferrer">View</a>
                            <a class="button primary small" href="{{ route('admin.heritage-shops.edit', $shop) }}">Edit</a>
                        </div>
                    </article>
                @endforeach
            </div>
            @if ($shops->hasPages())
                <div class="pagination">{{ $shops->links() }}</div>
            @endif
        @endif
    </section>
@endsection
