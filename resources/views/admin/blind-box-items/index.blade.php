@extends('admin.layout')

@section('title', 'Blind Box Items')
@section('page-title', 'Blind Box Items')

@push('styles')
    <style>
        .back-nav { margin-bottom: 22px; }
        .back-btn { 
            display: inline-flex; 
            align-items: center; 
            padding: 8px 16px 8px 12px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 10px;
            color: var(--ink); 
            text-decoration: none; 
            font-size: 0.82rem; 
            font-weight: 800; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        .back-btn:hover { 
            background: var(--canvas);
            border-color: var(--accent);
            color: var(--accent);
            transform: translateX(-4px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.08);
        }
        .back-btn svg { margin-right: 8px; transition: transform 0.2s ease; }
        .back-btn:hover svg { transform: translateX(-2px); }

        .catalog-summary { display: grid; grid-template-columns: repeat(2, minmax(0, 180px)); gap: 10px; margin-bottom: 22px; }
        .catalog-stat { padding: 15px; border: 1px solid var(--line); border-radius: 12px; background: #fff; }
        .catalog-stat strong { display: block; font-family: Georgia, serif; font-size: 1.65rem; }
        .catalog-stat span { color: var(--muted); font-size: .76rem; }
        .catalog-toolbar { display: flex; flex-wrap: nowrap; align-items: end; gap: 10px; margin-bottom: 14px; }
        .catalog-toolbar input { flex: 1 1 280px; min-width: 0; }
        .catalog-toolbar select { flex: 0 0 190px; }
        .catalog-toolbar .toolbar-actions { display: flex; gap: 8px; flex-shrink: 0; }
        @media (max-width: 620px) { .catalog-toolbar { flex-wrap: wrap; } .catalog-toolbar select { flex: 1 1 150px; } }
        .management-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; align-items: start; }
        .shop-list { display: grid; gap: 10px; margin-top: 16px; }
        .shop-card { display: grid; grid-template-columns: 76px minmax(0, 1fr); gap: 13px; padding: 12px; border: 1px solid var(--line); border-radius: 12px; background: #fff; }
        .shop-card img { width: 76px; height: 76px; object-fit: cover; border-radius: 9px; background: var(--canvas); }
        .shop-card h3 { font-family: Georgia, serif; font-size: 1rem; }
        .shop-card p { margin: 5px 0 0; color: var(--muted); font-size: .8rem; line-height: 1.4; }
        .shop-card-head { display: flex; align-items: start; justify-content: space-between; gap: 8px; }
        .shop-actions { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 10px; }
        .add-form { display: flex; flex-wrap: wrap; gap: 7px; align-items: end; margin-top: 10px; }
        .catalog-toolbar .button.ghost { display: inline-flex; align-items: center; justify-content: center; border: 1px dashed var(--line, #d5dce4); border-radius: 10px; padding: 9px 16px; font-size: .82rem; color: var(--muted); text-decoration: none; cursor: pointer; }
        .catalog-toolbar .button.ghost:hover { border-color: rgba(49, 93, 131, .45); color: #315d83; background: rgba(49, 93, 131, .05); }
        .add-form .field { flex: 1 1 150px; }
        .helper { margin: 0; color: var(--muted); font-size: .84rem; line-height: 1.5; }
        .section-heading { display: flex; align-items: start; justify-content: space-between; gap: 12px; }
        @media (max-width: 900px) { .management-grid { grid-template-columns: 1fr; } }
        @media (max-width: 620px) { .catalog-toolbar { grid-template-columns: 1fr; } .catalog-summary { grid-template-columns: 1fr 1fr; } }
    </style>
@endpush

@section('content')
    <div class="back-nav">
        <a href="{{ route('admin.dashboard') }}" class="back-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Back to Dashboard
        </a>
    </div>

    <div class="page-header">
        <div>
            <p class="eyebrow">Blind Box module</p>
            <h1>Recommendation pool</h1>
            <p>Shops not yet selected appear on the left; shops in the Blind Box appear on the right. Removing a shop returns it to the left list, where it can be re-added anytime.</p>
        </div>
        <div class="actions">
            <a class="button secondary" href="{{ route('blind-box.index') }}">Preview user view</a>
        </div>
    </div>

    <div class="catalog-summary">
        <div class="catalog-stat"><strong>{{ count($availableShops) }}</strong><span>Shops not yet selected</span></div>
        <div class="catalog-stat"><strong>{{ $activeShops }}</strong><span>Shops in the Blind Box</span></div>
    </div>

    <form class="catalog-toolbar" method="GET" action="{{ route('admin.blind-box-items.index') }}">
        <input name="search" value="{{ $search }}" placeholder="Search shop or state" aria-label="Search shop or state">
        <select name="category" aria-label="Filter recommendation category">
            <option value="">All categories</option>
            @foreach($categories as $option)
                <option value="{{ $option }}" @selected($category === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <span class="toolbar-actions">
            <button class="button secondary" type="submit">Filter</button>
            <a class="button ghost" href="{{ route('admin.blind-box-items.index') }}">Reset</a>
        </span>
    </form>

    <div class="management-grid">
        {{-- LEFT column: shops not yet selected --}}
        <section class="panel">
            <div class="section-heading">
                <div>
                    <h2>Shops Pending Selection</h2>
                    <p class="helper">These are source catalog records not currently included. Select a category to add one.</p>
                </div>
            </div>
            <div class="shop-list">
                @forelse($availableShops as $shop)
                    <article class="shop-card">
                        <img src="{{ $shop['image'] }}" alt="{{ $shop['name'] }}">
                        <div>
                            <div class="shop-card-head"><h3>{{ $shop['name'] }}</h3><span class="badge badge-draft">Available</span></div>
                            <p>{{ $shop['state'] }} · {{ $shop['year'] }}</p>
                            <form class="add-form" method="POST" action="{{ route('admin.blind-box-items.add', $shop['source_id']) }}">
                                @csrf
                                <div class="field"><label for="category-{{ $shop['source_id'] }}">Recommendation category</label><select id="category-{{ $shop['source_id'] }}" name="category" required><option value="">Select category</option>@foreach($categories as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                                <button class="button primary small" type="submit">Add to Blind Box</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="empty-state"><h2>All source shops are included</h2><p>When cloud shop records are added later, they will appear here automatically.</p></div>
                @endforelse
            </div>
        </section>

        {{-- RIGHT column: shops in the Blind Box --}}
        <section class="panel">
            <div class="section-heading">
                <div>
                    <h2>Shops Included in the Blind Box</h2>
                    <p class="helper">These shops can be recommended. Click a shop name to edit its category, or remove it to return it to the list on the left.</p>
                </div>
            </div>
            <div class="shop-list">
                @forelse($insideShops as $shop)
                    <article class="shop-card">
                        <img src="{{ $shop['image'] }}" alt="{{ $shop['name'] }}">
                        <div>
                            <div class="shop-card-head">
                                <div><h3><a href="{{ route('admin.blind-box-items.edit', $shop['id']) }}">{{ $shop['name'] }}</a></h3><span class="badge badge-approved">In the Blind Box</span></div>
                                <span class="badge badge-under_review">{{ $shop['category'] }}</span>
                            </div>
                            <p>{{ $shop['state'] }} · {{ $shop['year'] }}</p>
                            <div class="shop-actions">
                                <a class="button secondary small" href="{{ route('admin.blind-box-items.edit', $shop['id']) }}">Edit shop</a>
                                <form method="POST" action="{{ route('admin.blind-box-items.toggle', $shop['id']) }}" onsubmit="return confirm('Remove this shop from the Blind Box? It will return to the list on the left.');">
                                    @csrf
                                    @method('PATCH')
                                    <button class="button danger small" type="submit">Remove from reveals</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state"><h2>No shops included</h2><p>Add a source shop from the left panel.</p></div>
                @endforelse
            </div>
        </section>
    </div>

    <script>
        // Auto-hide status banners after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const banners = document.querySelectorAll('.status-banner');
            banners.forEach(banner => {
                setTimeout(() => {
                    banner.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    banner.style.opacity = '0';
                    banner.style.transform = 'translateY(-10px)';
                    setTimeout(() => banner.remove(), 500);
                }, 3000);
            });
        });
    </script>
@endsection
