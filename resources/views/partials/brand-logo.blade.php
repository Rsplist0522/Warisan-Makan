@php($branding = \App\Models\SiteBranding::current())

@if ($branding?->hasLogo())
    <img class="{{ $imageClass }}" src="{{ route('brand.logo') }}" alt="Warisan Makan logo">
@else
    <span class="{{ $placeholderClass }}" aria-label="Logo placeholder"></span>
@endif
