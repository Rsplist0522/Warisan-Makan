@extends('admin.layout')

@section('title', $module['name'])
@section('page-title', $module['name'])

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Module placeholder</p>
            <h1>{{ $module['name'] }}</h1>
            <p>{{ $module['description'] }}</p>
        </div>
        <a class="button secondary" href="{{ route('admin.dashboard') }}">Back to dashboard</a>
    </header>

    <section class="panel empty-state">
        <h2>Coming soon</h2>
        <p>This module is visible in the admin navigation so the platform structure is ready, but its workflow has not been built yet.</p>
    </section>
@endsection
