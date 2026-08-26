@extends('admin.layout')

@section('title', 'Edit Blind Box Shop')
@section('page-title', 'Edit Blind Box Shop')

@push('styles')
    <style>
        .back-nav { margin-bottom: 22px; }
        .back-btn { 
            display: inline-flex; 
            align-items: center; 
            padding: 8px 16px 8px 12px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 10px;
            color: var(--ink); 
            text-decoration: none; 
            font-size: 0.82rem; 
            font-weight: 800; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        .back-btn:hover { 
            background: var(--canvas);
            border-color: var(--accent);
            color: var(--accent);
            transform: translateX(-4px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.08);
        }
        .back-btn svg { margin-right: 8px; transition: transform 0.2s ease; }
        .back-btn:hover svg { transform: translateX(-2px); }

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
    @if (!$shop)
        <div class="empty-state panel">
            <h2>Shop not found</h2>
            <p>This shop is no longer in the Blind Box catalog.</p>
            <a class="button secondary" href="{{ route('admin.blind-box-items.index') }}">Back to Blind Box items</a>
        </div>
    @else
        <div class="back-nav">
            <a href="{{ route('admin.blind-box-items.index') }}" class="back-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Back to Shop List
            </a>
        </div>

        <div class="page-header">
            <div>
                <p class="eyebrow">Blind Box shop settings</p>
                <h1>{{ $shop['name'] }}</h1>
                <p>Review the source record and control how it participates in recommendations.</p>
            </div>
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
                    <div class="actions">
                        <button class="button primary" type="submit">Save changes</button>
                        <a class="button secondary" href="{{ route('admin.blind-box-items.index') }}">Cancel</a>
                    </div>
                </form>
            </section>

            <aside class="panel source-preview">
                <img src="{{ $shop['image'] }}" alt="{{ $shop['name'] }}">
                <div><h2>{{ $shop['name'] }}</h2><p>{{ $shop['description'] }}</p><p><strong>{{ $shop['state'] }}</strong> · {{ $shop['year'] }}@if($shop['address'])  
{{ $shop['address'] }}@endif</p></div>
                <span class="badge {{ $shop['active'] ? 'badge-approved' : 'badge-draft' }}">{{ $shop['active'] ? 'Active' : 'Inside but removed' }}</span>
            </aside>
        </div>
    @endif

    <script>
        // Auto-hide status banners after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const banners = document.querySelectorAll('.status-banner');
            banners.forEach(banner => {
                setTimeout(() => {
                    banner.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    banner.style.opacity = '0';
                    banner.style.transform = 'translateY(-10px)';
                    setTimeout(() => banner.remove(), 500);
                }, 3000);
            });
        });
    </script>
@endsection
