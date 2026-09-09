@props([
    'src',
    'alt',
    'imageClass' => '',
    'loading' => 'lazy',
    'fetchpriority' => null,
])

<button
    type="button"
    {{ $attributes->class('enlargeable-image') }}
    data-image-enlarge
    data-image-src="{{ $src }}"
    data-image-alt="{{ $alt }}"
    aria-label="{{ __('Enlarge image: :description', ['description' => $alt]) }}"
>
    <img
        class="{{ $imageClass }}"
        src="{{ $src }}"
        alt="{{ $alt }}"
        @if ($loading) loading="{{ $loading }}" @endif
        @if ($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif
        onerror="this.closest('[data-image-enlarge]').remove()"
    >
    <span class="enlargeable-image__hint" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"/></svg>
    </span>
</button>
