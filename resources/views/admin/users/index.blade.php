@extends('admin.layout')

@section('title', 'Users & Roles')
@section('page-title', 'Users & Roles')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Administrator function</p>
            <h1>User management</h1>
            <p>Review active member accounts, monitor status, and activate or deactivate access as needed.</p>
        </div>
        <div class="actions">
            <a class="button secondary" href="{{ route('admin.dashboard') }}">Back to dashboard</a>
        </div>
    </header>

    <form class="filters four" method="GET" action="{{ route('admin.users.index') }}">
        <div class="field">
            <label for="search">Search user</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Name, username, email, phone, city">
        </div>
        <div class="field">
            <label for="status">Account status</label>
            <select id="status" name="status">
                <option value="">All users</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="actions filter-action">
            <button class="button secondary" type="submit">Filter</button>
            <a class="button secondary" href="{{ route('admin.users.index') }}">Clear</a>
        </div>
    </form>

    @if (session('status'))
        <div class="status-banner success" role="status">{{ session('status') }}</div>
    @endif

    @if ($users->isEmpty())
        <section class="panel empty-state">
            <h2>No user accounts found</h2>
            <p>There are no matching member accounts in the system.</p>
        </section>
    @else
        <div class="record-list">
            @foreach ($users as $user)
                <article class="record-card">
                    <div style="display:flex;align-items:flex-start;gap:16px;">
                        <div style="flex-shrink:0;width:72px;height:72px;border-radius:18px;border:1px solid rgba(46,36,32,.12);background:#f4ecdf;display:grid;place-items:center;overflow:hidden;">
                            @if ($user->profile_photo)
                                <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }} profile photo" style="width:100%;height:100%;object-fit:cover;display:block;">
                            @else
                                <span style="font-size:1.2rem;font-weight:800;color:#a33636;">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <div style="min-width:0;flex:1;">
                            <span class="badge {{ $user->status === 'inactive' ? 'badge-rejected' : 'badge-approved' }}">
                                {{ $user->status === 'inactive' ? 'Inactive' : 'Active' }}
                            </span>
                            <h2>{{ $user->name }}</h2>
                            <p>{{ $user->bio ?: 'No profile bio added yet.' }}</p>
                            <div class="record-meta">
                                <span>Email: {{ $user->email }}</span>
                                <span>Phone: {{ $user->phone ?: 'Not provided' }}</span>
                                <span>City: {{ $user->city ?: 'Not provided' }}</span>
                                <span>Joined: {{ optional($user->created_at)->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="record-actions">
                        <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}">
                            @csrf
                            <button class="button {{ $user->status === 'inactive' ? 'primary' : 'danger' }} small" type="submit">
                                {{ $user->status === 'inactive' ? 'Activate account' : 'Deactivate account' }}
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="pagination">{{ $users->links() }}</div>
    @endif
@endsection
