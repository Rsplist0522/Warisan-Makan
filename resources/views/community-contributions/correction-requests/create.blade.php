@extends('community-contributions.layout')

@section('title', __('Report Incorrect Information'))

@section('content')
    <a class="button secondary small page-back" href="{{ route('heritage-shops.show', $heritageShop) }}">{{ __('Back to shop profile') }}</a>

    <header class="page-header">
        <div>
            <p class="eyebrow">{{ __('Correction request') }}</p>
            <h1>{{ __('Report Incorrect Information') }}</h1>
            <p>{{ __(':shop will remain published while an administrator reviews your correction.', ['shop' => $heritageShop->shop_name]) }}</p>
        </div>
    </header>

    <form class="form-grid" method="POST" action="{{ route('heritage-shops.correction-requests.store', $heritageShop) }}" enctype="multipart/form-data">
        @csrf

        <section class="form-section">
            <h2 class="section-title">{{ __('Shop being reported') }}</h2>
            <dl class="definition-grid">
                <div><dt>{{ __('Shop') }}</dt><dd>{{ $heritageShop->shop_name }}</dd></div>
                <div><dt>{{ __('Location') }}</dt><dd>{{ $heritageShop->location ?: __('Not provided') }}</dd></div>
            </dl>
        </section>

        <section class="form-section">
            <h2 class="section-title">{{ __('Correction details') }}</h2>
            <div class="field-grid">
                <div class="field full">
                    <label class="required" for="field_name">{{ __('Incorrect field/information') }}</label>
                    <select id="field_name" name="field_name" required>
                        <option value="">{{ __('Choose a published shop field') }}</option>
                        @foreach ($allowedFields as $field => $label)
                            <option value="{{ $field }}" @selected(old('field_name') === $field)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="current_value_display">{{ __('Current information') }}</label>
                    <textarea id="current_value_display" readonly placeholder="{{ __('Choose a field to view the current published information') }}"></textarea>
                    <p class="help-text">{{ __('This is filled from the published Heritage Shop profile.') }}</p>
                </div>
                <div class="field">
                    <label class="required" for="suggested_value" id="suggested_value_label">{{ __('Suggested corrected information') }}</label>
                    <textarea id="suggested_value" name="suggested_value" required placeholder="{{ __('Choose a field first, then enter the corrected value') }}">{{ old('suggested_value') }}</textarea>
                    <p class="help-text" id="suggested_value_help">{{ __('Use the same kind of information shown in the current field.') }}</p>
                </div>
                <div class="field full">
                    <label class="required" for="reason" id="reason_label">{{ __('Reason for correction') }}</label>
                    <textarea id="reason" name="reason" required placeholder="{{ __('Explain how you know this information should be corrected') }}">{{ old('reason') }}</textarea>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="section-title">{{ __('Supporting evidence') }}</h2>
            <div class="field">
                <label for="evidence">{{ __('Evidence files') }}</label>
                <input id="evidence" type="file" name="evidence[]" multiple accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.avi">
                <p class="help-text">{{ __('Upload up to 6 image or document files. Each file may be up to 10 MB.') }}</p>
            </div>
        </section>

        <div class="actions">
            <button class="button primary" type="submit">{{ __('Submit correction request') }}</button>
            <a class="button secondary" href="{{ route('community-contribution.correction-requests') }}">{{ __('My correction requests') }}</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const fieldSelect = document.getElementById('field_name');
            const currentValue = document.getElementById('current_value_display');
            const suggestedValue = document.getElementById('suggested_value');
            const suggestedLabel = document.getElementById('suggested_value_label');
            const suggestedHelp = document.getElementById('suggested_value_help');
            const reason = document.getElementById('reason');
            const fields = @json($allowedFields);
            const currentValues = @json($currentValues);
            const hints = {
    shop_name: [
        @json(__('Correct shop name')),
        @json(__('Enter the exact published name of the shop.'))
    ],

    primary_food_category: [
        @json(__('Correct food category')),
        @json(__('Example: Traditional noodles, street food, desserts, rice dishes.'))
    ],

    establishment_year: [
        @json(__('Correct establishment year')),
        @json(__('Use a 4-digit year, for example 1956.'))
    ],

    founder_name: [
        @json(__('Correct founder name')),
        @json(__('Enter the founder name as it should appear publicly.'))
    ],

    founder_background: [
        @json(__('Correct founder background')),
        @json(__('Provide the corrected background or origin details.'))
    ],

    current_owner_name: [
        @json(__('Correct current owner name')),
        @json(__('Enter the current owner or operator name.'))
    ],

    current_owner_details: [
        @json(__('Correct current owner details')),
        @json(__('Provide updated ownership or operator notes.'))
    ],

    heritage_story: [
        @json(__('Correct heritage story')),
        @json(__('Provide the corrected story text.'))
    ],

    operating_hours: [
        @json(__('Correct operating hours')),
        @json(__('Enter the corrected opening days and times.'))
    ],

    food_items: [
        @json(__('Correct food items')),
        @json(__('List the corrected menu or signature item details.'))
    ],

    contact_number: [
        @json(__('Correct contact number')),
        @json(__('Enter the updated phone number.'))
    ],

    address: [
        @json(__('Correct address')),
        @json(__('Enter the corrected street address.'))
    ],

    city: [
        @json(__('Correct city')),
        @json(__('Enter the corrected city.'))
    ],

    state: [
        @json(__('Correct state')),
        @json(__('Enter the corrected state.'))
    ],

    postal_code: [
        @json(__('Correct postal code')),
        @json(__('Enter the corrected postal code.'))
    ],

    latitude: [
        @json(__('Correct latitude')),
        @json(__('Enter a decimal latitude between -90 and 90.'))
    ],

    longitude: [
        @json(__('Correct longitude')),
        @json(__('Enter a decimal longitude between -180 and 180.'))
    ],
};

            const updateFieldContext = () => {
                const field = fieldSelect.value;
                const label = fields[field] || 'information';
                const hint = hints[field] || [`Correct ${label.toLowerCase()}`, 'Use the same kind of information shown in the current field.'];

                currentValue.value = field ? (currentValues[field] || 'Not provided') : '';
                suggestedLabel.textContent = hint[0];
                suggestedValue.placeholder = hint[1];
                suggestedHelp.textContent = hint[1];
                reason.placeholder = field
                    ? @json(__('Explain why the published :field should be changed.')).replace(':field', label.toLowerCase())
                    : @json(__('Explain how you know this information should be corrected'));

            fieldSelect.addEventListener('change', updateFieldContext);
            updateFieldContext();
        })();
    </script>
@endpush
