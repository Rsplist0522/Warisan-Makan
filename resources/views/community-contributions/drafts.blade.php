@extends('community-contributions.layout')

@section('title', __('Manage Drafts'))

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">{{ __('Saved drafts') }}</p>
            <h1>{{ __('Manage Drafts') }}</h1>
            <p>{{ __('Edit, submit, or permanently delete your unfinished heritage shop contributions.') }}</p>
        </div>

        <a class="button primary" href="{{ route('community-contribution.create') }}">
            + {{ __('New contribution') }}
        </a>
    </header>

    @if ($drafts->isEmpty())
        <section class="panel empty-state">
            <h2>{{ __('No drafts found') }}</h2>
            <p>{{ __('Save an unfinished heritage shop form and it will appear here.') }}</p>
            <a class="button primary" href="{{ route('community-contribution.create') }}">
                {{ __('Start a contribution') }}
            </a>
        </section>
    @else
        <div class="record-list">
            @foreach ($drafts as $draft)
                <article class="record-card">
                    <div>
                        <span class="badge badge-draft">{{ __('Draft') }}</span>

                        <h2>
                            {{ $draft->contribution_title
                                ?: ($draft->shop_name ?: __('Untitled contribution')) }}
                        </h2>

                        <div class="record-meta">
                            <span>
                                {{ __('Shop') }}:
                                {{ $draft->shop_name ?: __('Not provided') }}
                            </span>
                            <span>
                                {{ __('Last updated') }}:
                                {{ $draft->formatDateTime($draft->updated_at) }}
                            </span>
                        </div>
                    </div>

                    <div class="record-actions">
                        <a
                            class="button secondary small"
                            href="{{ route('community-contribution.edit', $draft) }}"
                        >
                            {{ __('Edit') }}
                        </a>

                        <form
                            method="POST"
                            action="{{ route('community-contribution.drafts.submit', $draft) }}"
                        >
                            @csrf
                            <button class="button primary small" type="submit">
                                {{ __('Submit') }}
                            </button>
                        </form>

                        <form
                            method="POST"
                            action="{{ route('community-contribution.drafts.destroy', $draft) }}"
                            onsubmit="return confirm(@json(__('Delete this draft and its uploaded media permanently?')))"
                        >
                            @csrf
                            @method('DELETE')
                            <button class="button danger small" type="submit">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pagination">
            {{ $drafts->links() }}
        </div>
    @endif
@endsection
