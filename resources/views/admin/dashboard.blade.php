@extends('admin.layout')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
    <style>
        .welcome { padding: 30px; border-radius: 14px; color: #fffaf4; background: linear-gradient(125deg, #8f2929, #5e1717); box-shadow: 0 20px 50px rgba(91, 29, 29, .18); }
        .welcome .eyebrow { margin: 0 0 8px; color: #e4bd72; font-size: .72rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
        .welcome h2 { margin: 0; font-family: Georgia, serif; font-size: clamp(1.8rem, 4vw, 2.7rem); }
        .welcome p { max-width: 700px; margin: 11px 0 0; color: rgba(255,250,244,.72); line-height: 1.6; }
        .module-grid { margin-top: 22px; }
        .module-index { width: 34px; height: 34px; display: grid; place-items: center; margin-bottom: 20px; border-radius: 10px; color: var(--accent); background: rgba(163,54,54,.1); font-size: .76rem; font-weight: 900; }
        .module-card.inactive .module-index { color: #8a7a70; background: rgba(66, 43, 32, .08); }
    </style>
@endpush

@section('content')
    <section class="welcome">
        <p class="eyebrow">Administrator portal</p>
        <h2>Welcome, {{ auth()->user()->name }}.</h2>
        <p>Choose a platform module to manage. Community Contribution is active now; the remaining areas are mapped out for future development.</p>
    </section>

    <section class="module-grid" aria-label="Admin modules">
        <a class="module-card" href="{{ route('admin.community-contributions.submissions') }}">
            <span class="module-index">01</span>
            <h3>Community Contribution</h3>
            <p>Review heritage eatery submissions, move items through moderation, and inspect the admin activity history.</p>
            <strong>Open module</strong>
        </a>
        <a class="module-card" href="{{ route('admin.blind-box-items.index') }}">
            <span class="module-index">02</span>
            <h3>Blind Box</h3>
            <p>Manage the dummy recommendation pool, categories, and shops available for user reveals.</p>
            <strong>Open module</strong>
        </a>
        <a class="module-card inactive" href="{{ route('admin.modules.show', 'heritage-registry') }}">
            <span class="module-index">03</span>
            <h3>Heritage Registry</h3>
            <p>Approved shop records, ownership notes, provenance, and publication controls.</p>
            <strong>Coming soon</strong>
        </a>
        <a class="module-card inactive" href="{{ route('admin.modules.show', 'food-map') }}">
            <span class="module-index">04</span>
            <h3>Food Map</h3>
            <p>Map-based discovery tools for heritage eateries, cuisine clusters, and local trails.</p>
            <strong>Coming soon</strong>
        </a>
        <a class="module-card inactive" href="{{ route('admin.modules.show', 'stories-editorial') }}">
            <span class="module-index">05</span>
            <h3>Stories & Editorial</h3>
            <p>Editorial planning for oral histories, guides, interviews, and featured shop narratives.</p>
            <strong>Coming soon</strong>
        </a>
        <a class="module-card inactive" href="{{ route('admin.modules.show', 'events-trails') }}">
            <span class="module-index">06</span>
            <h3>Events & Trails</h3>
            <p>Curated walking routes, food trail campaigns, and community makan events.</p>
            <strong>Coming soon</strong>
        </a>
        <a class="module-card inactive" href="{{ route('admin.modules.show', 'reports-analytics') }}">
            <span class="module-index">07</span>
            <h3>Reports & Analytics</h3>
            <p>Contribution trends, moderation throughput, geographic coverage, and content gaps.</p>
            <strong>Coming soon</strong>
        </a>
    </section>
@endsection
