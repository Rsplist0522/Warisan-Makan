@extends('admin.layout')

@section('title', 'Edit Blind Box Shop')
@section('page-title', 'Edit Blind Box Shop')

@push('styles')
    <style>
        .edit-layout { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(240px, .7fr); gap: 18px; align-items: start; }
        .source-preview { display: grid; gap: 14px; }
        .source-preview img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 12px; }
        .source-preview h2 { font-family: Georgia, serif; font-size: 1.55rem; }
        .source-preview p { margin: 7px 0 0; color: var(--muted); line-height: 1.55; }
        .edit-form { display: grid; gap: 16px; }
        .read-only-note { padding: 12px 14px; border: 1px solid rgba(49, 93, 131, .18); border-radius: 10px; background: rgba(49, 93, 131, .07); color: #315d83; font-size: .84rem; line-height: 1.5; }
        .check-row { display: flex; align-items: start; gap: 10px; padding: 13px; border: 1px solid var(--line); border-radius: 10px; background: #fff; }
        .check-row input { margin-top: 4px; }
        .check-row label { display: grid; gap: 3px; }
        .check-row small { color: var(--muted); font-weight: 400; }
        @media (max-width: 800px) { .edit-layout { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    @if (session('status'))
        <div class="status-banner success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="status-banner error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if (!$shop)
        <div class="empty-state panel"><h2>Shop not found</h2><p>This shop is no longer in the Blind Box catalog.</p><a class="button secondary" href="{{ route('admin.blind-box-items.index') }}">Back to Blind Box items</a></div>
    @else
        <div class="page-header">
            <div>
                <p class="eyebrow">Blind Box shop settings</p>
                <h1>{{ $shop['name'] }}</h1>
                <p>Review the source record and control how it participates in recommendations.</p>
            </div>
            <a class="button secondary" href="{{ route('admin.blind-box-items.index') }}">Back to shop list</a>
        </div>

        <div class="edit-layout">
            <section class="panel">
                <h2>Recommendation settings</h2>
                <p class="read-only-note">Shop information is supplied by the source catalog. This page only changes the Blind Box category and whether this shop is included in blind box.</p>
                <form class="edit-form" method="POST" action="{{ route('admin.blind-box-items.update', $shop['id']) }}">
                    @csrf
                    @method('PUT')
                    <div class="field"><label for="category">Blind Box category</label><select id="category" name="category" required>@foreach($categories as $option)<option value="{{ $option }}" @selected($shop['category'] === $option)>{{ $option }}</option>@endforeach</select></div>
                    <div class="check-row">
                        <input id="active" type="checkbox" name="active" value="1" @checked($shop['active'])>
                        <label for="active">Include in Blind Box reveals <small>Uncheck this to keep the source shop visible in the catalog but exclude it from recommendations.</small></label>
                    </div>
                    <div class="actions"><button class="button primary" type="submit">Save changes</button><a class="button secondary" href="{{ route('admin.blind-box-items.index') }}">Cancel</a></div>
                </form>
            </section>

            <aside class="panel source-preview">
                <img src="{{ $shop['image'] }}" alt="{{ $shop['name'] }}">
                <div><h2>{{ $shop['name'] }}</h2><p>{{ $shop['description'] }}</p><p><strong>{{ $shop['state'] }}</strong> · {{ $shop['year'] }}@if($shop['address'])<br>{{ $shop['address'] }}@endif</p></div>
                <span class="badge {{ $shop['active'] ? 'badge-approved' : 'badge-draft' }}">{{ $shop['active'] ? 'Inside and active' : 'Inside but removed' }}</span>
            </aside>
        </div>
    @endif
@endsection
