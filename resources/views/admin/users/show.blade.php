@extends('admin.layout')

@section('title', 'User Details')
@section('page-title', 'User Details')

@section('content')
    <style>
        .user-detail-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 24px;
        }

        .user-detail-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .user-profile-card {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 26px;
            margin-bottom: 24px;
            border: 1px solid rgba(46, 36, 32, 0.12);
            border-radius: 18px;
            background: #fffaf3;
            box-shadow: 0 8px 24px rgba(46, 36, 32, 0.06);
            flex-wrap: wrap;
        }

        .user-profile-photo {
            display: grid;
            flex-shrink: 0;
            width: 96px;
            height: 96px;
            overflow: hidden;
            place-items: center;
            border: 1px solid rgba(46, 36, 32, 0.12);
            border-radius: 22px;
            background: #f4ecdf;
        }

        .user-profile-photo img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-profile-initial {
            color: #a33636;
            font-size: 2rem;
            font-weight: 800;
        }

        .user-profile-main {
            flex: 1 1 260px;
            min-width: 0;
        }

        .user-profile-main h2 {
            margin: 0 0 6px;
        }

        .user-profile-main p {
            margin: 0 0 14px;
        }
        .stat-pills {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            width: 100%;
        }

        .stat-pill {
            display: flex;
            flex: 1 1 0;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            min-width: 0;
            padding: 8px 4px;
            border: 1px solid rgba(46, 36, 32, 0.12);
            border-radius: 14px;
            background: #f4ecdf;
            text-align: center;
        }

        .stat-pill .stat-value {
            color: #a33636;
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .stat-pill .stat-label {
            color: #6f6258;
            font-size: 0.68rem;
            line-height: 1.2;
            white-space: normal;
            word-break: normal;
            max-width: 100%;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            align-items: start;
        }

        .detail-panel {
            padding: 22px;
            border: 1px solid rgba(46, 36, 32, 0.12);
            border-radius: 18px;
            background: #fffaf3;
            box-shadow: 0 8px 24px rgba(46, 36, 32, 0.06);
        }

        .detail-panel.full-width {
            grid-column: 1 / -1;
        }

        .detail-panel h2 {
            margin-top: 0;
        }

        .detail-list {
            display: grid;
            gap: 12px;
            margin: 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(46, 36, 32, 0.08);
        }

        .detail-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .detail-row dt {
            color: #6f6258;
            font-weight: 700;
        }

        .detail-row dd {
            margin: 0;
            text-align: right;
        }

        .badge-list,
        .shop-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .badge-item,
        .shop-item {
            padding: 14px;
            border: 1px solid rgba(46, 36, 32, 0.1);
            border-radius: 14px;
            background: #fff;
            transition: box-shadow 0.15s ease, transform 0.15s ease;
        }

        .badge-item:hover,
        .shop-item:hover {
            box-shadow: 0 6px 16px rgba(46, 36, 32, 0.08);
            transform: translateY(-1px);
        }

        .badge-item strong,
        .shop-item strong {
            display: block;
            margin-bottom: 4px;
        }

        .muted {
            color: #6f6258;
        }

        @media (max-width: 720px) {
            .user-detail-header,
            .user-profile-card {
                flex-direction: column;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .detail-panel.full-width {
                grid-column: auto;
            }

            .detail-row {
                flex-direction: column;
                gap: 4px;
            }

            .detail-row dd {
                text-align: left;
            }

            .user-profile-main {
                flex: 1 1 auto;
            }
        }
    </style>

    <header class="user-detail-header">
        <div>
            <p class="eyebrow">Administrator function</p>
            <h1>{{ $user->name }}</h1>
            <p>View member information, badges, and visited heritage shops.</p>
        </div>

        <div class="user-detail-actions">
            <a class="button secondary" href="{{ route('admin.users.index') }}">
                Back to users
            </a>
        </div>
    </header>

    <section class="user-profile-card">
        <div class="user-profile-photo">
            @if ($user->profile_photo)
                <img
                    src="{{ $user->profilePhotoUrl() }}"
                    alt="{{ $user->name }} profile photo"
                >
            @else
                <span class="user-profile-initial">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </span>
            @endif
        </div>

        <div class="user-profile-main">
            <span class="badge {{ $user->status === 'inactive' ? 'badge-rejected' : 'badge-approved' }}">
                {{ $user->status === 'inactive' ? 'Inactive' : 'Active' }}
            </span>
            <h2>{{ $user->name }}</h2>
            <p>{{ $user->bio ?: 'No profile bio added yet.' }}</p>

            <div class="stat-pills">
                <div class="stat-pill">
                    <span class="stat-value">{{ $userBadges->count() }}</span>
                    <span class="stat-label">Badges earned</span>
                </div>

                <div class="stat-pill">
                    <span class="stat-value">{{ $shopVisitCount }}</span>
                    <span class="stat-label">Shops visited</span>
                </div>

                <div class="stat-pill">
                    <span class="stat-value">{{ $stamps->count() }}</span>
                    <span class="stat-label">Visit records</span>
                </div>
            </div>
        </div>
    </section>

    <div class="detail-grid">
        <section class="detail-panel full-width">
            <h2>Basic information</h2>

            <dl class="detail-list">
                <div class="detail-row">
                    <dt>Name</dt>
                    <dd>{{ $user->name }}</dd>
                </div>

                <div class="detail-row">
                    <dt>Username</dt>
                    <dd>{{ $user->username ?: 'Not provided' }}</dd>
                </div>

                <div class="detail-row">
                    <dt>Email</dt>
                    <dd>{{ $user->email }}</dd>
                </div>

                <div class="detail-row">
                    <dt>Phone</dt>
                    <dd>{{ $user->phone ?: 'Not provided' }}</dd>
                </div>

                <div class="detail-row">
                    <dt>City</dt>
                    <dd>{{ $user->city ?: 'Not provided' }}</dd>
                </div>

                <div class="detail-row">
                    <dt>Joined</dt>
                    <dd>{{ optional($user->created_at)->format('d M Y') }}</dd>
                </div>

                <div class="detail-row">
                    <dt>Account status</dt>
                    <dd>{{ $user->status === 'inactive' ? 'Inactive' : 'Active' }}</dd>
                </div>
            </dl>
        </section>

        <section class="detail-panel">
            <h2>Badges</h2>

            @if ($userBadges->isEmpty())
                <p class="muted">This member has not earned any badges yet.</p>
            @else
                <div class="badge-list">
                    @foreach ($userBadges as $userBadge)
                        <article class="badge-item">
                            <strong>{{ $userBadge->badge?->badge_name ?: 'Badge' }}</strong>

                            @if ($userBadge->badge?->description)
                                <span>{{ $userBadge->badge->description }}</span>
                            @endif

                            @if ($userBadge->earned_at)
                                <small class="muted">
                                    Earned {{ $userBadge->earned_at->format('d M Y') }}
                                </small>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="detail-panel">
            <h2>Visited shops</h2>

            @if ($visitedShops->isEmpty())
                <p class="muted">This member has not visited any shops yet.</p>
            @else
                <div class="shop-list">
                    @foreach ($visitedShops as $shop)
                        @php
                            $latestVisit = $stamps->firstWhere('shop_id', $shop->id);
                        @endphp

                        <article class="shop-item">
                            <strong>{{ $shop->shop_name }}</strong>

                            <span class="muted">
                                {{ $shop->city ?: $shop->address ?: 'Location not provided' }}
                            </span>

                            @if ($latestVisit?->stamp_datetime)
                                <small class="muted">
                                    Latest visit: {{ $latestVisit->stamp_datetime->format('d M Y, H:i') }}
                                </small>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection