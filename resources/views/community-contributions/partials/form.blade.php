@php
    $isEdit = filled($contribution);
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $savedHours = collect($contribution?->operating_hours ?? [])
        ->groupBy(fn (array $row): string => (string) ($row['day'] ?? ''))
        ->map(function ($rows, string $day): array {
            $periods = $rows
                ->filter(fn (array $row): bool => ! filter_var($row['closed'] ?? false, FILTER_VALIDATE_BOOLEAN))
                ->map(fn (array $row): array => [
                    'open' => trim((string) ($row['open'] ?? '')),
                    'close' => trim((string) ($row['close'] ?? '')),
                ])
                ->filter(fn (array $row): bool => filled($row['open']) || filled($row['close']))
                ->values()
                ->all();

            return [
                'closed' => $rows->contains(fn (array $row): bool => filter_var($row['closed'] ?? false, FILTER_VALIDATE_BOOLEAN)) && $periods === [],
                'periods' => $periods === [] ? [['open' => '08:00', 'close' => '17:00']] : $periods,
            ];
        });
    $hours = old('operating_hours', collect($days)->mapWithKeys(fn (string $day): array => [$day => $savedHours->get($day, [
        'closed' => false,
        'periods' => [['open' => '08:00', 'close' => '17:00']],
    ])])->all());
    $foodItems = old('food_items', $contribution?->food_items ?: [['name' => '', 'desc' => '', 'price' => '']]);
    $existingMedia = $contribution?->media ?? collect();
    $fieldValue = fn (string $name, mixed $fallback = null) => old($name, $contribution?->{$name} ?? $fallback);
    $submissionToken = old('submission_token', $contribution?->submission_token ?? $formToken ?? (string) \Illuminate\Support\Str::uuid());
    $isWithdrawnResubmission = $contribution?->status === \App\Models\HeritageShopContribution::STATUS_DRAFT
        && $contribution?->withdrawn_at !== null;
    $maxSupportingMedia = $maxSupportingMedia ?? 6;
    $existingMediaCount = $existingMedia->count();
    $isClosed = function (array $schedule): bool {
        $closed = filter_var($schedule['closed'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;

        return $closed;
    };
@endphp

<style>
    .food-item-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 14px;
        min-width: 0;
        padding: 16px;
        border: 1px solid var(--wm-border);
        border-radius: 12px;
        background: rgba(255, 255, 255, .58);
        align-items: stretch;
    }

    .food-item-row:first-child {
        padding-top: 16px;
        border-top: 1px solid var(--wm-border);
    }

    .food-item-fields {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr) minmax(0, .7fr);
        gap: 12px;
        align-items: start;
        min-width: 0;
    }

    .food-item-field {
        display: grid;
        gap: 7px;
        min-width: 0;
    }

    .food-item-price-control {
        display: flex;
        align-items: center;
        min-height: 42px;
        border: 1px solid var(--wm-border);
        border-radius: 10px;
        background: #fffaf3;
        overflow: hidden;
    }

    .food-item-price-control span {
        align-self: stretch;
        display: inline-flex;
        align-items: center;
        padding: 0 11px;
        border-right: 1px solid var(--wm-border);
        color: var(--wm-muted);
        background: #f1e5d7;
        font-size: .82rem;
        font-weight: 850;
    }

    .food-item-price-control input {
        min-width: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
    }

    .food-item-media {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 10px;
        min-width: 0;
    }

    .food-item-image-field {
        display: grid;
        gap: 7px;
        min-width: 0;
    }

    .food-item-upload-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .food-item-file-input {
        display: none;
    }

    .food-item-upload-button {
        flex: 0 0 auto;
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px dashed var(--wm-accent);
        border-radius: 10px;
        background: rgba(255, 255, 255, .72);
        color: var(--wm-accent);
        padding: 0 13px;
        font-weight: 800;
        cursor: pointer;
    }

    .food-item-upload-button:hover {
        background: rgba(200, 148, 50, .1);
    }

    .remove-food-item-image {
        flex: 0 0 auto;
        min-height: 38px;
        border: 1px solid rgba(180, 35, 24, .18);
        border-radius: 10px;
        background: rgba(180, 35, 24, .06);
        color: var(--wm-danger);
        padding: 0 12px;
        font-weight: 800;
        cursor: pointer;
    }

    .food-item-preview-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .food-item-row .repeat-actions {
        padding-top: 0;
        justify-self: end;
    }

    .food-item-image-note {
        margin: 0;
        color: var(--wm-muted);
        font-size: .78rem;
        overflow-wrap: anywhere;
    }

    .food-item-image-preview {
        display: block;
        width: 58px;
        height: 58px;
        margin: 0;
        border: 1px solid var(--wm-border);
        border-radius: 10px;
        background: #f1e5d7;
        object-fit: cover;
    }

    .food-item-image-preview[hidden],
    .remove-food-item-image[hidden] {
        display: none;
    }

    .supporting-media-shell {
        display: grid;
        gap: 14px;
    }

    .media-count-panel {
        display: grid;
        gap: 5px;
        padding: 12px 14px;
        border: 1px solid var(--wm-border);
        border-radius: 12px;
        background: rgba(255, 255, 255, .58);
    }

    .media-count-panel strong {
        color: var(--wm-accent);
    }

    .media-count-panel p {
        margin: 0;
        color: var(--wm-muted);
        font-size: .86rem;
    }

    .media-subheading {
        margin: 0;
        color: var(--wm-text);
        font-size: .95rem;
    }

    .media-card.marked-for-removal {
        border-color: rgba(180, 35, 24, .32);
        background: rgba(180, 35, 24, .06);
        opacity: .72;
    }

    .media-removal-status {
        display: none;
        margin: 0;
        padding: 0 9px 9px;
        color: var(--wm-danger);
        font-size: .78rem;
        font-weight: 800;
    }

    .media-card.marked-for-removal .media-removal-status {
        display: block;
    }

    .new-media-card {
        display: grid;
        align-content: start;
    }

    .new-media-remove {
        min-height: 40px;
        border: 0;
        border-top: 1px solid var(--wm-border);
        background: rgba(180, 35, 24, .06);
        color: var(--wm-danger);
        font-weight: 800;
        cursor: pointer;
    }

    .media-limit-error[hidden],
    .new-media-title[hidden] {
        display: none;
    }

    @media (max-width: 760px) {
        .food-item-fields {
            grid-template-columns: 1fr;
        }

        .food-item-row .repeat-actions {
            justify-self: start;
        }
    }
</style>

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
                <label class="required" for="primary_food_category">{{ __('Primary Food Category') }}</label>
                <input
                    id="primary_food_category"
                    name="primary_food_category"
                    type="text"
                    maxlength="255"
                    value="{{ $fieldValue('primary_food_category') }}"
                    placeholder="{{ __('Example: Hakka Cuisine') }}"
                >
                @error('primary_food_category') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="required" for="establishment_year">{{ __('Establishment year') }}</label>
                <input id="establishment_year" name="establishment_year" type="number" min="1000" max="{{ now()->year }}" value="{{ $fieldValue('establishment_year') }}" placeholder="{{ __('Example: 1956') }}">
                @error('establishment_year') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="contact_number">{{ __('Contact number') }}</label>
                <input
                    id="contact_number"
                    name="contact_number"
                    type="tel"
                    inputmode="tel"
                    maxlength="30"
                    pattern="(?:\+60|0)[0-9\s().-]{8,14}"
                    value="{{ $fieldValue('contact_number') }}"
                    placeholder="{{ __('Example: +60 3-1234 5678') }}"
                    title="{{ __('Use a Malaysian number starting with +60 or 0.') }}"
                >
                @error('contact_number') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="form-section">
        <h2 class="section-title">{{ __('Founder and owner information') }}</h2>
        <p class="help-text">{{ __('Optional — provide this information if known.') }}</p>
        <div class="field-grid">
            <div class="field">
                <label for="founder_name">{{ __('Founder name') }}</label>
                <input
                    id="founder_name"
                    name="founder_name"
                    value="{{ $fieldValue('founder_name') }}"
                    placeholder="{{ __('Founder’s full name') }}"
                >
                @error('founder_name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="current_owner_name">{{ __('Current owner name') }}</label>
                <input
                    id="current_owner_name"
                    name="current_owner_name"
                    value="{{ $fieldValue('current_owner_name') }}"
                    placeholder="{{ __('Current generation owner') }}"
                >
                @error('current_owner_name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field full">
                <label for="founder_background">{{ __('Founder background') }}</label>
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
                <div>{{ __('Opening hours') }}</div>
                <div>{{ __('Closed') }}</div>
            </div>

            @foreach ($days as $day)
                @php
                    $schedule = $hours[$day] ?? ['closed' => false, 'periods' => [['open' => '08:00', 'close' => '17:00']]];
                    $closed = filter_var($schedule['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $periods = collect($schedule['periods'] ?? [])->filter(fn ($period) => is_array($period))->values()->all();
                    if ($periods === []) {
                        $periods = [['open' => '', 'close' => '']];
                    }
                @endphp
                <div class="soft-card-row hours-day-row" data-hours-day="{{ $day }}" @if ($closed) data-closed="1" @endif>
                    <div class="day-label">{{ __($day) }}</div>
                    <div class="hours-periods" data-hours-periods>
                        @foreach ($periods as $index => $period)
                            <div class="hours-period-row" data-hours-period>
                                <label>
                                    <span>{{ __('Open') }}</span>
                                    <input name="operating_hours[{{ $day }}][periods][{{ $index }}][open]" type="time" value="{{ $period['open'] ?? '' }}" @if ($closed) disabled @endif>
                                </label>
                                <label>
                                    <span>{{ __('Close') }}</span>
                                    <input name="operating_hours[{{ $day }}][periods][{{ $index }}][close]" type="time" value="{{ $period['close'] ?? '' }}" @if ($closed) disabled @endif>
                                </label>
                                @if ($index > 0)
                                    <button class="mini-button remove-hours-period" type="button" @if ($closed) disabled @endif>{{ __('Remove') }}</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="close-cell">
                        <input type="hidden" name="operating_hours[{{ $day }}][closed]" value="0">
                        <label title="{{ __('Closed') }}"><input class="checkbox-input operating-hours-closed" name="operating_hours[{{ $day }}][closed]" value="1" type="checkbox" @checked($closed)>{{ __('Closed') }}</label>
                        <button class="link-button add-hours-period" type="button" @if ($closed) disabled @endif>+ {{ __('Add time slot') }}</button>
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
                    <div class="food-item-fields">
                        <div class="food-item-field">
                            <label class="required">{{ __('Food Item Name') }}</label>
                            <input
                                name="food_items[{{ $index }}][name]"
                                value="{{ $item['name'] ?? '' }}"
                                placeholder="{{ __('e.g. Chicken Rice') }}"
                            >
                        </div>
                        <div class="food-item-field">
                            <label>{{ __('Description') }}</label>
                            <input
                                name="food_items[{{ $index }}][desc]"
                                value="{{ $item['desc'] ?? '' }}"
                                placeholder="{{ __('Brief description') }}"
                            >
                        </div>
                        <div class="food-item-field food-item-price-field">
                            <label>{{ __('Price (Optional)') }}</label>
                            <div class="food-item-price-control">
                                <span>RM</span>
                                <input
                                    name="food_items[{{ $index }}][price]"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    inputmode="decimal"
                                    value="{{ \App\Models\HeritageShopContribution::foodItemPriceInputValue($item['price'] ?? null) }}"
                                    placeholder="45.00"
                                >
                            </div>
                            @error("food_items.$index.price") <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="food-item-media">
                        <div class="food-item-image-field">
                            <label>{{ __('Food Image (Optional)') }}</label>
                            <div class="food-item-upload-row">
                                @php($foodItemImageUrl = $contribution?->foodItemImageUrl($item['image_path'] ?? null))
                                <button type="button" class="food-item-upload-button" data-food-item-upload>{{ __('Upload Image') }}</button>
                                <p class="food-item-image-note" data-food-item-file-name>{{ $foodItemImageUrl ? __('Current image saved') : __('JPG / PNG / WEBP, optional') }}</p>
                            </div>
                            <div class="food-item-preview-row">
                                <img
                                    class="food-item-image-preview"
                                    @if ($foodItemImageUrl) src="{{ $foodItemImageUrl }}" @else hidden @endif
                                    alt="{{ $foodItemImageUrl ? __('Saved food item image') : __('Selected food item image preview') }}"
                                    data-food-item-preview
                                >
                                <button type="button" class="remove-food-item-image" data-remove-food-item-image @if (! $foodItemImageUrl) hidden @endif>{{ __('Remove image') }}</button>
                            </div>
                            @if (filled($item['image_path'] ?? null))
                                <input type="hidden" name="food_items[{{ $index }}][image_path]" value="{{ $item['image_path'] }}">
                            @endif
                            <input
                                class="food-item-file-input"
                                name="food_items[{{ $index }}][image]"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                            >
                            @error("food_items.$index.image") <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="repeat-actions">
                            <button type="button" class="mini-button remove-food-item" aria-label="{{ __('Remove food item') }}">{{ __('Remove food item') }}</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" id="add-food-item" class="link-button">+ {{ __('Add food item') }}</button>
    </section>

    <section
        class="form-section supporting-media-shell"
        data-supporting-media-panel
        data-existing-media-count="{{ $existingMediaCount }}"
        data-max-supporting-media="{{ $maxSupportingMedia }}"
    >
        <h2 class="section-title">{{ __('Supporting media') }}</h2>

        <div class="media-count-panel" aria-live="polite">
            <strong id="media-slot-summary"></strong>
            <p id="media-slot-detail"></p>
        </div>

        @if ($existingMedia->isNotEmpty())
            <h3 class="media-subheading">{{ __('Existing media (:count)', ['count' => $existingMediaCount]) }}</h3>
            <div class="media-grid" aria-label="{{ __('Existing media') }}">
                @foreach ($existingMedia as $media)
                    <div class="media-card" data-existing-media-card>
                        @if ($media->media_type === 'video')
                            <video controls preload="metadata"><source src="{{ $media->url }}"></video>
                        @else
                            <img src="{{ $media->url }}" alt="{{ __('Previously uploaded supporting media') }}">
                        @endif
                        <label class="remove-media">
                            <input
                                class="checkbox-input"
                                type="checkbox"
                                name="remove_media[]"
                                value="{{ $media->id }}"
                                data-remove-media
                                @checked(in_array($media->id, array_map('intval', old('remove_media', [])), true))
                            >
                            <span data-remove-media-label>{{ __('Remove this file') }}</span>
                        </label>
                        <p class="media-removal-status">{{ __('Marked for removal') }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="field full">
            <label for="supporting_media">{{ __('Add images or videos') }}</label>
            <input id="supporting_media" name="supporting_media[]" type="file" accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.avi" multiple data-supporting-media-input>
            <p class="help-text">{{ __('Up to 6 files in total. JPG, JPEG, PNG, WEBP, MP4, MOV, or AVI; maximum 20 MB per file.') }}</p>
            <p id="media-limit-error" class="field-error media-limit-error" hidden></p>
            @error('supporting_media') <p class="field-error">{{ $message }}</p> @enderror
            @error('supporting_media.*') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <h3 id="new-media-title" class="media-subheading new-media-title" hidden>{{ __('New files selected') }}</h3>
        <div id="media-preview" class="media-grid" aria-live="polite"></div>
    </section>

    <div class="actions">
        <button class="button secondary" type="submit" name="submission_action" value="draft">
            {{ $isEdit ? __('Save changes') : __('Save as draft') }}
        </button>
        <button class="button primary" type="submit" name="submission_action" value="submit">
            {{ $contribution?->status === \App\Models\HeritageShopContribution::STATUS_REVISION_REQUIRED || $isWithdrawnResubmission ? __('Resubmit for review') : __('Submit for review') }}
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
        <div class="food-item-fields">
            <div class="food-item-field">
                <label class="required">{{ __('Food Item Name') }}</label>
                <input name="food_items[__INDEX__][name]" placeholder="{{ __('e.g. Chicken Rice') }}">
            </div>
            <div class="food-item-field">
                <label>{{ __('Description') }}</label>
                <input name="food_items[__INDEX__][desc]" placeholder="{{ __('Brief description') }}">
            </div>
            <div class="food-item-field food-item-price-field">
                <label>{{ __('Price (Optional)') }}</label>
                <div class="food-item-price-control">
                    <span>RM</span>
                    <input name="food_items[__INDEX__][price]" type="number" min="0" step="0.01" inputmode="decimal" placeholder="45.00">
                </div>
            </div>
        </div>
        <div class="food-item-media">
            <div class="food-item-image-field">
                <label>{{ __('Food Image (Optional)') }}</label>
                <div class="food-item-upload-row">
                    <button type="button" class="food-item-upload-button" data-food-item-upload>{{ __('Upload Image') }}</button>
                    <p class="food-item-image-note" data-food-item-file-name>{{ __('JPG / PNG / WEBP, optional') }}</p>
                </div>
                <div class="food-item-preview-row">
                    <img class="food-item-image-preview" alt="{{ __('Selected food item image preview') }}" data-food-item-preview hidden>
                    <button type="button" class="remove-food-item-image" data-remove-food-item-image hidden>{{ __('Remove image') }}</button>
                </div>
                <input class="food-item-file-input" name="food_items[__INDEX__][image]" type="file" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="repeat-actions"><button type="button" class="mini-button remove-food-item">{{ __('Remove food item') }}</button></div>
        </div>
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
        const bindFoodImagePreview = (row) => {
            const imageInput = row.querySelector('input[type="file"][name^="food_items["]');
            const previewImage = row.querySelector('[data-food-item-preview]');
            const uploadButton = row.querySelector('[data-food-item-upload]');
            const removeImageButton = row.querySelector('[data-remove-food-item-image]');
            const fileName = row.querySelector('[data-food-item-file-name]');

            uploadButton?.addEventListener('click', () => imageInput?.click());

            imageInput?.addEventListener('change', () => {
                const file = imageInput.files?.[0];
                if (! file || ! previewImage) {
                    return;
                }

                const url = URL.createObjectURL(file);
                previewImage.src = url;
                previewImage.hidden = false;
                if (removeImageButton) {
                    removeImageButton.hidden = false;
                }
                if (fileName) {
                    fileName.textContent = file.name;
                }
                previewImage.onload = () => URL.revokeObjectURL(url);
            });

            removeImageButton?.addEventListener('click', () => {
                const savedImagePath = row.querySelector('input[type="hidden"][name$="[image_path]"]');

                if (imageInput) {
                    imageInput.value = '';
                }
                savedImagePath?.remove();
                if (previewImage) {
                    previewImage.removeAttribute('src');
                    previewImage.hidden = true;
                }
                removeImageButton.hidden = true;
                if (fileName) {
                    fileName.textContent = @json(__('JPG / PNG / WEBP, optional'));
                }
            });
        };
        const bindRemove = (row) => row.querySelector('.remove-food-item')?.addEventListener('click', () => {
            if (shell.querySelectorAll('.food-item-row').length === 1) {
                row.querySelectorAll('input').forEach((input) => input.value = '');
                const previewImage = row.querySelector('[data-food-item-preview]');
                const removeImageButton = row.querySelector('[data-remove-food-item-image]');
                const fileName = row.querySelector('[data-food-item-file-name]');
                if (previewImage) {
                    previewImage.removeAttribute('src');
                    previewImage.hidden = true;
                }
                if (removeImageButton) {
                    removeImageButton.hidden = true;
                }
                if (fileName) {
                    fileName.textContent = @json(__('JPG / PNG / WEBP, optional'));
                }
                return;
            }
            row.remove();
            reindex();
        });

        shell?.querySelectorAll('.food-item-row').forEach((row) => {
            bindFoodImagePreview(row);
            bindRemove(row);
        });
        addButton?.addEventListener('click', () => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(shell.querySelectorAll('.food-item-row').length)).trim();
            const row = wrapper.firstElementChild;
            shell.appendChild(row);
            bindFoodImagePreview(row);
            bindRemove(row);
        });

        const mediaPanel = document.querySelector('[data-supporting-media-panel]');
        const input = document.getElementById('supporting_media');
        const preview = document.getElementById('media-preview');
        const mediaSlotSummary = document.getElementById('media-slot-summary');
        const mediaSlotDetail = document.getElementById('media-slot-detail');
        const mediaLimitError = document.getElementById('media-limit-error');
        const newMediaTitle = document.getElementById('new-media-title');
        const removeMediaInputs = [...document.querySelectorAll('[data-remove-media]')];
        const form = document.querySelector('form.form-grid');
        const hourRows = document.querySelectorAll('.soft-card-row');
        const maxSupportingMedia = Number(mediaPanel?.dataset.maxSupportingMedia || 6);
        const existingMediaCount = Number(mediaPanel?.dataset.existingMediaCount || 0);
        let selectedSupportingFiles = [];

        const pluralizeFile = (count) => count === 1 ? 'file' : 'files';
        const retainedExistingMediaCount = () => existingMediaCount - removeMediaInputs.filter((checkbox) => checkbox.checked).length;
        const usedSupportingMediaCount = () => retainedExistingMediaCount() + selectedSupportingFiles.length;
        const availableNewMediaSlots = () => Math.max(0, maxSupportingMedia - retainedExistingMediaCount());
        const remainingSupportingMediaSlots = () => Math.max(0, maxSupportingMedia - usedSupportingMediaCount());

        const setMediaError = (message = '') => {
            if (! mediaLimitError) {
                return;
            }

            mediaLimitError.textContent = message;
            mediaLimitError.hidden = message === '';
        };

        const syncSupportingMediaInput = () => {
            if (! input) {
                return true;
            }

            if (typeof DataTransfer === 'undefined') {
                input.value = '';
                selectedSupportingFiles = [];

                return false;
            }

            const transfer = new DataTransfer();
            selectedSupportingFiles.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;

            return true;
        };

        const updateExistingMediaCards = () => {
            removeMediaInputs.forEach((checkbox) => {
                const card = checkbox.closest('[data-existing-media-card]');
                const label = card?.querySelector('[data-remove-media-label]');

                card?.classList.toggle('marked-for-removal', checkbox.checked);
                if (label) {
                    label.textContent = checkbox.checked
                        ? @json(__('Undo removal'))
                        : @json(__('Remove this file'));
                }
            });
        };

        const updateSupportingMediaCounts = () => {
            const used = usedSupportingMediaCount();
            const remaining = remainingSupportingMediaSlots();

            if (mediaSlotSummary) {
                mediaSlotSummary.textContent = `${used} of ${maxSupportingMedia} ${pluralizeFile(maxSupportingMedia)} selected.`;
            }

            if (mediaSlotDetail) {
                mediaSlotDetail.textContent = remaining === 0
                    ? @json(__('Maximum of 6 supporting media files reached.'))
                    : `You can add up to ${remaining} more ${pluralizeFile(remaining)}.`;
            }

            input?.toggleAttribute('data-maximum-reached', remaining === 0);
        };

        const renderSupportingMediaPreview = () => {
            preview?.replaceChildren();
            if (newMediaTitle) {
                newMediaTitle.hidden = selectedSupportingFiles.length === 0;
            }

            selectedSupportingFiles.forEach((file, index) => {
                const card = document.createElement('div');
                card.className = 'media-card new-media-card';
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

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'new-media-remove';
                button.textContent = @json(__('Remove new file'));
                button.addEventListener('click', () => {
                    selectedSupportingFiles.splice(index, 1);
                    const synced = syncSupportingMediaInput();
                    setMediaError(synced ? '' : @json(__('Your browser cleared the selected files. Please choose them again.')));
                    renderSupportingMediaPreview();
                    updateSupportingMediaCounts();
                });
                card.appendChild(button);
                preview?.appendChild(card);
            });

            if (selectedSupportingFiles.length) {
                const note = document.createElement('p');
                note.className = 'preview-note';
                note.textContent = newFilesSelectedMessage.replace(
                    ':count',
                    selectedSupportingFiles.length
                );
                preview?.appendChild(note);
            }
        };

        removeMediaInputs.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                if (! checkbox.checked && usedSupportingMediaCount() > maxSupportingMedia) {
                    checkbox.checked = true;
                    setMediaError(@json(__('Remove a newly selected file before keeping this saved file.')));
                } else {
                    setMediaError('');
                }

                updateExistingMediaCards();
                updateSupportingMediaCounts();
            });
        });

        updateExistingMediaCards();
        updateSupportingMediaCounts();

        const reindexHourRow = (row) => {
            const day = row.dataset.hoursDay;
            row.querySelectorAll('[data-hours-period]').forEach((periodRow, index) => {
                periodRow.querySelectorAll('input[type="time"]').forEach((timeInput) => {
                    const part = timeInput.name.endsWith('[close]') ? 'close' : 'open';
                    timeInput.name = `operating_hours[${day}][periods][${index}][${part}]`;
                });
                const removeButton = periodRow.querySelector('.remove-hours-period');
                if (removeButton && index === 0) {
                    removeButton.remove();
                }
                if (index > 0 && ! removeButton) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'mini-button remove-hours-period';
                    button.textContent = 'Remove';
                    periodRow.appendChild(button);
                    button.addEventListener('click', () => {
                        const rows = Array.from(row.querySelectorAll('[data-hours-period]'));
                        if (rows.length <= 1) {
                            return;
                        }
                        periodRow.remove();
                        reindexHourRow(row);
                        syncHourRow(row);
                    });
                }
            });
        };

        const syncHourRow = (row) => {
            const day = row.dataset.hoursDay;
            const closedInput = row.querySelector('.operating-hours-closed');
            const timeInputs = row.querySelectorAll('input[type="time"]');
            const addButton = row.querySelector('.add-hours-period');
            const removeButtons = row.querySelectorAll('.remove-hours-period');
            const closed = Boolean(closedInput?.checked);

            timeInputs.forEach((timeInput) => {
                timeInput.disabled = closed;
            });
            removeButtons.forEach((button) => {
                button.disabled = closed;
            });
            if (addButton) {
                addButton.disabled = closed;
            }
            if (closed) {
                const hidden = row.querySelector('input[type="hidden"][name^="operating_hours[' + day + '][closed]"]');
                if (hidden) {
                    hidden.value = '1';
                }
            } else {
                const hidden = row.querySelector('input[type="hidden"][name^="operating_hours[' + day + '][closed]"]');
                if (hidden) {
                    hidden.value = '0';
                }
            }
        };

        hourRows.forEach((row) => {
            const day = row.dataset.hoursDay;
            const addButton = row.querySelector('.add-hours-period');
            const closedInput = row.querySelector('.operating-hours-closed');

            row.querySelectorAll('[data-hours-period]').forEach((periodRow) => {
                const removeButton = periodRow.querySelector('.remove-hours-period');
                if (removeButton) {
                    removeButton.addEventListener('click', () => {
                        const rows = Array.from(row.querySelectorAll('[data-hours-period]'));
                        if (rows.length <= 1) {
                            return;
                        }
                        periodRow.remove();
                        reindexHourRow(row);
                        syncHourRow(row);
                    });
                }
            });

            addButton?.addEventListener('click', () => {
                const periods = row.querySelector('[data-hours-periods]');
                if (! periods) {
                    return;
                }
                const rowIndex = periods.querySelectorAll('[data-hours-period]').length;
                const newPeriod = document.createElement('div');
                newPeriod.className = 'hours-period-row';
                newPeriod.dataset.hoursPeriod = '';
                newPeriod.innerHTML = `
                    <label>
                        <span>Open</span>
                        <input name="operating_hours[${day}][periods][${rowIndex}][open]" type="time" value="">
                    </label>
                    <label>
                        <span>Close</span>
                        <input name="operating_hours[${day}][periods][${rowIndex}][close]" type="time" value="">
                    </label>
                    <button class="mini-button remove-hours-period" type="button">Remove</button>
                `;
                periods.appendChild(newPeriod);
                const removeButton = newPeriod.querySelector('.remove-hours-period');
                removeButton?.addEventListener('click', () => {
                    const rows = Array.from(row.querySelectorAll('[data-hours-period]'));
                    if (rows.length <= 1) {
                        return;
                    }
                    newPeriod.remove();
                    reindexHourRow(row);
                    syncHourRow(row);
                });
                syncHourRow(row);
            });

            closedInput?.addEventListener('change', () => {
                if (closedInput.checked) {
                    row.querySelectorAll('input[type="time"]').forEach((timeInput) => {
                        timeInput.value = '';
                    });
                }
                syncHourRow(row);
            });

            row.querySelectorAll('input[type="time"]').forEach((timeInput) => {
                timeInput.addEventListener('input', () => {
                    if (timeInput.value && closedInput) {
                        closedInput.checked = false;
                    }
                    syncHourRow(row);
                });
            });

            reindexHourRow(row);
            syncHourRow(row);
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
            const incomingFiles = [...input.files];
            const availableSlots = availableNewMediaSlots();

            if (incomingFiles.length > availableSlots) {
                const keptExisting = retainedExistingMediaCount();
                const savedContext = keptExisting > 0
                    ? `You already have ${keptExisting} saved ${pluralizeFile(keptExisting)} selected to keep. `
                    : '';

                setMediaError(`${savedContext}You can add up to ${availableSlots} more ${pluralizeFile(availableSlots)}.`);
                syncSupportingMediaInput();
                renderSupportingMediaPreview();
                updateSupportingMediaCounts();
                return;
            }

            selectedSupportingFiles = incomingFiles;
            setMediaError('');
            if (typeof DataTransfer !== 'undefined') {
                syncSupportingMediaInput();
            }
            renderSupportingMediaPreview();
            updateSupportingMediaCounts();
        });
    })();
</script>
@endpush
