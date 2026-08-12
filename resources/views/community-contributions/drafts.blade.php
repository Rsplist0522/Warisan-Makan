@extends('community-contributions.layout')

@section('title', 'Manage Drafts')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">User function 2</p>
            <h1>Manage Drafts</h1>
            <p>Edit, submit, or permanently delete your unfinished heritage shop contributions.</p>
        </div>
        <a class="button primary" href="{{ route('community-contribution.create') }}">+ New contribution</a>
    </header>

    @if ($drafts->isEmpty())
        <section class="panel empty-state">
            <h2>No drafts found</h2>
            <p>Save an unfinished heritage shop form and it will appear here.</p>
            <a class="button primary" href="{{ route('community-contribution.create') }}">Start a contribution</a>
        </section>
    @else
        <div class="record-list">
            @foreach ($drafts as $draft)
                <article class="record-card">
                    <div>
                        <span class="badge badge-draft">Draft</span>
                        <h2>{{ $draft->contribution_title ?: ($draft->shop_name ?: 'Untitled contribution') }}</h2>
                        <div class="record-meta">
                            <span>Shop: {{ $draft->shop_name ?: 'Not provided' }}</span>
                            <span>Last updated: {{ $draft->updated_at->format('d M Y, g:i A') }}</span>
                        </div>
                    </div>
                    <div class="record-actions">
                        <a class="button secondary small" href="{{ route('community-contribution.edit', $draft) }}">Edit</a>
                        <form method="POST" action="{{ route('community-contribution.drafts.submit', $draft) }}">
                            @csrf
                            <button class="button primary small" type="submit">Submit</button>
                        </form>
                        <form method="POST" action="{{ route('community-contribution.drafts.destroy', $draft) }}" onsubmit="return confirm('Delete this draft and its uploaded media permanently?')">
                            @csrf
                            @method('DELETE')
                            <button class="button danger small" type="submit">Delete</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="pagination">{{ $drafts->links() }}</div>
    @endif
@endsection
