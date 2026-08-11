@extends('community-contributions.layout')

@section('title', $contribution ? 'Edit Contribution' : 'Submit Heritage Shop')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">User function 1</p>
            <h1>{{ $contribution ? 'Edit Heritage Shop Contribution' : 'Submit Heritage Shop' }}</h1>
            <p>Preserve a local food legacy by sharing verified shop, family, and heritage information.</p>
        </div>
        @if ($contribution)
            <span class="badge badge-{{ $contribution->status }}">{{ $contribution->statusLabel() }}</span>
        @endif
    </header>

    @if ($contribution?->admin_feedback)
        <section class="status-banner error">
            <strong>Administrator feedback</strong><br>
            {{ $contribution->admin_feedback }}
        </section>
    @endif

    <section class="panel">
        @include('community-contributions.partials.form', ['contribution' => $contribution])
    </section>
@endsection
