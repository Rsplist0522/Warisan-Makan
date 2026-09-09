@props(['url'])
@php
    $branding = \App\Models\SiteBranding::current();
    $logoUrl = $branding?->hasLogo() ? route('brand.logo') : null;
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($logoUrl)
<img src="{{ $logoUrl }}" class="logo" alt="WarisanMakan" style="width: 160px; height: auto; max-width: 160px;">
@else
<span class="brand-name">WarisanMakan</span>
@endif
</a>
</td>
</tr>
