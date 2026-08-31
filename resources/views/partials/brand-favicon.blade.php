@php($branding = \App\Models\SiteBranding::current())

@if ($branding?->hasLogo())
    <link rel="icon" type="{{ $branding->logo_mime_type }}" href="{{ route('brand.logo', ['v' => $branding->updated_at?->timestamp]) }}">
@else
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
@endif
