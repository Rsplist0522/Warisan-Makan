@extends('admin.layout')

@section('title', $suggestion->exists ? 'Edit Food Trail' : 'Add Food Trail')
@section('page-title', 'Food Trails')

@section('content')
    <div class="page-header"><div><p class="eyebrow">User recommendations</p><h1>{{ $suggestion->exists ? 'Edit suggestion' : 'Add suggestion' }}</h1><p>Users will see published suggestions in Food Trails.</p></div></div>
    @if ($errors->any())<div class="status-banner error"><strong>Please correct the highlighted details.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form class="panel" method="POST" action="{{ $suggestion->exists ? route('admin.food-trails.update', $suggestion) : route('admin.food-trails.store') }}">
        @csrf @if($suggestion->exists) @method('PUT') @endif
        <div class="field"><label for="title">Trail title</label><input id="title" name="title" value="{{ old('title', $suggestion->title) }}" required></div>
        <div class="field" style="margin-top:16px"><label for="subtitle">Short description</label><input id="subtitle" name="subtitle" value="{{ old('subtitle', $suggestion->subtitle) }}"></div>
        <div class="field" style="margin-top:16px"><label for="summary">Recommendation details</label><textarea id="summary" name="summary" required>{{ old('summary', $suggestion->summary) }}</textarea></div>
        <div class="filters" style="margin-top:16px"><div class="field"><label for="location">Location</label><select id="location" name="location">@foreach($locations as $location)<option value="{{ $location }}" @selected(old('location', $suggestion->location) === $location)>{{ $location }}</option>@endforeach</select></div><div class="field"><label for="category">Category</label><select id="category" name="category">@foreach($categories as $category)<option value="{{ $category }}" @selected(old('category', $suggestion->category ?: 'All') === $category)>{{ $category }}</option>@endforeach</select></div></div>
        <div class="field" style="margin-top:16px"><label><input type="hidden" name="is_published" value="0"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $suggestion->exists ? $suggestion->is_published : true))> Show this suggestion to users</label></div>
        <div class="actions"><button class="button primary">{{ $suggestion->exists ? 'Save changes' : 'Add suggestion' }}</button><a class="button secondary" href="{{ route('admin.food-trails.index') }}">Cancel</a></div>
    </form>
@endsection
