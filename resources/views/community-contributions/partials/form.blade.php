@php
    $isEdit = filled($contribution);
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $savedHours = collect($contribution?->operating_hours ?? [])->keyBy('day');
    $hours = old('operating_hours', collect($days)->map(fn ($day) => $savedHours->get($day, [
        'day' => $day,
        'open' => '08:00',
        'close' => '17:00',
        'closed' => false,
    ]))->all());
    $foodItems = old('food_items', $contribution?->food_items ?: [['name' => '', 'desc' => '']]);
    $existingMedia = $contribution?->media ?? collect();
    $fieldValue = fn (string $name, mixed $fallback = null) => old($name, $contribution?->{$name} ?? $fallback);
    $submissionToken = old('submission_token', $contribution?->submission_token ?? $formToken ?? (string) \Illuminate\Support\Str::uuid());
    $isClosed = function (array $schedule): bool {
        $closed = filter_var($schedule['closed'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;

        return $closed && blank($schedule['open'] ?? null) && blank($schedule['close'] ?? null);
    };
@endphp

<form class="form-grid" method="POST"
      action="{{ $isEdit ? route('community-contribution.update', $contribution) : route('community-contribution.store') }}"
      enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
    @if ($isEdit)
        @method('PUT')
    @endif

    <section class="form-section">
        <h2 class="section-title">{{ __('Shop details') }}</h2>
        <div class="field-grid">
            <div class="field full">
                <label class="required" for="contribution_title">{{ __('Contribution title') }}</label>
                <input id="contribution_title" name="contribution_title" value="{{ $fieldValue('contribution_title') }}" placeholder="{{ __('Example: A 70-year-old family nasi kandar shop') }}">
                @error('contribution_title') <p class="field-error">{{ $message }}</p> @enderror
                @error('draft') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="required" for="shop_name">{{ __('Shop name') }}</label>
                <input id="shop_name" name="shop_name" value="{{ $fieldValue('shop_name') }}" placeholder="{{ __('Example: Capital Café') }}">
                @error('shop_name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="required" for="primary_food_category">{{ __('Primary food category') }}</label>
                <input id="primary_food_category" name="primary_food_category" value="{{ $fieldValue('primary_food_category') }}"placeholder="{{ __('Example: Hainanese cuisine') }}">
                @error('primary_food_category') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="required" for="establishment_year">{{ __('Establishment year') }}</label>
                <input id="establishment_year" name="establishment_year" type="number" min="1000" max="{{ now()->year }}" value="{{ $fieldValue('establishment_year') }}" placeholder="{{ __('Example: 1956') }}">
                @error('establishment_year') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="contact_number">{{ __('Contact number') }}</label>
                <input id="contact_number" name="contact_number" value="{{ $fieldValue('contact_number') }}" placeholder="{{ __('Example: +60 3-1234 5678') }}">
                @error('contact_number') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="form-section">
        <h2 class="section-title">{{ __('Founder and owner information') }}</h2>
        <div class="field-grid">
            <div class="field">
                <label class="required" for="founder_name">{{ __('Founder name') }}</label>
                <input
                    id="founder_name"
                    name="founder_name"
                    value="{{ $fieldValue('founder_name') }}"
                    placeholder="{{ __('Founder’s full name') }}"
                >
                @error('founder_name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="required" for="current_owner_name">{{ __('Current owner name') }}</label>
                <input
                    id="current_owner_name"
                    name="current_owner_name"
                    value="{{ $fieldValue('current_owner_name') }}"
                    placeholder="{{ __('Current generation owner') }}"
                >
                @error('current_owner_name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field full">
                <label class="required" for="founder_background">{{ __('Founder background') }}</label>
                <textarea
                    id="founder_background"
                    name="founder_background"
                    placeholder="{{ __('Explain how the founder began the business') }}"
                    >{{ $fieldValue('founder_background') }}</textarea>
                @error('founder_background') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field full">
                <label for="current_owner_details">{{ __('Current owner details') }}</label>
                <textarea
                    id="current_owner_details"
                    name="current_owner_details"
                    placeholder="{{ __('Explain how the present owner continues the tradition') }}"
                    >{{ $fieldValue('current_owner_details') }}</textarea>
                @error('current_owner_details') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="form-section">
        <h2 class="section-title">{{ __('Heritage story') }}</h2>
        <div class="field full">
            <label class="required" for="heritage_story">{{ __('Heritage or family story') }}</label>
            <textarea
                    id="heritage_story"
                    name="heritage_story"
                    placeholder="{{ __('Describe the history, traditions, and cultural significance of this shop') }}"
            >{{ $fieldValue('heritage_story') }}</textarea>
            @error('heritage_story') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </section>

    <section class="form-section">
        <h2 class="section-title">{{ __('Location') }}</h2>
        <div class="field-grid three">
            <div class="field full">
                <label class="required" for="address">{{ __('Address') }}</label>
                <input
                    id="address"
                    name="address"
                    value="{{ $fieldValue('address') }}"
                    placeholder="{{ __('Street and building address') }}"
>
                @error('address') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="city">{{ __('City') }}</label>
                <input
                    id="city"
                    name="city"
                    value="{{ $fieldValue('city') }}"
                    placeholder="{{ __('Kuala Lumpur') }}"
                >
            </div>
            <div class="field">
                <label for="state">{{ __('State') }}</label>
                <input
                    id="state"
                    name="state"
                    value="{{ $fieldValue('state') }}"
                    placeholder="{{ __('Wilayah Persekutuan') }}"
                >
            </div>
            <div class="field">
                <label for="postal_code">{{ __('Postal code') }}</label>
                <input id="postal_code" name="postal_code" value="{{ $fieldValue('postal_code') }}" placeholder="50000">
            </div>
            <div class="field">
                <label for="latitude">{{ __('Latitude (optional)') }}</label>
                <input id="latitude" name="latitude" type="number" step="any" value="{{ $fieldValue('latitude') }}" placeholder="3.1390">
                @error('latitude') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="longitude">{{ __('Longitude (optional)') }}</label>
                <input id="longitude" name="longitude" type="number" step="any" value="{{ $fieldValue('longitude') }}" placeholder="101.6869">
                @error('longitude') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="form-section">
        <h2 class="section-title">{{ __('Operating hours') }}</h2>
        <div class="soft-card">
            <div class="soft-card-head">
                <div>{{ __('Day') }}</div>
                <div>{{ __('Open') }}</div>
                <div>{{ __('Close / closed') }}</div>
            </div>

            @foreach ($days as $index => $day)
                @php
                    $schedule = $hours[$index] ?? ['day' => $day, 'open' => '08:00', 'close' => '17:00', 'closed' => false];
                    $closed = $isClosed($schedule);
                @endphp
                <div class="soft-card-row">
                    <div class="day-label">{{ __($day) }}</div>
                    <div>
                        <input name="operating_hours[{{ $index }}][open]" type="time" value="{{ $schedule['open'] ?? '' }}">
                        <input type="hidden" name="operating_hours[{{ $index }}][day]" value="{{ $day }}">
                    </div>
                    <div class="close-cell">
                        <input name="operating_hours[{{ $index }}][close]" type="time" value="{{ $schedule['close'] ?? '' }}">
                        <input type="hidden" name="operating_hours[{{ $index }}][closed]" value="0">
                        <label title="{{ __('Closed') }}"><input class="checkbox-input operating-hours-closed" name="operating_hours[{{ $index }}][closed]" value="1" type="checkbox" @checked($closed)>{{ __('Closed') }}</label>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="form-section">
        <h2 class="section-title">{{ __('Heritage food items') }}</h2>
        <div id="food-items-shell" class="repeat-shell">
            @foreach ($foodItems as $index => $item)
                <div class="repeat-row food-item-row">
                    <div>
                        @if ($index === 0) <label>{{ __('Item name') }}</label> @endif
                        <input
                            name="food_items[{{ $index }}][name]"
                            value="{{ $item['name'] ?? '' }}"
                            placeholder="{{ __('Example: Hainanese chicken chop') }}"
                        >
                    </div>
                    <div>
                        @if ($index === 0) <label>{{ __('Description') }}</label> @endif
                        <input
                            name="food_items[{{ $index }}][desc]"
                            value="{{ $item['desc'] ?? '' }}"
                            placeholder="{{ __('Briefly describe the traditional dish') }}"
                        >
                    </div>
                    <div class="repeat-actions">
                        <button type="button" class="mini-button remove-food-item" aria-label="{{ __('Remove food item') }}">{{ __('Remove') }}</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" id="add-food-item" class="link-button">+ {{ __('Add food item') }}</button>
    </section>

    <section class="form-section">
        <h2 class="section-title">{{ __('Supporting media') }}</h2>

        @if ($existingMedia->isNotEmpty())
            <div class="media-grid" aria-label="{{ __('Existing media') }}">
                @foreach ($existingMedia as $media)
                    <div class="media-card">
                        @if ($media->media_type === 'video')
                            <video controls preload="metadata"><source src="{{ $media->url }}"></video>
                        @else
                            <img src="{{ $media->url }}" alt="{{ __('Previously uploaded supporting media') }}">
                        @endif
                        <label class="remove-media">
                            <input class="checkbox-input" type="checkbox" name="remove_media[]" value="{{ $media->id }}">
                            {{ __('Remove this file') }}
                        </label>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="field full">
            <label for="supporting_media">{{ __('Add images or videos') }}</label>
            <input id="supporting_media" name="supporting_media[]" type="file" accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.avi" multiple>
            <p class="help-text">{{ __('Up to 6 files in total. JPG, JPEG, PNG, WEBP, MP4, MOV, or AVI; maximum 20 MB per file.') }}</p>
            @error('supporting_media') <p class="field-error">{{ $message }}</p> @enderror
            @error('supporting_media.*') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div id="media-preview" class="media-grid" aria-live="polite"></div>
    </section>

    <div class="actions">
        <button class="button secondary" type="submit" name="submission_action" value="draft">
            {{ $isEdit ? __('Save changes') : __('Save as draft') }}
        </button>
        <button class="button primary" type="submit" name="submission_action" value="submit">
            {{ $contribution?->status === \App\Models\HeritageShopContribution::STATUS_REVISION_REQUIRED? __('Resubmit revision'): __('Submit for review') }}{{ __('Cancel') }}
        </button>
        @if ($isEdit)
            <a class="button secondary" href="{{ $contribution->status === \App\Models\HeritageShopContribution::STATUS_DRAFT ? route('community-contribution.drafts') : route('community-contribution.contributions.show', $contribution) }}">Cancel</a>
        @endif
    </div>

    <p class="help-text">{{ __('Required fields are checked when you submit for review. A draft may be incomplete.') }}</p>
    <p id="draft-client-error" class="field-error" hidden>
    {{ __('Please enter at least one piece of information before saving this draft.') }}</p>
</form>

<template id="food-item-template">
    <div class="repeat-row food-item-row">
        <div><input name="food_items[__INDEX__][name]" placeholder="{{ __('Example: Hainanese chicken chop') }}"></div>
        <div><input name="food_items[__INDEX__][desc]"placeholder="{{ __('Briefly describe the traditional dish') }}"></div>
        <div class="repeat-actions"><button type="button" class="mini-button remove-food-item">{{ __('Remove') }}</button></div>
    </div>
</template>

@push('scripts')
<script>
    const newFilesSelectedMessage = @json(
        __(':count new file(s) selected. Files are uploaded only when you save or submit the form.')
    );

    (() => {
        const shell = document.getElementById('food-items-shell');
        const addButton = document.getElementById('add-food-item');
        const template = document.getElementById('food-item-template');
        const reindex = () => shell.querySelectorAll('.food-item-row').forEach((row, index) => {
            row.querySelectorAll('input').forEach((input) => {
                input.name = input.name.replace(/food_items\[\d+]/, `food_items[${index}]`);
            });
        });
        const bindRemove = (row) => row.querySelector('.remove-food-item')?.addEventListener('click', () => {
            if (shell.querySelectorAll('.food-item-row').length === 1) {
                row.querySelectorAll('input').forEach((input) => input.value = '');
                return;
            }
            row.remove();
            reindex();
        });

        shell?.querySelectorAll('.food-item-row').forEach(bindRemove);
        addButton?.addEventListener('click', () => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(shell.querySelectorAll('.food-item-row').length)).trim();
            const row = wrapper.firstElementChild;
            shell.appendChild(row);
            bindRemove(row);
        });

        const input = document.getElementById('supporting_media');
        const preview = document.getElementById('media-preview');
        const form = document.querySelector('form.form-grid');
        const hourRows = document.querySelectorAll('.soft-card-row');

        hourRows.forEach((row) => {
            const timeInputs = row.querySelectorAll('input[type="time"]');
            const closedInput = row.querySelector('.operating-hours-closed');

            timeInputs.forEach((timeInput) => {
                timeInput.addEventListener('input', () => {
                    if (timeInput.value && closedInput) {
                        closedInput.checked = false;
                    }
                });
            });

            closedInput?.addEventListener('change', () => {
                if (! closedInput.checked) {
                    return;
                }

                timeInputs.forEach((timeInput) => {
                    timeInput.value = '';
                });
            });
        });

        form?.addEventListener('submit', (event) => {
            if (form.dataset.submitted === 'true') {
                event.preventDefault();
                return;
            }

            const action = event.submitter?.value;
            const title = document.getElementById('contribution_title')?.value.trim();
            const shopName = document.getElementById('shop_name')?.value.trim();
            const draftError = document.getElementById('draft-client-error');

            if (action === 'draft' && ! title && ! shopName) {
                event.preventDefault();
                if (draftError) {
                    draftError.hidden = false;
                    draftError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            if (draftError) {
                draftError.hidden = true;
            }

            form.dataset.submitted = 'true';
            setTimeout(() => {
                form.querySelectorAll('button[type="submit"]').forEach((button) => {
                    button.disabled = true;
                    button.setAttribute('aria-disabled', 'true');
                });
            }, 0);
        });

        input?.addEventListener('change', () => {
            preview.replaceChildren();
            [...input.files].forEach((file) => {
                const card = document.createElement('div');
                card.className = 'media-card';
                const url = URL.createObjectURL(file);
                if (file.type.startsWith('image/')) {
                    const image = document.createElement('img');
                    image.src = url;
                    image.alt = file.name;
                    image.onload = () => URL.revokeObjectURL(url);
                    card.appendChild(image);
                } else {
                    const video = document.createElement('video');
                    video.src = url;
                    video.controls = true;
                    video.onloadedmetadata = () => URL.revokeObjectURL(url);
                    card.appendChild(video);
                }
                preview.appendChild(card);
            });
            if (input.files.length) {
                const note = document.createElement('p');
                note.className = 'preview-note';
                note.textContent = newFilesSelectedMessage.replace(
                    ':count',
                    input.files.length
                );
                preview.appendChild(note);
            }
        });
    })();
</script>
@endpush
