@extends('admin.layout')

@section('title', $mode === 'create' ? 'Add Heritage Shop' : 'Edit Heritage Shop')
@section('page-title', $mode === 'create' ? 'Add heritage shop' : 'Edit heritage shop')

@section('content')
    @php
        $shopName = old('shop_name', $shop->shop_name ?? '');
        $shopAddress = old('address', $shop->address ?? '');
        $shopCity = old('city', $shop->city ?? '');
        $shopState = old('state', $shop->state ?? '');
        $shopPostal = old('postal_code', $shop->postal_code ?? '');
        $shopCategory = old('primary_food_category', $shop->primary_food_category ?? '');
        $shopDescription = old('heritage_story', $shop->heritage_story ?? '');
        $shopContact = old('contact_number', $shop->contact_number ?? '');
        $shopHours = old('operating_hours', $shop->exists ? $shop->operatingHoursText() : '');
        $storedStatus = in_array($shop->publish_status, \App\Models\HeritageShop::ADMIN_STATUSES, true)
            ? $shop->publish_status
            : \App\Models\HeritageShop::STATUS_DRAFT;
        $shopStatus = old('publish_status', $storedStatus);
        $shopSourceUrl = old('source_url', $shop->source_url ?? '');
        $normalizedQuickItems = $shop->relationLoaded('foodItems')
            ? $shop->foodItems->where('is_active', true)->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'desc' => $item->description,
            ])->values()->all()
            : [];
        $quickItemFallback = $normalizedQuickItems !== []
            ? $normalizedQuickItems
            : ($shop->exists ? [] : [['name' => '', 'price' => '', 'desc' => '']]);
        $shopFoodItems = old('food_items', $quickItemFallback);
    @endphp

    <header class="page-header">
        <div>
            <p class="eyebrow">Heritage registry</p>
            <h1>{{ $mode === 'create' ? 'Add a heritage shop' : 'Edit heritage shop' }}</h1>
            <p>{{ $mode === 'create' ? 'Create a verified public record and review any crawler suggestions before publishing.' : 'Update the public listing and rewrite any imported data as needed.' }}</p>
        </div>
        <div class="actions">
        @if ($mode === 'edit')
                <a class="button secondary small" href="{{ route('admin.heritage-shops.preview', $shop) }}">Preview profile</a>
                <a class="button secondary small" href="{{ route('admin.heritage-shops.food-items.index', $shop) }}">Manage food catalog</a>
        @endif
            <a class="button secondary small" href="{{ route('admin.heritage-shops.index') }}">Back to list</a>
        </div>
    </header>

    @if ($errors->any())
        <div class="status-banner error">
            <strong>Please fix the highlighted fields and try again.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="status-banner success">{{ session('success') }}</div>
    @endif

    <form id="heritage-shop-form" method="POST" action="{{ $mode === 'create' ? route('admin.heritage-shops.store') : route('admin.heritage-shops.update', $shop) }}" enctype="multipart/form-data">
        @csrf
        @foreach ((array) old('crawler_images', []) as $crawlerImage)
            <input type="hidden" name="crawler_images[]" value="{{ $crawlerImage }}">
        @endforeach
        @if ($mode === 'edit')
            @method('PUT')
            <input type="hidden" name="version" value="{{ $shop->version }}">
        @endif

        <section class="panel" style="margin-bottom:18px;">
            <div class="crawl-controls">
                <div class="field">
                    <label for="crawl_url">Crawl source URL</label>
                    <input id="crawl_url" name="crawl_url" type="url" placeholder="https://example.com/heritage-restaurant" value="{{ old('crawl_url', $shopSourceUrl) }}">
                </div>
                <div class="field" style="align-self:end;">
                    <button class="button secondary small" id="crawl-button" type="button">Fetch / Crawl</button>
                </div>
                <div class="field crawl-feedback">
                    <div id="crawl-status" class="status-banner" style="display:none; margin:0; padding:10px 12px;"></div>
                    <div id="research-sources" class="help-text" style="display:none; margin-top:8px;"></div>
                </div>
            </div>
        </section>

        <div class="detail-grid">
            <div class="detail-stack">
                <section class="panel">
                    <h2>Shop profile</h2>
                    <p class="section-guidance" id="shop-profile-requirements">Shop name is always required and displayed publicly. Heritage story is required when Status is Published.</p>
                    <div class="filters four" style="margin-top:16px;">
                        <div class="field">
                            <label for="shop_name">Shop name <span aria-hidden="true">*</span></label>
                            <input id="shop_name" name="shop_name" type="text" value="{{ $shopName }}" required maxlength="255" aria-describedby="shop-profile-requirements" @if ($errors->has('shop_name')) aria-invalid="true" @endif>
                            @error('shop_name')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="field">
                            <label for="primary_food_category">Primary category</label>
                            <input id="primary_food_category" name="primary_food_category" type="text" list="existing-category-options" value="{{ $shopCategory }}" maxlength="255">
                            <datalist id="existing-category-options">
                                @foreach ($categorySuggestions as $categorySuggestion)
                                    <option value="{{ $categorySuggestion }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="field">
                            <label for="establishment_year">Established year</label>
                            <input id="establishment_year" name="establishment_year" type="number" min="1000" max="{{ now()->year }}" value="{{ old('establishment_year', $shop->establishment_year ?? '') }}">
                        </div>
                        <div class="field">
                            <label for="contact_number">Contact number</label>
                            <input id="contact_number" name="contact_number" type="tel" value="{{ $shopContact }}" maxlength="30">
                        </div>
                        <div class="field" style="grid-column:1 / -1;">
                            <label for="source_url">Source URL</label>
                            <input id="source_url" name="source_url" type="url" value="{{ $shopSourceUrl }}" maxlength="500">
                        </div>
                        <div class="field" style="grid-column:1 / -1;">
                            <label for="heritage_story">Heritage story <span class="required-marker" data-required-marker="published" @if ($shopStatus !== 'published') hidden @endif aria-hidden="true">*</span></label>
                            <textarea id="heritage_story" name="heritage_story" maxlength="10000" aria-describedby="shop-profile-requirements" @if ($errors->has('heritage_story')) aria-invalid="true" @endif>{{ $shopDescription }}</textarea>
                            @error('heritage_story')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="field" style="grid-column:1 / -1;">
                            <label for="operating_hours">Operating hours</label>
                            <textarea id="operating_hours" name="operating_hours" maxlength="2000" aria-describedby="operating-hours-help" @if ($errors->has('operating_hours')) aria-invalid="true" @endif>{{ $shopHours }}</textarea>
                            <p class="help-text field-guidance" id="operating-hours-help">Enter one day or schedule per line, for example: Monday: 11:30–14:30; 17:30–22:30.</p>
                            @error('operating_hours')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <h2>Location and ownership</h2>
                    <p class="section-guidance" id="location-requirements">Address and City are required when Status is Published.</p>
                    <div class="filters four location-grid" style="margin-top:16px;">
                        <div class="field location-address" style="grid-column:1 / -1;">
                            <label for="address">Address <span class="required-marker" data-required-marker="published" @if ($shopStatus !== 'published') hidden @endif aria-hidden="true">*</span></label>
                            <input id="address" name="address" type="text" value="{{ $shopAddress }}" maxlength="500" aria-describedby="location-requirements" @if ($errors->has('address')) aria-invalid="true" @endif>
                            @error('address')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="field location-city">
                            <label for="city">City <span class="required-marker" data-required-marker="published" @if ($shopStatus !== 'published') hidden @endif aria-hidden="true">*</span></label>
                            <input id="city" name="city" type="text" value="{{ $shopCity }}" maxlength="100" aria-describedby="location-requirements" @if ($errors->has('city')) aria-invalid="true" @endif>
                            @error('city')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="field location-state">
                            <label for="state">State</label>
                            <input id="state" name="state" type="text" value="{{ $shopState }}" maxlength="100">
                        </div>
                        <div class="field location-postal">
                            <label for="postal_code">Postal code</label>
                            <input id="postal_code" name="postal_code" type="text" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="{{ $shopPostal }}">
                        </div>
                        <div class="field location-latitude">
                            <label for="latitude">Latitude</label>
                            <input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" value="{{ old('latitude', $shop->latitude ?? '') }}">
                        </div>
                        <div class="field location-longitude">
                            <label for="longitude">Longitude</label>
                            <input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" value="{{ old('longitude', $shop->longitude ?? '') }}">
                        </div>
                        <div class="field">
                            <label for="founder_name">Founder</label>
                            <input id="founder_name" name="founder_name" type="text" value="{{ old('founder_name', $shop->founder_name ?? '') }}" maxlength="255">
                        </div>
                        <div class="field">
                            <label for="current_owner_name">Current owner</label>
                            <input id="current_owner_name" name="current_owner_name" type="text" value="{{ old('current_owner_name', $shop->current_owner_name ?? '') }}" maxlength="255">
                        </div>
                        <div class="field" style="grid-column:1 / -1;">
                            <label for="founder_background">Founder background</label>
                            <textarea id="founder_background" name="founder_background" maxlength="5000">{{ old('founder_background', $shop->founder_background ?? '') }}</textarea>
                        </div>
                        <div class="field" style="grid-column:1 / -1;">
                            <label for="current_owner_details">Current owner details</label>
                            <textarea id="current_owner_details" name="current_owner_details" maxlength="5000">{{ old('current_owner_details', $shop->current_owner_details ?? '') }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:8px;">
                        <div>
                            <h2 style="margin:0 0 5px;">Menu items</h2>
                            <p class="help-text" style="margin:0;">Quick profile entries appear on the public page. For photos, availability, categories, and heritage significance, use the dedicated food catalog.</p>
                        </div>
                        <div class="actions">
                            @if ($mode === 'edit')
                                <a class="button secondary small" href="{{ route('admin.heritage-shops.food-items.index', $shop) }}">Open catalog</a>
                            @endif
                            <button type="button" class="button secondary small" id="add-menu-item">Add quick item</button>
                        </div>
                    </div>
                    <div id="menu-items-container" style="display:grid; gap:12px;"></div>
                </section>
            </div>

            <aside class="detail-stack">
                <section class="panel">
                    <h2>Heritage Shop Gallery</h2>
                    <p class="section-guidance">Upload up to {{ config('heritage_shop.max_gallery_images', 10) }} images in total. Accepted formats: JPG, JPEG, PNG and WebP. Maximum size: {{ number_format(config('heritage_shop.max_image_kb', 2048) / 1024, 0) }} MB per image.</p>
                    <p class="gallery-count" aria-live="polite"><strong id="gallery-count">{{ $shop->images->count() }} / {{ config('heritage_shop.max_gallery_images', 10) }} images</strong><span id="gallery-remaining"></span></p>
                    <div class="field" style="margin-top:16px;">
                        <label for="images">Upload gallery images</label>
                        <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple data-max-bytes="{{ config('heritage_shop.max_image_bytes', 2097152) }}" data-max-gallery="{{ config('heritage_shop.max_gallery_images', 10) }}">
                        <p class="help-text" id="image-upload-help">JPG, JPEG, PNG, or WebP only. Maximum {{ number_format(config('heritage_shop.max_image_kb', 2048) / 1024, 0) }} MB per image.</p>
                        @error('images')
                            <p class="field-error" role="alert">{{ $message }}</p>
                        @enderror
                        <div id="image-upload-error" class="status-banner error" style="display:none; margin:0; padding:10px 12px;"></div>
                    </div>
                    <div id="upload-preview" style="display:grid; gap:10px; margin-top:14px;"></div>

                    @if ($shop->exists && $shop->images->isNotEmpty())
                        <div style="margin-top:20px;">
                            <p style="font-weight:800; margin-bottom:10px;">Current gallery</p>
                            <div style="display:grid; gap:12px;">
                                @foreach ($shop->images as $image)
                                    <div class="existing-image-card" style="padding:10px; border:1px solid var(--line); border-radius:12px; background:#fff;">
                                        <img class="existing-image-preview" src="{{ $imageService->url($image) }}" alt="Shop gallery image {{ $loop->iteration }}" style="width:100%; height:150px; object-fit:cover; border-radius:8px; margin-bottom:8px;" onerror="this.replaceWith(Object.assign(document.createElement('div'), {textContent:'Image unavailable', className:'muted'}))">

                                        <div class="actions" style="margin-top:8px;">
                                            <label class="button secondary small primary-image-choice" style="cursor:pointer;">
                                                <input type="radio" name="primary_image_id" value="{{ $image->id }}" @checked((int) old('primary_image_id', $shop->images->firstWhere('is_primary', true)?->id ?? $shop->images->first()?->id) === $image->id)> Primary
                                            </label>
                                            <button class="button secondary small image-remove-toggle" type="button">Remove</button>
                                            <input class="image-remove-checkbox" type="checkbox" name="remove_images[]" value="{{ $image->id }}" hidden @checked(in_array($image->id, (array) old('remove_images', [])))>
                                            <label class="button small image-replace-label" style="border:1px solid var(--line); background:#fff; cursor:pointer;">
                                                Replace
                                                <input type="file" name="replace_images[{{ $image->id }}]" accept="image/jpeg,image/png,image/webp" style="display:none;">
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>

                <section class="panel">
                    <h2>Publishing</h2>
                    <div class="field" style="margin-top:16px;">
                        <label for="publish_status">Status</label>
                        <select id="publish_status" name="publish_status" aria-describedby="publish-status-help">
                            @php($selectedStatus = $shopStatus)
                            <option value="draft" @selected($selectedStatus === 'draft')>Draft</option>
                            <option value="published" @selected($selectedStatus === 'published')>Published</option>
                            <option value="archived" @selected($selectedStatus === 'archived')>Archived</option>
                        </select>
                    </div>
                    <p class="help-text field-guidance" id="publish-status-help" style="margin-top:10px;">Only <strong>Published</strong> records appear in public discovery. Draft and Archived records remain admin-only. Published records require a Heritage story, Address, City, and at least one valid gallery image.</p>
                    @error('primary_image_id')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                    <div class="publishing-readiness" data-publishing-readiness aria-live="polite">
                        <strong>Publishing readiness</strong>
                        <span data-ready-field="shop_name">Shop name</span>
                        <span data-ready-field="heritage_story">Heritage story</span>
                        <span data-ready-field="address">Address</span>
                        <span data-ready-field="city">City</span>
                        <span data-ready-field="image">At least one gallery image</span>
                        <span data-ready-field="gallery_limit">Maximum gallery size</span>
                    </div>
                    <div class="actions" style="margin-top:16px;">

                        <button class="button primary" type="submit" data-save-button data-idle-label="{{ $mode === 'create' ? 'Create Shop' : 'Save Changes' }}">{{ $mode === 'create' ? 'Create Shop' : 'Save Changes' }}</button>
                    </div>
                </section>
            </aside>
        </div>
    </form>

    <style>
        .field-guidance { margin: 0; color: var(--muted); font-size: .72rem; line-height: 1.4; }
        .section-guidance { margin: 6px 0 0; color: var(--muted); font-size: .76rem; line-height: 1.45; }
        .filters.four.location-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .filters.four.location-grid .location-address { grid-column: 1 / -1; }
        .required-marker { color: #a33a2d; font-weight: 900; }
        .field-error { margin: 0; color: #a33a2d; font-size: .76rem; font-weight: 800; line-height: 1.4; }
        .field input[aria-invalid="true"], .field textarea[aria-invalid="true"], .field select[aria-invalid="true"] { border-color: rgba(163,54,54,.55); box-shadow: 0 0 0 3px rgba(163,54,54,.1); }
        .publishing-readiness { display:grid; gap:7px; margin-top:14px; padding:12px; border:1px solid var(--line); border-radius:12px; background:#fffaf2; }
        .publishing-readiness span { color:#8b3d31; font-size:.76rem; font-weight:800; }
        .publishing-readiness span::before { content:'Needs: '; }
        .publishing-readiness span.is-ready { color:#286345; }
        .publishing-readiness span.is-ready::before { content:'Ready: '; }
        .existing-image-card.is-marked-for-removal { opacity:.58; background:#f7eeee !important; }
        .existing-image-card.is-marked-for-removal .existing-image-preview { filter:grayscale(1); }
        .gallery-count { display:flex; flex-wrap:wrap; gap:8px 12px; margin:12px 0 0; color:var(--muted); font-size:.78rem; }
        .gallery-count strong { color:var(--accent); }
        .autofill-field {
            border: 1px solid rgba(163, 54, 54, .35) !important;
            box-shadow: 0 0 0 4px rgba(163, 54, 54, .08);
            background: rgba(255, 247, 245, .9);
        }
        .autofill-tag {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(163, 54, 54, .1);
            color: var(--accent);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 6px;
            max-width: 100%;
            overflow-wrap: anywhere;
            white-space: normal;
        }
        .crawl-controls {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: end;
        }
        .crawl-feedback {
            grid-column: 1 / -1;
            min-width: 0;
        }
        #research-sources, #research-sources a {
            overflow-wrap: anywhere;
        }
        .menu-item-row {
            display: grid;
            gap: 12px;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(255,255,255,.72);
        }
        .menu-item-row-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .menu-item-number {
            font-weight: 800;
            color: var(--accent);
        }
        .menu-item-fields {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(120px, .6fr);
            gap: 12px;
        }
        .menu-item-fields .field {
            display: grid;
            gap: 6px;
        }
        .menu-item-fields textarea {
            min-height: 72px;
        }
        .menu-item-actions {
            display: flex;
            justify-content: flex-end;
        }
        @media (max-width: 1100px) {
            .filters.four.location-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .menu-item-fields { grid-template-columns: 1fr; }
            .crawl-controls { grid-template-columns: 1fr; }
            .filters.four.location-grid { grid-template-columns: 1fr; }
        }
    </style>

    <script>
        const heritageShopForm = document.getElementById('heritage-shop-form');
        const crawlButton = document.getElementById('crawl-button');
        const crawlStatus = document.getElementById('crawl-status');
        const crawlUrlInput = document.getElementById('crawl_url');
        const uploadInput = document.getElementById('images');
        const imageUploadError = document.getElementById('image-upload-error');
        const maxImageBytes = Number('{{ config('heritage_shop.max_image_bytes', 2097152) }}');
        const maxImageLabel = '{{ number_format(config('heritage_shop.max_image_kb', 2048) / 1024, 0) }} MB';
        const maxGalleryImages = Number('{{ config('heritage_shop.max_gallery_images', 10) }}');
        const publishStatusInput = document.getElementById('publish_status');
        const publishRequiredFields = ['heritage_story', 'address', 'city'];

        function updatePublishingReadiness() {
            if (!document.querySelector('[data-publishing-readiness]')) return;
            const remainingImages = Array.from(document.querySelectorAll('.image-remove-checkbox')).filter((input) => !input.checked).length;
            const finalImageCount = remainingImages + (uploadInput?.files?.length ?? 0) + document.querySelectorAll('input[name="crawler_images[]"]').length;
            const hasImage = finalImageCount > 0;
            const ready = {
                shop_name: Boolean(document.getElementById('shop_name')?.value.trim()),
                heritage_story: Boolean(document.getElementById('heritage_story')?.value.trim()),
                address: Boolean(document.getElementById('address')?.value.trim()),
                city: Boolean(document.getElementById('city')?.value.trim()),
                image: hasImage,
                gallery_limit: finalImageCount <= maxGalleryImages,
            };
            Object.entries(ready).forEach(([key, value]) => document.querySelector('[data-ready-field="' + key + '"]')?.classList.toggle('is-ready', value));
            const count = document.getElementById('gallery-count');
            const remaining = document.getElementById('gallery-remaining');
            if (count) count.textContent = finalImageCount + ' / ' + maxGalleryImages + ' images';
            if (remaining) remaining.textContent = finalImageCount <= maxGalleryImages
                ? 'You can add up to ' + (maxGalleryImages - finalImageCount) + ' more image' + (maxGalleryImages - finalImageCount === 1 ? '.' : 's.')
                : 'Remove ' + (finalImageCount - maxGalleryImages) + ' image' + (finalImageCount - maxGalleryImages === 1 ? '.' : 's.');
        }

        function syncPublishedRequirements() {
            const isPublished = publishStatusInput?.value === 'published';
            publishRequiredFields.forEach((fieldId) => {
                const field = document.getElementById(fieldId);
                if (field) field.required = isPublished;
                const marker = field?.closest('.field')?.querySelector('[data-required-marker="published"]');
                if (marker) marker.hidden = !isPublished;
            });
        }

        publishStatusInput?.addEventListener('change', syncPublishedRequirements);
        syncPublishedRequirements();
        ['shop_name', 'heritage_story', 'address', 'city'].forEach((id) => document.getElementById(id)?.addEventListener('input', updatePublishingReadiness));

        const menuItemsContainer = document.getElementById('menu-items-container');
        const addMenuItemButton = document.getElementById('add-menu-item');
        const initialMenuItems = @json($shopFoodItems ?? [['name' => '', 'price' => '', 'desc' => '']]);

        function reindexMenuRows() {
            menuItemsContainer.querySelectorAll('.menu-item-row').forEach((row, index) => {
                row.querySelector('.menu-item-number').textContent = 'Food ' + (index + 1);
                row.querySelectorAll('[name^="food_items["]').forEach((field) => {
                    field.name = field.name.replace(/food_items\[\d+]/, 'food_items[' + index + ']');
                });
            });
        }

        function createMenuField(labelText, control) {
            const field = document.createElement('div');
            field.className = 'field';
            const label = document.createElement('label');
            label.textContent = labelText;
            field.appendChild(label);
            field.appendChild(control);
            return field;
        }

        function buildMenuRow(item = { name: '', price: '', desc: '' }, index = 0) {
            const row = document.createElement('div');
            row.className = 'menu-item-row';

            const header = document.createElement('div');
            header.className = 'menu-item-row-head';
            const label = document.createElement('div');
            label.className = 'menu-item-number';
            label.textContent = 'Food ' + (index + 1);
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'button danger small';
            removeButton.textContent = 'Remove';
            removeButton.addEventListener('click', () => {
                const rows = menuItemsContainer.querySelectorAll('.menu-item-row');
                if (rows.length > 1) {
                    row.remove();
                    reindexMenuRows();
                } else {
                    row.querySelectorAll('input:not([type="hidden"]), textarea').forEach((field) => field.value = '');
                }
            });
            header.appendChild(label);
            header.appendChild(removeButton);

            const fields = document.createElement('div');
            fields.className = 'menu-item-fields';

            const nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.name = 'food_items[' + index + '][name]';
            nameInput.value = String(item.name || '');
            nameInput.placeholder = 'Example: Nasi Kandar';
            nameInput.maxLength = 255;
            const nameField = createMenuField('Food name', nameInput);

            const priceInput = document.createElement('input');
            priceInput.type = 'text';
            priceInput.name = 'food_items[' + index + '][price]';
            priceInput.value = String(item.price || '');
            priceInput.placeholder = 'RM 18.00';
            priceInput.maxLength = 80;
            const priceField = createMenuField('Price', priceInput);

            const descriptionInput = document.createElement('textarea');
            descriptionInput.name = 'food_items[' + index + '][desc]';
            descriptionInput.value = String(item.desc || item.description || '');
            descriptionInput.placeholder = 'Short description for this dish';
            descriptionInput.maxLength = 1000;
            const descField = createMenuField('Description', descriptionInput);
            descField.style.gridColumn = '1 / -1';

            if (item.id) {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'food_items[' + index + '][id]';
                idInput.value = String(item.id);
                fields.appendChild(idInput);
            }

            fields.appendChild(nameField);
            fields.appendChild(priceField);
            fields.appendChild(descField);

            const actions = document.createElement('div');
            actions.className = 'menu-item-actions';

            row.appendChild(header);
            row.appendChild(fields);
            row.appendChild(actions);

            return row;
        }

        function renderMenuItems(items) {
            const safeItems = Array.isArray(items) && items.length ? items : [{ name: '', price: '', desc: '' }];
            menuItemsContainer.replaceChildren();
            safeItems.forEach((item, index) => {
                menuItemsContainer.appendChild(buildMenuRow(item, index));
            });
        }

        addMenuItemButton.addEventListener('click', () => {
            const rows = menuItemsContainer.querySelectorAll('.menu-item-row');
            const nextIndex = rows.length;
            menuItemsContainer.appendChild(buildMenuRow({ name: '', price: '', desc: '' }, nextIndex));
        });

        renderMenuItems(initialMenuItems);

        function setCrawlStatus(type, message) {
            crawlStatus.style.display = 'block';
            crawlStatus.className = 'status-banner';
            crawlStatus.classList.add(type === 'error' ? 'error' : 'success');
            crawlStatus.textContent = message;
        }

        function markAutofill(fieldId, source = '') {
            const field = document.getElementById(fieldId);
            if (!field) return;
            field.classList.add('autofill-field');
            const parent = field.closest('.field');
            if (!parent) return;
            if (!parent.querySelector('.autofill-tag')) {
                const tag = document.createElement('span');
                tag.className = 'autofill-tag';
                const sourceLabel = source
                    ? (source.startsWith('http') ? 'Source page' : 'Web research')
                    : '';
                tag.textContent = sourceLabel ? 'Auto-filled • ' + sourceLabel : 'Auto-filled';
                parent.insertBefore(tag, parent.firstChild);
            }
        }

        function fieldIsBlank(field) {
            return !field || !String(field.value || '').trim();
        }

        function renderResearchSources(sources) {
            const container = document.getElementById('research-sources');
            container.replaceChildren();
            if (!Array.isArray(sources) || !sources.length) {
                container.style.display = 'none';
                return;
            }
            const heading = document.createElement('strong');
            heading.textContent = 'Web research sources — review before publishing: ';
            container.appendChild(heading);
            sources.forEach((source, index) => {
                let url;
                try {
                    url = new URL(String(source.url || ''));
                } catch (error) {
                    return;
                }
                if (!['http:', 'https:'].includes(url.protocol)) return;
                const link = document.createElement('a');
                link.href = url.href;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.textContent = source.title || source.url;
                container.appendChild(link);
                if (index < sources.length - 1) container.appendChild(document.createTextNode(' · '));
            });
            container.style.display = 'block';
        }

        function fillFormFromCrawl(payload) {
            const mapping = {
                name: 'shop_name',
                primary_food_category: 'primary_food_category',
                establishment_year: 'establishment_year',
                heritage_story: 'heritage_story',
                description: 'heritage_story',
                contact_number: 'contact_number',
                address: 'address',
                city: 'city',
                state: 'state',
                postal_code: 'postal_code',
                source_url: 'source_url',
                operating_hours: 'operating_hours',
                founder_name: 'founder_name',
                current_owner_name: 'current_owner_name',
                founder_background: 'founder_background',
                current_owner_details: 'current_owner_details',
            };

            Object.entries(mapping).forEach(([sourceKey, fieldId]) => {
                const value = payload[sourceKey] ?? payload[fieldId] ?? '';
                const field = document.getElementById(fieldId);
                // Crawling complements the admin's work; it must not replace a
                // value that was deliberately entered in the form.
                if (field && fieldIsBlank(field) && value !== null && value !== undefined && value !== '') {
                    field.value = value;
                    markAutofill(fieldId, payload.field_sources?.[sourceKey] || payload.field_sources?.[fieldId] || '');
                }
            });

            const menu = Array.isArray(payload.food_items) ? payload.food_items : (Array.isArray(payload.menu) ? payload.menu : []);
            if (menu.length && !menuItemsContainer.querySelector('input[name$="[name]"]').value.trim()) {
                renderMenuItems(menu.map((item) => ({
                    name: item.name || '',
                    price: item.price || '',
                    desc: item.description || item.desc || '',
                })));
            }

            if (payload.images && payload.images.length) {
                const form = document.querySelector('form');
                payload.images.forEach((imagePath) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'crawler_images[]';
                    hidden.value = imagePath;
                    form.appendChild(hidden);
                });
            }

            renderResearchSources(payload.research_sources);
        }

        crawlButton.addEventListener('click', async () => {
            const url = crawlUrlInput.value.trim();
            if (!url) {
                setCrawlStatus('error', 'Please enter a valid URL before fetching data.');
                return;
            }

            crawlButton.disabled = true;
            crawlButton.textContent = 'Fetching...';
            setCrawlStatus('success', 'Fetching shop data...');
            try {
                const response = await fetch('{{ route('admin.heritage-shops.crawl') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ url, heritage_shop_id: '{{ $shop->id ?? '' }}' })
                });

                const responseData = await response.json();
                if (!response.ok) {
                    if (response.status === 409 && responseData.existing_shop_url) {
                        setCrawlStatus('error', responseData.message || 'This source URL is already in the registry.');
                        const existingLink = document.createElement('a');
                        existingLink.href = responseData.existing_shop_url;
                        existingLink.target = '_blank';
                        existingLink.rel = 'noopener noreferrer';
                        existingLink.textContent = 'Open existing shop';
                        existingLink.style.display = 'inline-block';
                        existingLink.style.marginTop = '6px';
                        crawlStatus.appendChild(document.createElement('br'));
                        crawlStatus.appendChild(existingLink);
                        return;
                    }
                    throw new Error(responseData.message || 'The crawler could not fetch that URL.');
                }

                fillFormFromCrawl(responseData);
                const researchNote = responseData.research_status ? ' ' + responseData.research_status : '';
                setCrawlStatus('success', (responseData.research_sources?.length
                    ? 'Source data and grounded web research filled blank fields. Review the linked sources before saving.'
                    : 'Source data filled blank fields. No unsupported values were invented; please review before saving.') + researchNote);
            } catch (error) {
                setCrawlStatus('error', error.message || 'The crawl request failed.');
            } finally {
                crawlButton.disabled = false;
                crawlButton.textContent = 'Fetch / Crawl';
                updatePublishingReadiness();
            }
        });

        // Pasting a source URL is enough for a new, otherwise empty record.
        // Existing records still require the button, preventing surprise edits.
        let autoCrawlUrl = '';
        crawlUrlInput.addEventListener('change', () => {
            const url = crawlUrlInput.value.trim();
            const isNewRecord = {{ $mode === 'create' ? 'true' : 'false' }};
            if (isNewRecord && url && url !== autoCrawlUrl && fieldIsBlank(document.getElementById('shop_name'))) {
                autoCrawlUrl = url;
                crawlButton.click();
            }
        });

        if (uploadInput) {
            uploadInput.addEventListener('change', () => {
                const preview = document.getElementById('upload-preview');
                preview.replaceChildren();
                imageUploadError.style.display = 'none';
                const selectedFiles = Array.from(uploadInput.files);
                const invalidFiles = selectedFiles.filter((file) => file.size > maxImageBytes || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type));

                const retainedImages = Array.from(document.querySelectorAll('.image-remove-checkbox')).filter((input) => !input.checked).length;
                const crawlerImages = document.querySelectorAll('input[name="crawler_images[]"]').length;
                if (invalidFiles.length) {
                    const file = invalidFiles[0];
                    imageUploadError.textContent = file.size > maxImageBytes
                        ? file.name + ' exceeds the maximum Heritage Shop image size of ' + maxImageLabel + '.'
                        : file.name + ' must be a JPG, JPEG, PNG, or WebP image.';
                    imageUploadError.style.display = 'block';
                    uploadInput.value = '';
                    updatePublishingReadiness();
                    return;
                }

                if (retainedImages + crawlerImages + selectedFiles.length > maxGalleryImages) {
                    const available = Math.max(0, maxGalleryImages - retainedImages - crawlerImages);
                    imageUploadError.textContent = 'This shop can contain a maximum of ' + maxGalleryImages + ' gallery images. After your current changes, you can add up to ' + available + ' more.';
                    imageUploadError.style.display = 'block';
                    uploadInput.value = '';
                    updatePublishingReadiness();
                    return;
                }

                selectedFiles.forEach((file) => {
                    const wrapper = document.createElement('div');
                    wrapper.style.border = '1px solid var(--line)';
                    wrapper.style.borderRadius = '12px';
                    wrapper.style.padding = '8px';
                    wrapper.style.background = '#fff';

                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.alt = file.name;
                    img.style.width = '100%';
                    img.style.height = '130px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '8px';
                    wrapper.appendChild(img);
                    preview.appendChild(wrapper);
                });
                updatePublishingReadiness();
            });
        }

        document.querySelectorAll('input[name^="replace_images["]').forEach((input) => {
            input.addEventListener('change', () => {
                const file = input.files?.[0];
                if (file && (file.size > maxImageBytes || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type))) {
                    imageUploadError.textContent = 'Use a JPG, PNG, or WebP image no larger than ' + maxImageLabel + '.';
                    imageUploadError.style.display = 'block';
                    input.value = '';
                } else {
                    imageUploadError.style.display = 'none';
                    const preview = input.closest('.existing-image-card')?.querySelector('.existing-image-preview');
                    if (file && preview) preview.src = URL.createObjectURL(file);
                }
            });
        });

        document.querySelectorAll('.existing-image-card').forEach((card) => {
            const checkbox = card.querySelector('.image-remove-checkbox');
            const toggle = card.querySelector('.image-remove-toggle');
            const sync = () => {
                card.classList.toggle('is-marked-for-removal', checkbox.checked);
                toggle.textContent = checkbox.checked ? 'Undo removal' : 'Remove';
                card.querySelector('input[name="primary_image_id"]')?.toggleAttribute('disabled', checkbox.checked);
                updatePublishingReadiness();
            };
            toggle?.addEventListener('click', () => { checkbox.checked = !checkbox.checked; sync(); });
            sync();
        });

        let formIsDirty = false;
        heritageShopForm?.addEventListener('input', () => { formIsDirty = true; });
        heritageShopForm?.addEventListener('change', () => { formIsDirty = true; updatePublishingReadiness(); });
        heritageShopForm?.addEventListener('submit', () => {
            formIsDirty = false;
            const saveButton = heritageShopForm.querySelector('[data-save-button]');
            if (saveButton) { saveButton.disabled = true; saveButton.textContent = 'Saving...'; }
        });
        window.addEventListener('beforeunload', (event) => {
            if (!formIsDirty) return;
            event.preventDefault();
            event.returnValue = '';
        });
        document.querySelector('[aria-invalid="true"]')?.focus();
        updatePublishingReadiness();

    </script>
@endsection
