<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Warisan Makan - Community Contribution</title>

    @fonts

    @vite(['resources/js/app.js'])
</head>
<body class="community-contribution-page">
    <main class="shell">
        <section class="card" aria-label="Community contribution submission page">
            <aside class="panel">
                <header class="page-header">
                    <a class="back-link" href="{{ route('home') }}">&larr; Community contribution</a>
                    <h1>Submit Heritage Shop</h1>
                </header>

                @if (session('status'))
                    <div class="status-banner success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="status-banner error">
                        <strong>Please fix the highlighted fields.</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    $hours = old('operating_hours', array_fill(0, count($days), []));
                    $foodItems = old('food_items', [['name' => '', 'desc' => '']]);
                @endphp

                <form class="form-grid" method="POST" action="{{ route('community-contribution.store') }}" enctype="multipart/form-data">
                    @csrf

                    <section class="form-section">
                        <h2 class="section-title">Shop details</h2>
                        <div class="field-grid">
                            <div class="field">
                                <label for="shop_name">Shop name</label>
                                <input id="shop_name" name="shop_name" value="{{ old('shop_name') }}" placeholder="Example: Restoran Al-Hidayah">
                                @error('shop_name')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="primary_food_category">Category</label>
                                <input id="primary_food_category" name="primary_food_category" value="{{ old('primary_food_category') }}" placeholder="Example: Nasi Kandar">
                                @error('primary_food_category')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="address">Location / Address</label>
                                <input id="address" name="address" value="{{ old('address') }}" placeholder="Example: 22, Jalan Ampang, 50450 Kuala Lumpur">
                                @error('address')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="establishment_year">Establishment year</label>
                                <input id="establishment_year" name="establishment_year" type="number" min="1000" max="{{ now()->year }}" value="{{ old('establishment_year') }}" placeholder="Example: 1978">
                                @error('establishment_year')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <h2 class="section-title">Founder & owner information</h2>
                        <div class="field-grid">
                            <div class="field">
                                <label for="founder_name">Founder name</label>
                                <input id="founder_name" name="founder_name" value="{{ old('founder_name') }}" placeholder="Example: Haji Ahmad bin Ali">
                                @error('founder_name')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="current_owner_name">Current owner</label>
                                <input id="current_owner_name" name="current_owner_name" value="{{ old('current_owner_name') }}" placeholder="Example: Siti Aminah binti Ahmad">
                                @error('current_owner_name')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="field full">
                                <label for="founder_background">Founder background</label>
                                <textarea id="founder_background" name="founder_background" placeholder="Share how the shop started and the family background">{{ old('founder_background') }}</textarea>
                                @error('founder_background')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <h2 class="section-title">Heritage story</h2>
                        <div class="field full">
                            <label for="heritage_story">Story</label>
                            <textarea id="heritage_story" name="heritage_story" placeholder="Tell the story that makes this shop heritage-worthy">{{ old('heritage_story') }}</textarea>
                            @error('heritage_story')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </section>

                    <section class="form-section">
                        <h2 class="section-title">Operating hours</h2>
                        <div class="soft-card">
                            <div class="soft-card-head">
                                <div>Day</div><div>Open</div><div>Close</div>
                            </div>
                            @foreach ($days as $index => $day)
                                @php $daySchedule = $hours[$index] ?? []; @endphp
                                <div class="soft-card-row operating-hour-row">
                                    <div class="day-label">{{ $day }}</div>
                                    <div>
                                        <input class="form-input time-input" name="operating_hours[{{ $index }}][open]" type="time" value="{{ $daySchedule['open'] ?? '08:00' }}">
                                        <input type="hidden" name="operating_hours[{{ $index }}][day]" value="{{ $day }}">
                                    </div>
                                    <div class="close-cell">
                                        <input class="form-input time-input" name="operating_hours[{{ $index }}][close]" type="time" value="{{ $daySchedule['close'] ?? '17:00' }}">
                                        <input class="checkbox-input" name="operating_hours[{{ $index }}][closed]" value="1" type="checkbox" aria-label="Mark {{ $day }} as closed" {{ ! empty($daySchedule['closed'] ?? false) ? 'checked' : '' }}>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="form-section">
                        <h2 class="section-title">Heritage food items</h2>
                        <div id="food-items-shell" class="repeat-shell">
                            @foreach ($foodItems as $index => $item)
                                <div class="repeat-row food-item-row">
                                    <div>
                                        @if ($index === 0)
                                            <label>Item name</label>
                                        @endif
                                        <input class="form-input" name="food_items[{{ $index }}][name]" placeholder="e.g. Char Kway Teow" value="{{ $item['name'] ?? '' }}">
                                    </div>
                                    <div>
                                        @if ($index === 0)
                                            <label>Description</label>
                                        @endif
                                        <input class="form-input" name="food_items[{{ $index }}][desc]" placeholder="e.g. Wok-fried flat rice noodles, family recipe since 1952" value="{{ $item['desc'] ?? '' }}">
                                    </div>
                                    <div class="repeat-actions">
                                        @if ($index === 0)
                                            <div class="repeat-spacer"></div>
                                        @endif
                                        @if (count($foodItems) > 1)
                                            <button type="button" class="mini-button remove-food-item">×</button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" id="add-food-item" class="link-button">+ Add food item</button>
                    </section>

                    <section class="form-section">
                        <h2 class="section-title">Supporting media</h2>
                        <div class="field full">
                            <label for="supporting_media">Supporting media</label>
                            <input id="supporting_media" name="supporting_media[]" type="file" accept="image/*,video/*" multiple>
                            <p class="help-text">Upload up to 6 images or videos. Supported formats: JPG, PNG, WEBP, MP4, MOV, AVI.</p>
                            @error('supporting_media')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                            @error('supporting_media.*')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </section>

                    <div class="actions">
                        <button class="button secondary" type="submit" name="submission_action" value="draft">Save draft</button>
                        <button class="button primary" type="submit" name="submission_action" value="submit">Submit for review</button>
                    </div>
                </form>

                <small>
                    Submitted contributions are stored as private records first and become visible to the moderation flow after review.
                </small>
            </aside>
        </section>
    </main>

    <template id="food-item-template">
        <div class="repeat-row food-item-row">
            <div>
                <input class="form-input" name="food_items[__INDEX__][name]" placeholder="e.g. Char Kway Teow">
            </div>
            <div>
                <input class="form-input" name="food_items[__INDEX__][desc]" placeholder="e.g. Wok-fried flat rice noodles, family recipe since 1952">
            </div>
            <div class="repeat-actions">
                <button type="button" class="mini-button remove-food-item">×</button>
            </div>
        </div>
    </template>

    <script>
        (() => {
            const shell = document.getElementById('food-items-shell');
            const addButton = document.getElementById('add-food-item');
            const template = document.getElementById('food-item-template');

            if (!shell || !addButton || !template) {
                return;
            }

            const bindRemove = (row) => {
                const removeButton = row.querySelector('.remove-food-item');
                if (!removeButton) {
                    return;
                }

                removeButton.addEventListener('click', () => {
                    const rows = shell.querySelectorAll('.food-item-row');
                    if (rows.length === 1) {
                        row.querySelectorAll('input').forEach((input) => { input.value = ''; });
                        return;
                    }

                    row.remove();
                    shell.querySelectorAll('.food-item-row').forEach((currentRow, index) => {
                        currentRow.querySelectorAll('input').forEach((input) => {
                            input.name = input.name.replace(/food_items\[\d+\]/, `food_items[${index}]`);
                        });
                    });
                });
            };

            shell.querySelectorAll('.food-item-row').forEach(bindRemove);

            addButton.addEventListener('click', () => {
                const nextIndex = shell.querySelectorAll('.food-item-row').length;
                const wrapper = document.createElement('div');
                wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex)).trim();
                const row = wrapper.firstElementChild;
                shell.appendChild(row);
                bindRemove(row);
            });
        })();
    </script>
</body>
</html>