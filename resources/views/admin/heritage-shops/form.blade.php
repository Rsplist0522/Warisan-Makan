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
        $shopHours = old('operating_hours', $shop->operating_hours ?? '');
        $shopSourceUrl = old('source_url', $shop->source_url ?? '');
        $shopFoodItems = old('food_items', $shop->food_items ?? [['name' => '', 'price' => '', 'desc' => '']]);
    @endphp

    <header class="page-header">
        <div>
            <p class="eyebrow">Heritage registry</p>
            <h1>{{ $mode === 'create' ? 'Add a heritage shop' : 'Edit heritage shop' }}</h1>
            <p>{{ $mode === 'create' ? 'Create a verified public record and review any crawler suggestions before publishing.' : 'Update the public listing and rewrite any imported data as needed.' }}</p>
        </div>
        <div class="actions">
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

    <form method="POST" action="{{ $mode === 'create' ? route('admin.heritage-shops.store') : route('admin.heritage-shops.update', $shop) }}" enctype="multipart/form-data">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <section class="panel" style="margin-bottom:18px;">
            <div class="filters six" style="margin-bottom:0;">
                <div class="field">
                    <label for="crawl_url">Crawl source URL</label>
                    <input id="crawl_url" name="crawl_url" type="url" placeholder="https://example.com/heritage-restaurant" value="{{ old('crawl_url', $shopSourceUrl) }}">
                </div>
                <div class="field" style="align-self:end;">
                    <button class="button secondary small" id="crawl-button" type="button">Fetch / Crawl</button>
                </div>
                <div class="field" style="grid-column: 3 / -1;">
                    <div id="crawl-status" class="status-banner" style="display:none; margin:0; padding:10px 12px;"></div>
                </div>
            </div>
        </section>

        <div class="detail-grid">
            <div class="detail-stack">
                <section class="panel">
                    <h2>Shop profile</h2>
                    <div class="filters four" style="margin-top:16px;">
                        <div class="field">
                            <label for="shop_name">Shop name</label>
                            <input id="shop_name" name="shop_name" type="text" value="{{ $shopName }}">
                        </div>
                        <div class="field">
                            <label for="primary_food_category">Primary category</label>
                            <input id="primary_food_category" name="primary_food_category" type="text" value="{{ $shopCategory }}">
                        </div>
                        <div class="field">
                            <label for="establishment_year">Established year</label>
                            <input id="establishment_year" name="establishment_year" type="number" min="1000" max="{{ now()->year }}" value="{{ old('establishment_year', $shop->establishment_year ?? '') }}">
                        </div>
                        <div class="field">
                            <label for="contact_number">Contact number</label>
                            <input id="contact_number" name="contact_number" type="text" value="{{ $shopContact }}">
                        </div>
                        <div class="field" style="grid-column:1 / -1;">
                            <label for="source_url">Source URL</label>
                            <input id="source_url" name="source_url" type="url" value="{{ $shopSourceUrl }}">
                        </div>
                        <div class="field" style="grid-column:1 / -1;">
                            <label for="heritage_story">Heritage story</label>
                            <textarea id="heritage_story" name="heritage_story">{{ $shopDescription }}</textarea>
                        </div>
                        <div class="field" style="grid-column:1 / -1;">
                            <label for="operating_hours">Operating hours</label>
                            <textarea id="operating_hours" name="operating_hours">{{ is_array($shopHours) ? implode("\n", $shopHours) : $shopHours }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <h2>Location and ownership</h2>
                    <div class="filters four" style="margin-top:16px;">
                        <div class="field" style="grid-column:1 / -1;">
                            <label for="address">Address</label>
                            <input id="address" name="address" type="text" value="{{ $shopAddress }}">
                        </div>
                        <div class="field">
                            <label for="city">City</label>
                            <input id="city" name="city" type="text" value="{{ $shopCity }}">
                        </div>
                        <div class="field">
                            <label for="state">State</label>
                            <input id="state" name="state" type="text" value="{{ $shopState }}">
                        </div>
                        <div class="field">
                            <label for="postal_code">Postal code</label>
                            <input id="postal_code" name="postal_code" type="text" value="{{ $shopPostal }}">
                        </div>
                        <div class="field">
                            <label for="founder_name">Founder</label>
                            <input id="founder_name" name="founder_name" type="text" value="{{ old('founder_name', $shop->founder_name ?? '') }}">
                        </div>
                        <div class="field">
                            <label for="current_owner_name">Current owner</label>
                            <input id="current_owner_name" name="current_owner_name" type="text" value="{{ old('current_owner_name', $shop->current_owner_name ?? '') }}">
                        </div>
                        <div class="field" style="grid-column:1 / -1;">
                            <label for="founder_background">Founder background</label>
                            <textarea id="founder_background" name="founder_background">{{ old('founder_background', $shop->founder_background ?? '') }}</textarea>
                        </div>
                        <div class="field" style="grid-column:1 / -1;">
                            <label for="current_owner_details">Current owner details</label>
                            <textarea id="current_owner_details" name="current_owner_details">{{ old('current_owner_details', $shop->current_owner_details ?? '') }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:14px;">
                        <h2 style="margin:0;">Menu items</h2>
                        <button type="button" class="button secondary small" id="add-menu-item">Add menu item</button>
                    </div>
                    <div id="menu-items-container" style="display:grid; gap:12px;"></div>
                </section>
            </div>

            <aside class="detail-stack">
                <section class="panel">
                    <h2>Images</h2>
                    <div class="field" style="margin-top:16px;">
                        <label for="images">Upload gallery images</label>
                        <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                    </div>
                    <div id="upload-preview" style="display:grid; gap:10px; margin-top:14px;"></div>

                    @if ($shop->exists && $shop->images->isNotEmpty())
                        <div style="margin-top:20px;">
                            <p style="font-weight:800; margin-bottom:10px;">Current gallery</p>
                            <div style="display:grid; gap:12px;">
                                @foreach ($shop->images as $image)
                                    <div style="padding:10px; border:1px solid var(--line); border-radius:12px; background:#fff;">
                                        <img src="{{ Storage::disk(config('filesystems.media_disk', 'public'))->url($image->path) }}" alt="Shop gallery image" style="width:100%; height:150px; object-fit:cover; border-radius:8px; margin-bottom:8px;">
                                        <div class="actions" style="margin-top:8px;">
                                            <label class="button secondary small" style="cursor:pointer;">
                                                <input type="checkbox" name="remove_images[]" value="{{ $image->id }}" style="margin-right:6px;"> Remove
                                            </label>
                                            <label class="button small" style="border:1px solid var(--line); background:#fff; cursor:pointer;">
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
                        <select id="publish_status" name="publish_status">
                            <option value="draft" {{ old('publish_status', $shop->publish_status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="approved" {{ old('publish_status', $shop->publish_status ?? 'draft') === 'approved' ? 'selected' : '' }}>Approved</option>
                        </select>
                    </div>
                    <div class="actions" style="margin-top:16px;">
                        <button class="button primary" type="submit">{{ $mode === 'create' ? 'Save shop' : 'Update shop' }}</button>
                    </div>
                </section>
            </aside>
        </div>
    </form>

    <style>
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
        @media (max-width: 640px) {
            .menu-item-fields { grid-template-columns: 1fr; }
        }
    </style>

    <script>
        const crawlButton = document.getElementById('crawl-button');
        const crawlStatus = document.getElementById('crawl-status');
        const crawlUrlInput = document.getElementById('crawl_url');
        const uploadInput = document.getElementById('images');
        const menuItemsContainer = document.getElementById('menu-items-container');
        const addMenuItemButton = document.getElementById('add-menu-item');
        const initialMenuItems = @json($shopFoodItems ?? [['name' => '', 'price' => '', 'desc' => '']]);

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
                }
            });
            header.appendChild(label);
            header.appendChild(removeButton);

            const fields = document.createElement('div');
            fields.className = 'menu-item-fields';

            const nameField = document.createElement('div');
            nameField.className = 'field';
            nameField.innerHTML = '<label>Food name</label><input type="text" name="food_items[' + index + '][name]" value="' + (item.name || '').replace(/"/g, '&quot;') + '" placeholder="Example: Nasi Kandar">';

            const priceField = document.createElement('div');
            priceField.className = 'field';
            priceField.innerHTML = '<label>Price</label><input type="text" name="food_items[' + index + '][price]" value="' + (item.price || '').replace(/"/g, '&quot;') + '" placeholder="RM 18.00">';

            const descField = document.createElement('div');
            descField.className = 'field';
            descField.style.gridColumn = '1 / -1';
            descField.innerHTML = '<label>Description</label><textarea name="food_items[' + index + '][desc]" placeholder="Short description for this dish">' + (item.desc || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</textarea>';

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
            menuItemsContainer.innerHTML = '';
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

        function markAutofill(fieldId) {
            const field = document.getElementById(fieldId);
            if (!field) return;
            field.classList.add('autofill-field');
            const parent = field.closest('.field');
            if (!parent) return;
            if (!parent.querySelector('.autofill-tag')) {
                const tag = document.createElement('span');
                tag.className = 'autofill-tag';
                tag.textContent = 'Auto-filled';
                parent.insertBefore(tag, parent.firstChild);
            }
        }

        function fieldIsBlank(field) {
            return !field || !String(field.value || '').trim();
        }

        function fillFormFromCrawl(payload) {
            const mapping = {
                shop_name: 'shop_name',
                primary_food_category: 'primary_food_category',
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
                    markAutofill(fieldId);
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
        }

        crawlButton.addEventListener('click', async () => {
            const url = crawlUrlInput.value.trim();
            if (!url) {
                setCrawlStatus('error', 'Please enter a valid URL before fetching data.');
                return;
            }

            setCrawlStatus('success', 'Fetching shop data...');
            try {
                const response = await fetch('{{ route('admin.heritage-shops.crawl') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ url })
                });

                const responseData = await response.json();
                if (!response.ok) {
                    throw new Error(responseData.message || 'The crawler could not fetch that URL.');
                }

                fillFormFromCrawl(responseData);
                setCrawlStatus('success', 'Shop data was discovered and blank fields were filled. Please review before saving.');
            } catch (error) {
                setCrawlStatus('error', error.message || 'The crawl request failed.');
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
                preview.innerHTML = '';
                Array.from(uploadInput.files).forEach((file) => {
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
            });
        }
    </script>
@endsection
