@extends('layouts.user')

@section('user-topbar-title', __('Community Contribution'))
@section('user-topbar-subtitle', __('Preserve heritage shops, stories, corrections and supporting evidence.'))

@section('user-topbar-actions')
    <a class="user-topbar-link" href="{{ route('home') }}">{{ __('Back to Home') }}</a>
    <a class="user-topbar-link" href="{{ route('heritage-shops.index') }}">{{ __('Heritage Shops') }}</a>
@endsection
