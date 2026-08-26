@extends('admin.layout')

@section('title', 'Heritage Shops')
@section('page-title', 'Heritage Shops')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Registry</p>
            <h1>Heritage Shop records</h1>
                        <p>Review and manage verified heritage food businesses, their living stories, galleries, and visitor-facing food catalogs.</p>

        </div>
        <div class="actions">
            <a class="button primary small" href="{{ route('admin.heritage-shops.create') }}">Add shop</a>
        </div>
    </header>

        @if (session('success'))
        <div class="status-banner success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="status-banner error">{{ session('error') }}</div>
    @endif

    <section class="heritage-admin-spotlight" aria-label="HeritageShop management highlights">
        <div><span class="spotlight-label">Registry health</span><strong>{{ $shops->total() }}</strong><span>records in this view</span></div>
        <div><span class="spotlight-label">Food coverage</span><strong>{{ $shops->sum(fn ($shop) => $shop->foodItems->count()) }}</strong><span>food items on this page</span></div>
        <div><span class="spotlight-label">Public storytelling</span><strong>{{ $shops->filter(fn ($shop) => filled($shop->heritage_story))->count() }}</strong><span>profiles with stories</span></div>
        <div class="spotlight-message"><strong>Make every dish memorable.</strong><span>Open a shop’s food catalog to add names, prices, photos, availability, and heritage significance.</span></div>
        </section>

    <section class="panel discovery-panel" aria-labelledby="discovery-heading">
        <div class="discovery-heading">
            <div>
                <p class="eyebrow">Curated intake</p>
                <h2 id="discovery-heading">Discover several shops from one permitted list page</h2>
                <p class="help-text">Use an official, user-authorized, or explicitly permitted directory/list page. HeritageShop previews same-host candidates only, never publishes automatically, and marks duplicates before import. Restricted directories such as TripAdvisor are not supported for automated copying.</p>
            </div>
            <span class="discovery-badge">Preview first · Draft only</span>
        </div>
        <div class="discovery-controls">
            <div class="field">
                <label for="discovery_url">Permitted list-page URL</label>
                <input id="discovery_url" type="url" placeholder="https://your-authorized-source.example/shops" autocomplete="url">
            </div>
            <div class="field">
                <label for="discovery_limit">Maximum shops</label>
                <select id="discovery_limit">
                    @foreach ([3, 6, 10] as $limit)
                        <option value="{{ $limit }}" @selected($limit === 6)>{{ $limit }} results</option>
                    @endforeach
                </select>
            </div>
            <div class="actions" style="align-self:end;">
                <button class="button primary small" id="discover-button" type="button">Find shops</button>
            </div>
        </div>
        <div id="discovery-status" class="status-banner" style="display:none; margin-top:14px;" role="status" aria-live="polite"></div>
        <div id="discovery-results" class="discovery-results" style="display:none;" aria-live="polite"></div>
        <form id="discovery-import-form" method="POST" action="{{ route('admin.heritage-shops.discover.import') }}" style="display:none; margin-top:14px;">
            @csrf
            <input type="hidden" id="discovery-list-source" name="list_url" value="">
            <div id="discovery-hidden-fields"></div>
            <button class="button primary small" type="submit">Import selected as drafts</button>
        </form>
    </section>



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
                                    <span>{{ $shop->foodItems->count() }} food item{{ $shop->foodItems->count() === 1 ? '' : 's' }}</span>
                                    <span>{{ $shop->publish_status ?: 'draft' }}</span>

                                    <span>{{ optional($shop->created_at)->format('d M Y') ?: 'Unknown date' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="record-actions">
                                                        <a class="button secondary small" href="{{ route('heritage-shops.show', $shop) }}" target="_blank" rel="noopener noreferrer">View</a>
                            <a class="button secondary small" href="{{ route('admin.heritage-shops.food-items.index', $shop) }}">Manage food</a>
                                                        <a class="button primary small" href="{{ route('admin.heritage-shops.edit', $shop) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.heritage-shops.destroy', $shop) }}" onsubmit="return confirm('Delete {{ addslashes($shop->shop_name) }} permanently? This removes its HeritageShop gallery and food catalog. If visitor passport history is linked, the system will safely block deletion.');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="button danger small" type="submit">Delete</button>
                            </form>

                        </div>

                    </article>
                @endforeach
            </div>
            @if ($shops->hasPages())
                <div class="pagination">{{ $shops->links() }}</div>
            @endif
        @endif
    </section>
@push('styles')
<style>
    .heritage-admin-spotlight { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)) minmax(260px,2fr); gap:10px; margin-bottom:18px; }
    .heritage-admin-spotlight > div { display:grid; gap:4px; padding:15px; border:1px solid var(--line); border-radius:14px; background:var(--panel); }
    .heritage-admin-spotlight strong { color:var(--accent); font-family:Georgia,serif; font-size:1.35rem; }
    .heritage-admin-spotlight span { color:var(--muted); font-size:.76rem; line-height:1.4; }
    .heritage-admin-spotlight .spotlight-label { color:var(--gold); font-size:.68rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; }
    .heritage-admin-spotlight .spotlight-message { background:linear-gradient(135deg,#fff8eb,#fffdf9); }
    .heritage-admin-spotlight .spotlight-message strong { color:var(--accent); font-family:inherit; font-size:.92rem; }
    @media (max-width:900px) { .heritage-admin-spotlight { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:560px) { .heritage-admin-spotlight { grid-template-columns:1fr; } }
    .discovery-panel { background:linear-gradient(135deg,#fffaf0,#fffdf9); }
    .discovery-heading { display:flex; justify-content:space-between; gap:18px; align-items:flex-start; }
    .discovery-heading h2 { margin:0 0 6px; font-family:Georgia,serif; color:var(--accent); }
    .discovery-badge { flex:0 0 auto; border:1px solid rgba(168,116,44,.25); color:var(--gold); background:#fff; border-radius:999px; padding:7px 10px; font-size:.72rem; font-weight:800; }
    .discovery-controls { display:grid; grid-template-columns:minmax(0,1fr) 150px auto; gap:12px; align-items:end; margin-top:16px; }
    .discovery-results { display:grid; gap:10px; margin-top:16px; }
    .discovery-result { display:flex; gap:12px; align-items:flex-start; padding:12px; border:1px solid var(--line); border-radius:12px; background:rgba(255,255,255,.8); }
    .discovery-result input { margin-top:4px; }
    .discovery-result strong { display:block; color:var(--accent); }
    .discovery-result small { display:block; color:var(--muted); overflow-wrap:anywhere; margin-top:3px; }
    .discovery-result .duplicate-label { color:#9a4d32; font-weight:800; font-size:.75rem; }
    @media (max-width:760px) { .discovery-heading { display:grid; } .discovery-controls { grid-template-columns:1fr; } .discovery-result { align-items:flex-start; } }

</style>
@endpush

<script>
(() => {
    const button = document.getElementById('discover-button');
    const urlInput = document.getElementById('discovery_url');
    const limitInput = document.getElementById('discovery_limit');
    const status = document.getElementById('discovery-status');
    const results = document.getElementById('discovery-results');
    const importForm = document.getElementById('discovery-import-form');
    const hiddenFields = document.getElementById('discovery-hidden-fields');
    const listSource = document.getElementById('discovery-list-source');
    if (!button || !urlInput || !limitInput || !status || !results || !importForm || !hiddenFields || !listSource) return;

    let discoveredItems = [];
    const setStatus = (type, message) => {
        status.className = 'status-banner ' + (type === 'error' ? 'error' : 'success');
        status.textContent = message;
        status.style.display = 'block';
    };

    const renderResults = (data) => {
        discoveredItems = Array.isArray(data.items) ? data.items : [];
        listSource.value = String(data.source_url || urlInput.value || '');
        results.replaceChildren();
        importForm.style.display = 'none';
        hiddenFields.replaceChildren();
        if (!discoveredItems.length) {
            setStatus('error', 'No reviewable same-site shop pages were found. Try an authorized list page with clear shop or restaurant links.');
            results.style.display = 'none';
            return;
        }

        discoveredItems.forEach((item, index) => {
            const row = document.createElement('label');
            row.className = 'discovery-result';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'discovery-select';
            checkbox.dataset.index = String(index);
            checkbox.checked = item.can_import === true;
            checkbox.disabled = item.duplicate === true;
            const content = document.createElement('span');
            const title = document.createElement('strong');
            title.textContent = String(item.name || item.shop_name || 'Unnamed shop');
            content.appendChild(title);
            const source = document.createElement('small');
            source.textContent = String(item.source_url || 'No source URL');
            content.appendChild(source);
            if (item.duplicate) {
                const duplicate = document.createElement('small');
                duplicate.className = 'duplicate-label';
                duplicate.textContent = 'Already imported' + (item.duplicate_shop_name ? ' as ' + item.duplicate_shop_name : '') + ' — skipped';
                content.appendChild(duplicate);
            } else {
                const review = document.createElement('small');
                review.textContent = 'Will be created as Draft for manual review';
                content.appendChild(review);
            }
            row.appendChild(checkbox);
            row.appendChild(content);
            results.appendChild(row);
        });
        results.style.display = 'grid';
        importForm.style.display = 'block';
        setStatus('success', 'Found ' + discoveredItems.length + ' reviewable shop result(s). Confirm your selection below; no record is published automatically.');
    };

    button.addEventListener('click', async () => {
        const url = urlInput.value.trim();
        if (!url) { setStatus('error', 'Enter a permitted list-page URL first.'); return; }
        button.disabled = true;
        button.textContent = 'Finding…';
        setStatus('success', 'Checking the permitted list page and preparing a review preview…');
        try {
            const response = await fetch('{{ route('admin.heritage-shops.discover') }}', {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                body: JSON.stringify({url, limit: Number(limitInput.value)})
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'The list page could not be discovered safely.');
            renderResults(data);
        } catch (error) {
            results.style.display = 'none';
            importForm.style.display = 'none';
            setStatus('error', error.message || 'The discovery request failed.');
        } finally {
            button.disabled = false;
            button.textContent = 'Find shops';
        }
    });

    importForm.addEventListener('submit', (event) => {
        hiddenFields.replaceChildren();
        const selected = Array.from(results.querySelectorAll('.discovery-select:checked'))
            .map((checkbox) => discoveredItems[Number(checkbox.dataset.index)])
            .filter((item) => item && item.can_import === true);
        if (!selected.length) {
            event.preventDefault();
            setStatus('error', 'Select at least one new shop. Duplicate results are disabled automatically.');
            return;
        }
        selected.forEach((item, index) => {
            const safeFields = {
                name: item.name || item.shop_name || '',
                source_url: item.source_url || '',
                primary_food_category: item.primary_food_category || '',
                establishment_year: item.establishment_year || '',
                heritage_story: item.heritage_story || item.description || '',
                contact_number: item.contact_number || '',
                address: item.address || '',
                city: item.city || '',
                state: item.state || '',
                postal_code: item.postal_code || '',
                food_items: JSON.stringify(Array.isArray(item.food_items) ? item.food_items : (Array.isArray(item.menu) ? item.menu : []))
            };
            Object.entries(safeFields).forEach(([key, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'items[' + index + '][' + key + ']';
                input.value = String(value);
                hiddenFields.appendChild(input);
            });
        });
    });
})();
</script>

@endsection
