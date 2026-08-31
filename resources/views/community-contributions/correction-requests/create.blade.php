@extends('community-contributions.layout')

@section('title', 'Report Incorrect Information')

@php($structuredFields = \App\Models\CorrectionRequest::structuredFieldTargets())
@php($selectedCorrectionField = old('field_name', ''))
@php($hasSelectedCorrectionField = filled($selectedCorrectionField))
@php($isStructuredCorrection = array_key_exists($selectedCorrectionField, $structuredFields))
@php($isOperatingHoursCorrection = $selectedCorrectionField === 'operating_hours')
@php($isGenericCorrection = $hasSelectedCorrectionField && ! $isStructuredCorrection && ! $isOperatingHoursCorrection)
@php($oldOperatingHours = old('operating_hours_correction'))
@php($operatingHourEditor = is_array($oldOperatingHours) ? array_replace($operatingHoursCorrection, $oldOperatingHours) : $operatingHoursCorrection)
@php($displayCurrent = fn ($value): string => filled($value) ? (string) $value : __('Not provided'))
@php($structuredCurrentValues = [
    'founder_information' => [
        'Founder Name' => $heritageShop->founder_name,
        'Founder Background' => $heritageShop->founder_background,
    ],
    'current_owner_information' => [
        'Owner / Operator Name' => $heritageShop->current_owner_name,
        'Owner / Operator Details' => $heritageShop->current_owner_details,
    ],
    'address_location' => [
        'Street Address' => $heritageShop->address,
        'City' => $heritageShop->city,
        'State' => $heritageShop->state,
        'Postal Code' => $heritageShop->postal_code,
    ],
])
@php($structuredMeta = [
    'founder_information' => [
        'current' => 'Current Founder Information',
        'proposed' => 'Proposed Founder Correction',
        'help' => 'Provide only the founder details you know should be corrected.',
    ],
    'current_owner_information' => [
        'current' => 'Current Owner / Operator Information',
        'proposed' => 'Proposed Owner / Operator Correction',
        'help' => 'Provide only the owner/operator details you know should be corrected.',
    ],
    'address_location' => [
        'current' => 'Current Address Details',
        'proposed' => 'Proposed Address Correction',
        'help' => 'Provide only the address details you know should be corrected.',
    ],
])
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
                <div><dt>Address reference</dt><dd>{{ $heritageShop->location ?: 'Not provided' }}</dd></div>
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
                <div class="field full correction-generic-fields" id="generic_correction_component" @if (! $isGenericCorrection) hidden @endif>
                    <div class="correction-comparison">
                        <section class="correction-panel">
                            <h3 id="current_value_label">Current Information</h3>
                            <div class="current-display" id="current_value_display">{{ $isGenericCorrection ? ($currentValues[$selectedCorrectionField] ?? __('Not provided')) : '' }}</div>
                            <p class="help-text">This is filled from the published Heritage Shop profile.</p>
                        </section>
                        <section class="correction-panel">
                            <div class="field">
                                <label class="required" for="suggested_value_input" id="suggested_value_label">Suggested corrected information</label>
                                <input id="suggested_value_input" name="suggested_value" value="{{ old('suggested_value') }}" @if (! $isGenericCorrection) disabled @endif placeholder="Choose a field first, then enter the corrected value">
                                <textarea id="suggested_value_textarea" name="suggested_value" @if (! $isGenericCorrection) disabled @endif placeholder="Choose a field first, then enter the corrected value">{{ old('suggested_value') }}</textarea>
                                <p class="help-text" id="suggested_value_help">Use the same kind of information shown in the current field.</p>
                                @error('suggested_value') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </section>
                    </div>
                </div>
                @foreach ($structuredFields as $field => $targets)
                    @php($structuredGroupActive = $selectedCorrectionField === $field)
                    <div class="field full correction-structured-fields" data-correction-structured="{{ $field }}" @if (! $structuredGroupActive) hidden @endif>
                        <div class="correction-comparison">
                            <section class="correction-panel">
                                <h3>{{ $structuredMeta[$field]['current'] }}</h3>
                                <dl class="correction-current-list">
                                    @foreach ($structuredCurrentValues[$field] as $label => $value)
                                        <div>
                                            <dt>{{ $label }}</dt>
                                            <dd>{{ $displayCurrent($value) }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </section>
                            <section class="correction-panel">
                                <h3>{{ $structuredMeta[$field]['proposed'] }}</h3>
                                <div class="field-grid" style="margin-top:12px">
                                    @foreach ($targets as $target => $label)
                                        <div class="field {{ str_contains($target, 'background') || str_contains($target, 'details') || $target === 'address' ? 'full' : '' }}">
                                            <label for="suggested_fields_{{ $target }}">{{ $label }}</label>
                                            @if (str_contains($target, 'background') || str_contains($target, 'details') || $target === 'address')
                                                <textarea
                                                    id="suggested_fields_{{ $target }}"
                                                    name="suggested_fields[{{ $target }}]"
                                                    @if (! $structuredGroupActive) disabled @endif
                                                    placeholder="Leave blank if this part is already correct or unknown"
                                                >{{ old("suggested_fields.{$target}") }}</textarea>
                                            @else
                                                <input
                                                    id="suggested_fields_{{ $target }}"
                                                    name="suggested_fields[{{ $target }}]"
                                                    value="{{ old("suggested_fields.{$target}") }}"
                                                    @if (! $structuredGroupActive) disabled @endif
                                                    placeholder="Leave blank if this part is already correct or unknown"
                                                >
                                            @endif
                                            @error("suggested_fields.{$target}") <p class="field-error">{{ $message }}</p> @enderror
                                        </div>
                                    @endforeach
                                </div>
                                <p class="help-text">{{ $structuredMeta[$field]['help'] }}</p>
                            </section>
                        </div>
                    </div>
                @endforeach
                @error('suggested_fields') <p class="field-error">{{ $message }}</p> @enderror

                <div class="field full correction-operating-hours" id="operating_hours_component" @if (! $isOperatingHoursCorrection) hidden @endif>
                    <div class="correction-comparison">
                        <section class="correction-panel">
                            <h3>Current Operating Hours</h3>
                            <div class="current-display">{{ $currentValues['operating_hours'] ?? __('Not provided') }}</div>
                        </section>
                        <section class="correction-panel">
                            <h3>Correct Operating Hours</h3>
                            <div class="hours-editor" data-hours-editor>
                                @foreach (\App\Models\CorrectionRequest::OPERATING_HOUR_DAYS as $day)
                                    @php($schedule = is_array($operatingHourEditor[$day] ?? null) ? $operatingHourEditor[$day] : ['closed' => false, 'periods' => []])
                                    @php($closed = filter_var($schedule['closed'] ?? false, FILTER_VALIDATE_BOOLEAN))
                                    @php($periods = collect($schedule['periods'] ?? [])->filter(fn ($period) => is_array($period))->values()->all())
                                    <section class="hours-day-row" data-hours-day="{{ $day }}">
                                        <div class="hours-day-cell">
                                            <strong>{{ $day }}</strong>
                                            <label class="hours-closed-toggle">
                                                <input type="hidden" name="operating_hours_correction[{{ $day }}][closed]" value="0" @if (! $isOperatingHoursCorrection) disabled @endif>
                                                <input class="checkbox-input operating-correction-closed" name="operating_hours_correction[{{ $day }}][closed]" value="1" type="checkbox" @checked($closed) @if (! $isOperatingHoursCorrection) disabled @endif>
                                                Closed
                                            </label>
                                        </div>
                                        <div class="hours-period-cell">
                                            <div class="hours-periods" data-hours-periods>
                                                @foreach ($periods as $index => $period)
                                                    <div class="hours-period-row" data-hours-period>
                                                        <label>
                                                            <span>Open</span>
                                                            <input name="operating_hours_correction[{{ $day }}][periods][{{ $index }}][open]" type="time" value="{{ $period['open'] ?? '' }}" @if (! $isOperatingHoursCorrection || $closed) disabled @endif>
                                                        </label>
                                                        <label>
                                                            <span>Close</span>
                                                            <input name="operating_hours_correction[{{ $day }}][periods][{{ $index }}][close]" type="time" value="{{ $period['close'] ?? '' }}" @if (! $isOperatingHoursCorrection || $closed) disabled @endif>
                                                        </label>
                                                        @if ($index > 0)
                                                            <button class="mini-button remove-hours-period" type="button" @if (! $isOperatingHoursCorrection || $closed) disabled @endif>Remove</button>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                            <button class="link-button add-hours-period" type="button" @if (! $isOperatingHoursCorrection || $closed) disabled @endif>+ Add another time period</button>
                                        </div>
                                    </section>
                                @endforeach
                            </div>
                            @error('operating_hours_correction') <p class="field-error">{{ $message }}</p> @enderror
                        </section>
                    </div>
                </div>
                <div class="field full" id="reason_field" @if (! $hasSelectedCorrectionField) hidden @endif>
                    <label class="required" for="reason" id="reason_label">Reason for correction</label>
                    <textarea id="reason" name="reason" @if ($hasSelectedCorrectionField) required @else disabled @endif placeholder="Explain how you know this information should be corrected">{{ old('reason') }}</textarea>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="section-title">Supporting evidence</h2>
            <div class="field">
                <label for="evidence">Evidence files</label>
                <input id="evidence" type="file" name="evidence[]" multiple accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.avi">
                <p class="help-text">Upload up to 6 image or document files. Each file may be up to 10 MB.</p>
            </div>
        </section>

        <div class="actions">
            <button class="button primary" type="submit">Submit correction request</button>
            <a class="button secondary" href="{{ route('community-contribution.correction-requests') }}">My correction requests</a>
        </div>
    </form>
@endsection

@push('styles')
    <style>
        [hidden] {
            display: none !important;
        }

        .correction-comparison {
            display: grid;
            gap: 14px;
        }

        .correction-panel {
            display: grid;
            gap: 12px;
            padding: 16px;
            border: 1px solid var(--wm-border);
            border-radius: 14px;
            background: rgba(255, 255, 255, .58);
        }

        .correction-panel h3 {
            color: var(--wm-accent);
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1rem;
            text-transform: uppercase;
        }

        .current-display {
            white-space: pre-line;
            line-height: 1.6;
        }

        .correction-current-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 18px;
            margin: 0;
        }

        .correction-current-list.compact {
            grid-template-columns: 1fr;
        }

        .correction-current-list > div {
            min-width: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--wm-border);
        }

        .correction-current-list dd {
            line-height: 1.55;
        }

        .map-preview {
            position: relative;
            display: grid;
            min-height: 220px;
            place-items: center;
            overflow: hidden;
            border: 1px solid var(--wm-border);
            border-radius: 14px;
            background: #fbf6f1;
            color: var(--wm-muted);
            text-align: center;
        }

        .map-preview.interactive {
            min-height: 280px;
        }

        .map-search-controls {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
        }

        .hours-editor {
            display: grid;
            gap: 0;
            overflow: hidden;
            border: 1px solid var(--wm-border);
            border-radius: 14px;
            background: #fffdf9;
        }

        .hours-day-row {
            display: grid;
            grid-template-columns: minmax(140px, .55fr) minmax(0, 1.45fr);
            gap: 12px;
            align-items: start;
            padding: 12px 14px;
            border-top: 1px solid var(--wm-border);
        }

        .hours-day-row:first-child {
            border-top: 0;
        }

        .hours-day-cell {
            display: flex;
            min-height: 42px;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .hours-closed-toggle {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-transform: none;
            letter-spacing: 0;
        }

        .hours-period-cell,
        .hours-periods {
            display: grid;
            gap: 8px;
        }

        .hours-period-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
            gap: 10px;
            align-items: end;
        }

        .hours-period-row label {
            display: grid;
            gap: 5px;
        }

        .hours-period-row label span {
            color: var(--wm-muted);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .hours-day-row.is-closed .hours-periods,
        .hours-day-row.is-closed .add-hours-period {
            display: none;
        }

        @media (max-width: 760px) {
            .correction-current-list,
            .hours-day-row,
            .hours-period-row,
            .map-search-controls {
                grid-template-columns: 1fr;
            }

            .hours-day-cell {
                align-items: start;
                flex-direction: column;
            }

            .hours-period-row .mini-button {
                justify-self: start;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (() => {
            const fieldSelect = document.getElementById('field_name');
            const genericComponent = document.getElementById('generic_correction_component');
            const currentValueLabel = document.getElementById('current_value_label');
            const currentValue = document.getElementById('current_value_display');
            const suggestedInput = document.getElementById('suggested_value_input');
            const suggestedTextarea = document.getElementById('suggested_value_textarea');
            const suggestedLabel = document.getElementById('suggested_value_label');
            const suggestedHelp = document.getElementById('suggested_value_help');
            const operatingHoursComponent = document.getElementById('operating_hours_component');
            const reasonField = document.getElementById('reason_field');
            const reason = document.getElementById('reason');
            const fields = @json($allowedFields);
            const structuredFields = @json($structuredFields);
            const structuredGroups = document.querySelectorAll('[data-correction-structured]');
            const currentValues = @json($currentValues);
            const narrativeFields = new Set(['heritage_story']);
            const hints = {
                shop_name: ['Correct shop name', 'Enter the exact published name of the shop.'],
                primary_food_category: ['Correct primary food category', 'Example: Hakka cuisine, Nyonya cuisine, traditional noodles.'],
                establishment_year: ['Correct establishment year', 'Use a 4-digit year, for example 1956.'],
                founder_information: ['Correct founder information', 'Provide only the founder details you know should be corrected.'],
                current_owner_information: ['Correct owner / operator information', 'Provide only the owner/operator details you know should be corrected.'],
                heritage_story: ['Correct heritage story', 'Provide the corrected story text.'],
                operating_hours: ['Correct operating hours', 'Enter the corrected opening days and times.'],
                contact_number: ['Correct contact number', 'Enter the updated phone number.'],
                address_location: ['Correct address details', 'Provide only the address details you know should be corrected.'],
            };
            const currentTitles = {
                shop_name: 'Current Shop Name',
                primary_food_category: 'Current Primary Food Category',
                establishment_year: 'Current Establishment Year',
                heritage_story: 'Current Information',
                contact_number: 'Current Contact Number',
            };
            const reasonPlaceholders = {
                founder_information: 'Explain why the published founder information should be changed.',
                current_owner_information: 'Explain why the published owner/operator information should be changed.',
                address_location: 'Explain why the published address details should be changed.',
                operating_hours: 'Explain why the published operating hours should be changed.',
            };

            const setControlsDisabled = (element, disabled) => {
                element?.querySelectorAll('input, textarea, select, button').forEach((control) => {
                    control.disabled = disabled;
                });
            };

            const syncGenericInput = (field, active) => {
                const useTextarea = narrativeFields.has(field);
                suggestedInput.hidden = useTextarea;
                suggestedTextarea.hidden = !useTextarea;
                suggestedInput.disabled = !active || useTextarea;
                suggestedTextarea.disabled = !active || !useTextarea;
                suggestedInput.required = active && !useTextarea;
                suggestedTextarea.required = active && useTextarea;
            };

            const updateFieldContext = () => {
                const field = fieldSelect.value;
                const hasField = field !== '';
                const label = fields[field] || 'information';
                const hint = hints[field] || [`Correct ${label.toLowerCase()}`, 'Use the same kind of information shown in the current field.'];
                const isStructured = Object.prototype.hasOwnProperty.call(structuredFields, field);
                const isOperatingHours = field === 'operating_hours';
                const isGeneric = hasField && ! isStructured && ! isOperatingHours;

                genericComponent.hidden = ! isGeneric;
                currentValue.textContent = isGeneric ? (currentValues[field] || 'Not provided') : '';
                currentValueLabel.textContent = currentTitles[field] || 'Current Information';
                suggestedLabel.textContent = hint[0];
                suggestedInput.placeholder = hint[1];
                suggestedTextarea.placeholder = hint[1];
                suggestedHelp.textContent = hint[1];
                syncGenericInput(field, isGeneric);
                structuredGroups.forEach((group) => {
                    const hidden = group.dataset.correctionStructured !== field;
                    group.hidden = hidden;
                    setControlsDisabled(group, hidden);
                });
                operatingHoursComponent.hidden = ! isOperatingHours;
                setControlsDisabled(operatingHoursComponent, ! isOperatingHours);
                syncAllClosedStates();
                reasonField.hidden = ! hasField;
                reason.required = hasField;
                reason.disabled = ! hasField;
                reason.placeholder = reasonPlaceholders[field] || (field
                    ? `Explain why the published ${label.toLowerCase()} should be changed.`
                    : 'Explain how you know this information should be corrected');
            };

            const reindexDay = (card) => {
                const day = card.dataset.hoursDay;
                card.querySelectorAll('[data-hours-period]').forEach((row, index) => {
                    row.querySelectorAll('input[type="time"]').forEach((input) => {
                        const part = input.name.endsWith('[close]') ? 'close' : 'open';
                        input.name = `operating_hours_correction[${day}][periods][${index}][${part}]`;
                    });
                });
            };

            const syncRemoveButtons = (card) => {
                const rows = Array.from(card.querySelectorAll('[data-hours-period]'));
                rows.forEach((row, index) => {
                    row.querySelector('.remove-hours-period')?.remove();
                    if (index === 0 || rows.length <= 1) return;

                    const button = document.createElement('button');
                    button.className = 'mini-button remove-hours-period';
                    button.type = 'button';
                    button.textContent = 'Remove';
                    row.appendChild(button);
                });
            };

            const syncClosedState = (card) => {
                const closed = card.querySelector('.operating-correction-closed')?.checked ?? false;
                card.classList.toggle('is-closed', closed);
                syncRemoveButtons(card);
                card.querySelectorAll('[data-hours-period] input').forEach((input) => {
                    input.disabled = closed || operatingHoursComponent.hidden;
                });
                card.querySelectorAll('.remove-hours-period, .add-hours-period').forEach((button) => {
                    button.disabled = closed || operatingHoursComponent.hidden;
                });
            };

            const syncAllClosedStates = () => {
                document.querySelectorAll('[data-hours-day]').forEach((card) => syncClosedState(card));
            };

            const addPeriodRow = (card) => {
                const periods = card.querySelector('[data-hours-periods]');
                if (!periods) return;

                const row = document.createElement('div');
                row.className = 'hours-period-row';
                row.dataset.hoursPeriod = '';
                row.innerHTML = `
                    <label>
                        <span>Open</span>
                        <input type="time" value="">
                    </label>
                    <label>
                        <span>Close</span>
                        <input type="time" value="">
                    </label>
                `;
                periods.appendChild(row);
                reindexDay(card);
                syncClosedState(card);
            };

            document.querySelectorAll('[data-hours-day]').forEach((card) => {
                card.querySelector('.operating-correction-closed')?.addEventListener('change', () => syncClosedState(card));
                card.querySelector('.add-hours-period')?.addEventListener('click', () => addPeriodRow(card));
                card.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('.remove-hours-period');
                    if (!removeButton) return;

                    const rows = card.querySelectorAll('[data-hours-period]');
                    if (rows.length <= 1) return;

                    removeButton.closest('[data-hours-period]')?.remove();
                    reindexDay(card);
                    syncClosedState(card);
                });
                syncClosedState(card);
            });

            fieldSelect.addEventListener('change', updateFieldContext);
            updateFieldContext();
        })();
    </script>
@endpush
