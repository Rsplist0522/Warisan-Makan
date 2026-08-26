@extends('admin.layout')

@section('title', 'Food Catalog · '.$shop->shop_name)
@section('page-title', 'Food catalog')

@section('content')
    @php
        $activeCount = $foodItems->where('is_active', true)->count();
        $hiddenCount = $foodItems->where('is_active', false)->count();
    @endphp

    <header class="page-header">
        <div>
            <p class="eyebrow">HeritageShop · food catalog</p>
            <h1>{{ $shop->shop_name }}</h1>
            <p>Build the menu visitors see when they open this shop. Preserve each dish’s name, story, availability, price, and image as verified catalog data.</p>
        </div>
        <div class="actions">
            <a class="button secondary small" href="{{ route('admin.heritage-shops.edit', $shop) }}">Back to shop</a>
            <a class="button primary small" href="{{ route('heritage-shops.show', ['id' => $shop->id]) }}" target="_blank" rel="noopener noreferrer">Preview public page ↗</a>
        </div>
    </header>

    @if (session('success'))
        <div class="status-banner success" role="status">{{ session('success') }}</div>
    @endif

    <section class="food-catalog-summary" aria-label="Food catalog summary">
        <div><strong>{{ $foodItems->count() }}</strong><span>Total records</span></div>
        <div><strong>{{ $activeCount }}</strong><span>Visible to visitors</span></div>
        <div><strong>{{ $hiddenCount }}</strong><span>Hidden / archived</span></div>
        <div class="food-catalog-note"><strong>Why this matters</strong><span>UNESCO describes food heritage as living practices transmitted across generations—not only a list of dishes. Add the story behind every signature item.</span></div>
    </section>

    <section class="panel food-add-panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Add a verified dish</p>
                <h2>New food item</h2>
                <p class="muted">Only saved items marked visible will appear on the public shop profile.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.heritage-shops.food-items.store', $shop) }}" enctype="multipart/form-data">
            @csrf
            <div class="food-form-grid">
                <div class="field"><label for="new-name">Food name <span aria-hidden="true">*</span></label><input id="new-name" name="name" required maxlength="255" placeholder="Example: Nasi Lemak Warisan"></div>
                <div class="field"><label for="new-category">Category</label><input id="new-category" name="category" maxlength="120" placeholder="Breakfast, kuih, noodles…"></div>
                <div class="field"><label for="new-price">Price / range</label><input id="new-price" name="price" maxlength="80" placeholder="RM 12.00"></div>
                <div class="field"><label for="new-availability">Availability</label><input id="new-availability" name="availability" maxlength="120" placeholder="Daily · Until sold out"></div>
                <div class="field full"><label for="new-description">Description</label><textarea id="new-description" name="description" maxlength="2000" placeholder="What is served, and what should a visitor notice?"></textarea></div>
                <div class="field full"><label for="new-significance">Heritage significance</label><textarea id="new-significance" name="heritage_significance" maxlength="3000" placeholder="How is this dish connected to the family, community, place, technique, or memory?"></textarea></div>
                <div class="field"><label for="new-image">Food photo</label><input id="new-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" data-heritage-image-input data-max-bytes="{{ config('heritage_shop.max_image_bytes', 1048576) }}"><span class="help-text">JPG, PNG, or WebP · max {{ number_format(config('heritage_shop.max_image_kb', 1024) / 1024, 2) }} MB</span><span class="image-size-error status-banner error" data-image-error style="display:none; margin:0; padding:8px 10px;"></span></div>
                <div class="field"><label for="new-order">Display order</label><input id="new-order" name="display_order" type="number" min="0" max="9999" value="0"></div>
                <div class="field food-check-field"><label><input type="checkbox" name="is_active" value="1" checked> Show this item publicly</label></div>
            </div>
            <div class="actions"><button class="button primary" type="submit">Add to food catalog</button></div>
        </form>
    </section>

    <section class="panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Manage every dish</p>
                <h2>Recorded food items</h2>
                <p class="muted">Edit, hide, restore, or archive an item without changing the shop’s main profile.</p>
            </div>
        </div>
        @if ($foodItems->isEmpty())
            <div class="empty-state"><h2>No food items yet</h2><p>Add the first verified dish above. It will appear in the public Heritage foods &amp; menu section after you save it as visible.</p></div>
        @else
            <div class="food-item-admin-list">
                @foreach ($foodItems as $item)
                    <article class="food-item-admin-card">
                        <div class="food-item-admin-header">
                            <div>
                                <span class="food-item-order">#{{ $loop->iteration }}</span>
                                <h3>{{ $item->name }}</h3>
                                <span class="badge {{ $item->is_active ? 'badge-approved' : 'badge-draft' }}">{{ $item->is_active ? 'Visible' : 'Hidden' }}</span>
                                @if ($item->image_path)
                                    <img class="food-item-thumb" src="{{ route('admin.heritage-shops.food-items.image', [$shop, $item]) }}" alt="{{ $item->name }} food photo" loading="lazy" onerror="this.style.display='none'">
                                @endif
                            </div>
                            <div class="food-item-admin-actions">
                                <form method="POST" action="{{ route('admin.heritage-shops.food-items.toggle', [$shop, $item]) }}">@csrf @method('PATCH')<button class="button secondary small" type="submit">{{ $item->is_active ? 'Hide' : 'Show' }}</button></form>
                                <form method="POST" action="{{ route('admin.heritage-shops.food-items.destroy', [$shop, $item]) }}" onsubmit="return confirm('Archive this food item from the catalog?');">@csrf @method('DELETE')<button class="button danger small" type="submit">Archive</button></form>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.heritage-shops.food-items.update', [$shop, $item]) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="food-form-grid compact">
                                <div class="field"><label for="name-{{ $item->id }}">Food name</label><input id="name-{{ $item->id }}" name="name" value="{{ $item->name }}" required maxlength="255"></div>
                                <div class="field"><label for="category-{{ $item->id }}">Category</label><input id="category-{{ $item->id }}" name="category" value="{{ $item->category }}" maxlength="120"></div>
                                <div class="field"><label for="price-{{ $item->id }}">Price / range</label><input id="price-{{ $item->id }}" name="price" value="{{ $item->price }}" maxlength="80"></div>
                                <div class="field"><label for="availability-{{ $item->id }}">Availability</label><input id="availability-{{ $item->id }}" name="availability" value="{{ $item->availability }}" maxlength="120"></div>
                                <div class="field full"><label for="description-{{ $item->id }}">Description</label><textarea id="description-{{ $item->id }}" name="description" maxlength="2000">{{ $item->description }}</textarea></div>
                                <div class="field full"><label for="significance-{{ $item->id }}">Heritage significance</label><textarea id="significance-{{ $item->id }}" name="heritage_significance" maxlength="3000">{{ $item->heritage_significance }}</textarea></div>
                                <div class="field"><label for="image-{{ $item->id }}">Replace photo</label><input id="image-{{ $item->id }}" name="image" type="file" accept="image/jpeg,image/png,image/webp" data-heritage-image-input data-max-bytes="{{ config('heritage_shop.max_image_bytes', 1048576) }}"><span class="help-text">Leave empty to keep the current photo. New photo max {{ number_format(config('heritage_shop.max_image_kb', 1024) / 1024, 2) }} MB.</span><span class="image-size-error status-banner error" data-image-error style="display:none; margin:0; padding:8px 10px;"></span></div>
                                <div class="field"><label for="order-{{ $item->id }}">Display order</label><input id="order-{{ $item->id }}" name="display_order" type="number" min="0" max="9999" value="{{ $item->display_order }}"></div>
                                <div class="field food-check-field"><label><input type="checkbox" name="is_active" value="1" @checked($item->is_active)> Show this item publicly</label></div>
                            </div>
                            <div class="actions"><button class="button primary small" type="submit">Save item</button></div>
                        </form>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection

@push('styles')
<style>
    .food-catalog-summary { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)) minmax(260px, 2fr); gap:10px; margin-bottom:18px; }
    .food-catalog-summary > div { display:grid; gap:4px; padding:16px; border:1px solid var(--line); border-radius:14px; background:var(--panel); }
    .food-catalog-summary strong { color:var(--accent); font-family:Georgia,serif; font-size:1.35rem; }
    .food-catalog-summary span { color:var(--muted); font-size:.78rem; line-height:1.45; }
    .food-catalog-summary .food-catalog-note { background:linear-gradient(135deg,#fff8eb,#fffdf9); }
    .food-catalog-summary .food-catalog-note strong { font-family:inherit; font-size:.82rem; }
    .section-heading { display:flex; justify-content:space-between; gap:16px; align-items:start; margin-bottom:18px; }
    .section-heading h2 { margin:0 0 5px; font-family:Georgia,serif; }
    .section-heading p { margin:0; }
    .food-add-panel { margin-bottom:18px; }
    .food-form-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:13px; }
    .food-form-grid .full { grid-column:1 / -1; }
    .food-form-grid textarea { min-height:90px; }
    .food-check-field { align-self:end; padding-bottom:13px; }
    .food-check-field label { display:flex; align-items:center; gap:8px; }
    .food-item-admin-list { display:grid; gap:15px; }
    .food-item-admin-card { padding:18px; border:1px solid var(--line); border-radius:14px; background:#fff; }
    .food-item-admin-header { display:flex; justify-content:space-between; gap:18px; align-items:start; margin-bottom:16px; }
    .food-item-admin-header h3 { display:inline-block; margin:0 8px 7px 5px; color:var(--accent); font-family:Georgia,serif; font-size:1.2rem; }
    .food-item-order { color:var(--gold); font-size:.75rem; font-weight:900; }
    .food-item-thumb { display:block; width:100px; height:72px; margin-top:10px; border-radius:10px; object-fit:cover; border:1px solid var(--line); background:#f1e5d7; }
    .food-item-admin-actions { display:flex; flex-wrap:wrap; justify-content:end; gap:7px; }
    .food-item-admin-actions form { margin:0; }
    .help-text { color:var(--muted); font-size:.74rem; line-height:1.4; }
    @media (max-width:900px) { .food-catalog-summary { grid-template-columns:repeat(2,minmax(0,1fr)); } .food-form-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:560px) { .food-catalog-summary, .food-form-grid { grid-template-columns:1fr; } .food-form-grid .full { grid-column:auto; } .food-item-admin-header { display:grid; } .food-item-admin-actions { justify-content:start; } }
</style>
@endpush

<script>
(() => {
    const maxLabel = '{{ number_format(config('heritage_shop.max_image_kb', 1024) / 1024, 2) }} MB';
    document.querySelectorAll('[data-heritage-image-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const error = input.closest('.field')?.querySelector('[data-image-error]');
            const file = input.files?.[0];
            if (file && file.size > Number(input.dataset.maxBytes || 1048576)) {
                if (error) {
                    error.textContent = 'This image is larger than the HeritageShop limit of ' + maxLabel + '.';
                    error.style.display = 'block';
                }
                input.value = '';
                return;
            }
            if (error) error.style.display = 'none';
        });
    });
})();
</script>
