@extends('layouts.user')

@section('title', __('Edit Profile'))
@section('user-topbar-title', __('Edit Profile'))
@section('user-topbar-subtitle', __('Update your contact details, language, and profile photo.'))

@section('user-topbar-actions')
<a class="user-topbar-link" href="{{ route('profile.show') }}">{{ __('View Profile') }}</a>
<a class="user-topbar-link" href="{{ route('home') }}">{{ __('Back to Home') }}</a>
@endsection

@push('head-scripts')
@vite(['resources/js/profile.js'])
@endpush

@push('styles')
<style>
        :root {
            color-scheme: light;
            --wm-bg: #fbf2e7;
            --wm-panel: #fff8f0;
            --wm-ink: #5b4335;
            --wm-muted: #8c6f5f;
            --wm-border: rgba(177, 140, 106, .16);
            --wm-accent: #b34d35;
            --wm-gold: #d19c3b;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(180deg, #fbf2e7 0%, #f5e4d5 100%);
            color: var(--wm-ink);
        }

        a { color: inherit; text-decoration: none; }
        button, input, textarea, label { font: inherit; }

        .page {
            max-width: 820px;
            margin: 0 auto;
            padding: 24px 22px 42px;
        }

        .topbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            padding: 24px 24px;
            margin-bottom: 20px;
            border-radius: 20px;
            background: linear-gradient(180deg, #fff7f0 0%, #fdf0e3 100%);
            border: 1px solid rgba(177, 140, 106, .2);
            box-shadow: 0 18px 36px rgba(104, 71, 42, .08);
        }

        .section-heading {
            margin: 0;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1.85rem;
            line-height: 1.05;
            color: #7d4634;
        }

        .section-copy {
            margin: 10px 0 0;
            color: var(--wm-muted);
            max-width: 620px;
            line-height: 1.75;
        }

        .topbar-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .button,
        .button-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 999px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            font-weight: 700;
            white-space: nowrap;
        }

        .button {
            background: var(--wm-accent);
            color: #fff;
        }

        .button-secondary {
            background: transparent;
            color: var(--wm-ink);
            border-color: var(--wm-border);
        }

        .status-alert {
            margin-bottom: 18px;
            padding: 16px 18px;
            border-radius: 14px;
            background: rgba(22, 163, 74, .10);
            color: #166534;
            border: 1px solid rgba(22, 163, 74, .25);
        }

        .status-alert strong {
            display: block;
            margin-bottom: 6px;
        }

        .status-alert ul {
            margin: 0;
            padding-left: 20px;
        }

        /* ---- Form card ---- */

        .form-card {
            border-radius: 28px;
            background: var(--wm-panel);
            border: 1px solid var(--wm-border);
            box-shadow: 0 14px 32px rgba(113, 80, 53, .08);
            overflow: hidden;
        }

        .photo-row {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 28px 28px 24px;
            border-bottom: 1px solid var(--wm-border);
        }

        .photo-preview {
            flex: 0 0 auto;
            width: 68px;
            height: 68px;
            border-radius: 18px;
            overflow: hidden;
            background: linear-gradient(160deg, #f3e3d5, #e6d0bb);
            border: 1px solid var(--wm-border);
            box-shadow: 0 6px 14px rgba(113, 80, 53, .1);
            display: grid;
            place-items: center;
            color: #a14d39;
            font-weight: 800;
            font-size: 1.4rem;
        }

        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .photo-controls {
            display: grid;
            gap: 6px;
            min-width: 0;
        }

        .photo-controls label {
            font-size: .9rem;
            font-weight: 700;
        }

        .photo-controls input[type="file"] {
            font-size: .88rem;
            color: var(--wm-ink);
        }

        .photo-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .camera-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 118px;
            min-height: 38px;
            padding: 0 14px;
            border: 1px solid var(--wm-border);
            border-radius: 999px;
            background: #fff;
            color: var(--wm-ink);
            cursor: pointer;
            font-weight: 700;
            font-size: .9rem;
            line-height: 1;
        }

        .choose-file-button {
            text-transform: none !important;
        }

        .camera-button:hover,
        .camera-button:focus-visible {
            border-color: var(--wm-accent);
            outline: none;
        }

        .camera-modal[hidden] { display: none; }

        .camera-modal {
            position: fixed;
            inset: 0;
            z-index: 10;
            display: grid;
            place-items: center;
            padding: 20px;
            background: rgba(58, 35, 24, .62);
        }

        .camera-dialog {
            width: min(100%, 560px);
            padding: 22px;
            border-radius: 22px;
            background: var(--wm-panel);
            box-shadow: 0 22px 60px rgba(40, 22, 12, .25);
        }

        .camera-dialog h2 { margin: 0 0 14px; font-size: 1.2rem; }

        #cameraVideo {
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            border-radius: 14px;
            background: #2d241f;
            object-fit: cover;
        }

        .camera-dialog-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 16px;
        }

        .photo-controls small {
            color: var(--wm-muted);
            font-size: .82rem;
        }

        .photo-controls select {
            min-height: 42px;
            padding: 8px 12px;
            border: 1px solid var(--wm-border);
            border-radius: 12px;
            background: #fff;
            color: var(--wm-ink);
        }

        .form-fields {
            padding: 22px 28px 8px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        .field.span-2 {
            grid-column: 1 / -1;
        }

        .field label {
            font-size: .9rem;
            font-weight: 700;
        }

        .field input,
        .field textarea {
            width: 100%;
            min-height: 46px;
            padding: 12px 14px;
            border: 1px solid var(--wm-border);
            border-radius: 14px;
            background: #fff;
            color: var(--wm-ink);
        }

        .field input:focus,
        .field textarea:focus {
            outline: 2px solid var(--wm-accent);
            outline-offset: 1px;
        }

        .field textarea {
            min-height: 110px;
            resize: vertical;
        }

        .field small {
            color: var(--wm-muted);
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 24px 28px 28px;
        }

        @media (max-width: 640px) {
            .form-fields { grid-template-columns: 1fr; }
            .photo-row { flex-wrap: wrap; }
        }

        /* ---- Profile completion ---- */

.completion-card {
    margin-bottom: 20px;
    padding: 20px 22px;
    border-radius: 20px;
    background: var(--wm-panel);
    border: 1px solid var(--wm-border);
    box-shadow: 0 10px 24px rgba(113, 80, 53, .06);
}

.completion-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.completion-header h2 {
    margin: 0;
    color: #7d4634;
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1.2rem;
}

.completion-percentage {
    color: var(--wm-accent);
    font-size: 1.2rem;
}

.completion-progress {
    width: 100%;
    height: 10px;
    margin-top: 14px;
    overflow: hidden;
    border-radius: 999px;
    background: #eadccd;
}

.completion-progress span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, var(--wm-gold), var(--wm-accent));
    transition: width .3s ease;
}

    </style>
@endpush

@section('content')
<div class="page">
        <header class="topbar">
            <div>
                <h1 class="section-heading">{{ __('Edit your profile') }}</h1>
                <p class="section-copy">{{ __('Upload a profile photo, keep your contact details current, and let WarisanMakan remember your preferences.') }}</p>
            </div>
            <div class="topbar-actions">
                <a class="button-secondary" href="{{ route('profile.show') }}">{{ __('View Profile') }}</a>
                <a class="button" href="{{ route('home') }}">{{ __('Dashboard') }}</a>
            </div>
        </header>

        @if ($errors->any())
            <div class="status-alert">
                <strong>{{ __('There were some issues with your submission.') }}</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
    $completionItems = [
        $user->name,
        $user->phone,
        $user->city,
        $user->bio,
        $user->profile_photo,
    ];

    $completedItems = collect($completionItems)
        ->filter(fn ($value) => filled($value))
        ->count();

    $profileCompletion = (int) round(
        ($completedItems / count($completionItems)) * 100
    );
@endphp

<section class="completion-card" aria-labelledby="profile-completion-title">
    <div class="completion-header">
        <h2 id="profile-completion-title">
            {{ __('Profile completion') }}
        </h2>

        <strong class="completion-percentage">
            {{ $profileCompletion }}%
        </strong>
    </div>

    <div
        class="completion-progress"
        role="progressbar"
        aria-valuenow="{{ $profileCompletion }}"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label="{{ __('Profile completion percentage') }}"
    >
        <span style="width: {{ $profileCompletion }}%"></span>
    </div>
</section>


        <form class="form-card" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf

            <div class="photo-row">
                <div class="photo-preview" id="photoPreview">
                    @if ($user->profile_photo)
                        <img id="photoPreviewImg" src="{{ $user->profilePhotoUrl() }}" alt="Current profile photo">
                    @else
                        <img id="photoPreviewImg" src="" alt="Selected profile photo" style="display:none;">
                        <span id="photoPreviewFallback">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="photo-controls">
                    <label for="profile_photo">{{ __('Profile photo') }}</label>
                    <div class="photo-actions">
                        <input id="profile_photo" name="profile_photo" type="file" accept="image/*" hidden>
                        <label class="camera-button choose-file-button" for="profile_photo" tabindex="0">{{ __('Choose File') }}</label>
                        <button class="camera-button" id="openCamera" type="button">{{ __('Use Camera') }}</button>
                    </div>
                </div>
            </div>

            <div class="form-fields">
                <div class="field span-2">
                    <label for="name">{{ __('Name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="field span-2">
                    <label for="email">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required readonly>
                </div>

                <div class="field">
                    <label for="phone">{{ __('Phone') }}</label>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            value="{{ old('phone', $user->phone) }}"
                            inputmode="numeric"
                            pattern="[0-9]{1,11}"
                            maxlength="11"
                            title="{{ __('Phone number must contain only digits and be no more than 11 digits.') }}"
                            autocomplete="tel"
                        >
                        @error('phone')
                        <small class="field-error">{{ $message}}</small>
                        @enderror
                </div>

                <div class="field">
                    <label for="city">{{ __('City') }}</label>
                    <input id="city" name="city" type="text" value="{{ old('city', $user->city) }}">
                </div>

                <div class="field span-2">
                    <label for="bio">{{ __('Bio') }}</label>
                    <textarea id="bio" name="bio">{{ old('bio', $user->bio) }}</textarea>
                </div>
            </div>

            <div class="form-actions">
                <button class="button" type="submit">{{ __('Save changes') }}</button>
                <a class="button-secondary" href="{{ route('profile.show') }}">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>

    <div class="camera-modal" id="cameraModal" role="dialog" aria-modal="true" aria-labelledby="cameraTitle" hidden>
        <div class="camera-dialog">
            <h2 id="cameraTitle">{{ __('Take a profile photo') }}</h2>
            <video id="cameraVideo" autoplay playsinline></video>
            <p id="cameraMessage" class="section-copy" role="status"></p>
            <div class="camera-dialog-actions">
                <button class="button-secondary" id="closeCamera" type="button">{{ __('Cancel') }}</button>
                <button class="button" id="capturePhoto" type="button">{{ __('Take photo') }}</button>
            </div>
        </div>
    </div>

    <script>
        window.profileTranslations = {
            selected: @json(__(':name selected.', ['name' => ':name'])),
            notImage: @json(__('That file is not an image. Choose an image file.')),
            requestingCamera: @json(__('Requesting camera access...')),
            unsupportedCamera: @json(__('Camera access is not supported by this browser.')),
            unavailableCamera: @json(__('Camera access was unavailable. Check your browser permission and try again.'))
        };
    </script>
@endsection
