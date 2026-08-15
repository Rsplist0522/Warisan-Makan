@extends('community-contributions.layout')

@section('title', 'Report Incorrect Information')

@section('content')
    <a class="button secondary small page-back" href="{{ route('heritage-shops.show', $heritageShop) }}">Back to shop profile</a>

    <header class="page-header">
        <div>
            <p class="eyebrow">Correction request</p>
            <h1>Report Incorrect Information</h1>
            <p>{{ $heritageShop->shop_name }} will remain published while an administrator reviews your correction.</p>
        </div>
    </header>

    <form class="form-grid" method="POST" action="{{ route('heritage-shops.correction-requests.store', $heritageShop) }}" enctype="multipart/form-data">
        @csrf

        <section class="form-section">
            <h2 class="section-title">Shop being reported</h2>
            <dl class="definition-grid">
                <div><dt>Shop</dt><dd>{{ $heritageShop->shop_name }}</dd></div>
                <div><dt>Location</dt><dd>{{ $heritageShop->location ?: 'Not provided' }}</dd></div>
            </dl>
        </section>

        <section class="form-section">
            <h2 class="section-title">Correction details</h2>
            <div class="field-grid">
                <div class="field full">
                    <label class="required" for="field_name">Incorrect field/information</label>
                    <select id="field_name" name="field_name" required>
                        <option value="">Choose a published shop field</option>
                        @foreach ($allowedFields as $field => $label)
                            <option value="{{ $field }}" @selected(old('field_name') === $field)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="current_value_display">Current information</label>
                    <textarea id="current_value_display" readonly placeholder="Choose a field to view the current published information"></textarea>
                    <p class="help-text">This is filled from the published Heritage Shop profile.</p>
                </div>
                <div class="field">
                    <label class="required" for="suggested_value" id="suggested_value_label">Suggested corrected information</label>
                    <textarea id="suggested_value" name="suggested_value" required placeholder="Choose a field first, then enter the corrected value">{{ old('suggested_value') }}</textarea>
                    <p class="help-text" id="suggested_value_help">Use the same kind of information shown in the current field.</p>
                </div>
                <div class="field full">
                    <label class="required" for="reason" id="reason_label">Reason for correction</label>
                    <textarea id="reason" name="reason" required placeholder="Explain how you know this information should be corrected">{{ old('reason') }}</textarea>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="section-title">Supporting evidence</h2>
            <div class="field">
                <label for="evidence">Evidence files</label>
                <input id="evidence" type="file" name="evidence[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
                <p class="help-text">Upload up to 6 image or document files. Each file may be up to 10 MB.</p>
            </div>
        </section>

        <div class="actions">
            <button class="button primary" type="submit">Submit correction request</button>
            <a class="button secondary" href="{{ route('community-contribution.correction-requests') }}">My correction requests</a>
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
                shop_name: ['Correct shop name', 'Enter the exact published name of the shop.'],
                primary_food_category: ['Correct food category', 'Example: Traditional noodles, street food, desserts, rice dishes.'],
                establishment_year: ['Correct establishment year', 'Use a 4-digit year, for example 1956.'],
                founder_name: ['Correct founder name', 'Enter the founder name as it should appear publicly.'],
                founder_background: ['Correct founder background', 'Provide the corrected background or origin details.'],
                current_owner_name: ['Correct current owner name', 'Enter the current owner or operator name.'],
                current_owner_details: ['Correct current owner details', 'Provide updated ownership or operator notes.'],
                heritage_story: ['Correct heritage story', 'Provide the corrected story text.'],
                operating_hours: ['Correct operating hours', 'Enter the corrected opening days and times.'],
                food_items: ['Correct food items', 'List the corrected menu or signature item details.'],
                contact_number: ['Correct contact number', 'Enter the updated phone number.'],
                address: ['Correct address', 'Enter the corrected street address.'],
                city: ['Correct city', 'Enter the corrected city.'],
                state: ['Correct state', 'Enter the corrected state.'],
                postal_code: ['Correct postal code', 'Enter the corrected postal code.'],
                latitude: ['Correct latitude', 'Enter a decimal latitude between -90 and 90.'],
                longitude: ['Correct longitude', 'Enter a decimal longitude between -180 and 180.'],
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
                    ? `Explain why the published ${label.toLowerCase()} should be changed.`
                    : 'Explain how you know this information should be corrected';
            };

            fieldSelect.addEventListener('change', updateFieldContext);
            updateFieldContext();
        })();
    </script>
@endpush
