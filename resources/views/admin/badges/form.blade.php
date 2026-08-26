@extends('admin.layout')

@section('title', $mode === 'create' ? 'Create Badge' : 'Edit Badge')
@section('page-title', $mode === 'create' ? 'Create badge' : 'Edit badge')

@section('content')
    @if (session('badge_error'))
    <div class="status-banner error" role="alert">
        <strong>Badge action could not be completed:</strong>
        <p>{{ session('badge_error') }}</p>
    </div>
    @endif

    <header class="page-header">
        <div>
            <p class="eyebrow">Food Passport</p>
            <h1>{{ $mode === 'create' ? 'Create an achievement badge' : 'Edit achievement badge' }}</h1>
            <p>Badge edits do not change existing user award records or their earned dates.</p>
        </div>
        <a class="button secondary small" href="{{ route('admin.badges.index') }}">Back to badges</a>
    </header>

    <form method="POST" action="{{ $mode === 'create' ? route('admin.badges.store') : route('admin.badges.update', $badge) }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <section class="panel">
            <div class="filters four">
                <div class="field">
                    <label for="badge_name">Badge name</label>
                    <input id="badge_name" name="badge_name" value="{{ old('badge_name', $badge->badge_name) }}" maxlength="100" required>
                </div>

                <div class="field">
                    <label for="icon">Icon</label>
                    <input id="icon" name="icon" value="{{ old('icon', $badge->icon) }}" maxlength="255" placeholder="✦, ★, or an icon name">
                </div>

                <input type="hidden" name="criteria_type" value="visits">

                <div class="field visits-required-field">
                    <label for="criteria_value">Visits required</label>
                    <div class="visits-threshold-control">
                        <input id="criteria_value" name="criteria_value" type="number" min="1" value="{{ old('criteria_value', $badge->criteria_value) }}" required>
                        <small class="threshold-hint">Users will see “Need {{ old('criteria_value', $badge->criteria_value ?: 'X') }} visits” until they reach this threshold.</small>
                    </div>
                </div>

                <div class="field" style="grid-column: 1 / -1;">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required>{{ old('description', $badge->description) }}</textarea>
                </div>

                <label style="display:flex;align-items:center;gap:9px;">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $badge->is_active))>
                    Active and eligible for future awards
                </label>
            </div>

            <div class="actions">
                <button class="button primary" type="submit">{{ $mode === 'create' ? 'Create badge' : 'Save changes' }}</button>
                <a class="button secondary" href="{{ route('admin.badges.index') }}">Cancel</a>
            </div>
        </section>
    </form>
@endsection

@push('styles')
<style>
    .visits-required-field {
        grid-column: span 2;
    }

    .visits-threshold-control {
        display: grid;
        grid-template-columns: minmax(110px, 140px) minmax(180px, 1fr);
        gap: 12px;
        align-items: center;
    }

    .threshold-hint {
        display: block;
        margin: 0;
        color: #7A6A63;
        font-size: 0.78rem;
        line-height: 1.4;
    }

    @media (max-width: 800px) {
        .visits-required-field {
            grid-column: 1 / -1;
        }

        .visits-threshold-control {
            grid-template-columns: minmax(110px, 140px) minmax(0, 1fr);
        }
    }

    @media (max-width: 560px) {
        .visits-threshold-control {
            grid-template-columns: 1fr;
            gap: 6px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    const criteriaValueInput = document.getElementById('criteria_value');
    const criteriaValueHint = criteriaValueInput?.nextElementSibling;

    criteriaValueInput?.addEventListener('input', () => {
        if (criteriaValueHint && criteriaValueInput.value) {
            criteriaValueHint.textContent = `Users will see “Need ${criteriaValueInput.value} visits” until they reach this threshold.`;
        }
    });
</script>
@endpush

