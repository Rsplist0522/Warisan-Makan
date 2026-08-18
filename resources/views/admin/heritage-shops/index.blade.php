@extends('admin.layout')

@section('title', 'Heritage Shops')
@section('page-title', 'Heritage Shops')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Registry</p>
            <h1>Heritage Shop records</h1>
            <p>Review and manage approved heritage food businesses and their public profiles.</p>
        </div>
        <div class="actions">
            <a class="button primary small" href="{{ route('admin.heritage-shops.create') }}">Add shop</a>
        </div>
    </header>

    @if (session('success'))
        <div class="status-banner success">{{ session('success') }}</div>
    @endif

    <section class="panel">
        @if ($shops->isEmpty())
            <div class="empty-state">
                <h2>No heritage shops yet</h2>
                <p>Create the first approved record for the Heritage Shop registry.</p>
                <a class="button primary" href="{{ route('admin.heritage-shops.create') }}">Create shop</a>
            </div>
        @else
            <div class="record-list">
                @foreach ($shops as $shop)
                    <article class="record-card">
                        <div>
                            <strong style="display:block; font-size:1.05rem; margin-bottom:6px;">{{ $shop->shop_name }}</strong>
                            <p>{{ $shop->address ?: 'No address provided' }}{{ $shop->city ? ', '.$shop->city : '' }}</p>
                            <div class="record-meta">
                                <span>{{ $shop->primary_food_category ?: 'Uncategorized' }}</span>
                                <span>{{ $shop->publish_status ?: 'draft' }}</span>
                                <span>{{ optional($shop->created_at)->format('d M Y') ?: 'Unknown date' }}</span>
                            </div>
                        </div>
                        <div class="record-actions">
                            <a class="button secondary small" href="{{ route('heritage-shops.show', $shop) }}" target="_blank" rel="noopener noreferrer">View</a>
                            <a class="button primary small" href="{{ route('admin.heritage-shops.edit', $shop) }}">Edit</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
