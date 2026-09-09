@php($branding = \App\Models\SiteBranding::current())

@if ($branding?->hasLogo())
    <img class="{{ $imageClass }}" src="{{ route('brand.logo') }}" alt="WarisanMakan">
@else
    <span class="{{ $placeholderClass }}" aria-label="WarisanMakan"></span>
@endif
