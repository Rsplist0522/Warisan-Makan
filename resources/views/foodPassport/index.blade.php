@extends('layouts.user')

@section('title', __('Food Passport'))
@section('user-topbar-title', __('Food Passport'))
@section('user-topbar-subtitle', __('Collect stamps, unlock achievements, and compare your progress.'))

@section('user-topbar-actions')
<a class="user-topbar-link" href="{{ route('heritage-shops.index') }}">{{ __('Heritage Shops') }}</a>
<a class="user-topbar-link" href="{{ route('passport.index') }}#leaderboard">{{ __('Leaderboard') }}</a>
@endsection

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
        :root {
            --primary: #8C1F1F;
            --primary-deep: #6D1717;
            --accent: #D4A017;
            --bg: #F7F1E7;
            --panel: #FFFDF9;
            --surface: #F2E5D0;
            --ink: #2F251F;
            --muted: #675B54;
            --line: rgba(86, 59, 48, 0.12);
            --success: #3E6C4F;
            --shadow: rgba(47, 37, 31, 0.08);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Inter, Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(140,31,31,0.06), transparent 25%),
                linear-gradient(180deg, rgba(255,255,255,.25), rgba(255,255,255,0)),
                var(--bg);
            color: var(--ink);
        }

       img { max-width: 100%; display: block; }

        .passport-shell {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 20px 40px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0 20px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-transform: uppercase;
            font-size: 0.82rem;
            color: var(--primary);
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-deep));
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 1.1rem;
            box-shadow: 0 12px 24px rgba(140,31,31,0.18);
        }

        .nav {
            display: flex;
            gap: 16px;
            align-items: center;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .nav a {
            color: var(--ink);
            text-decoration: none;
            font-weight: 600;
        }

        .nav .chip {
            background: rgba(212,160,23,0.12);
            border: 1px solid rgba(212,160,23,0.25);
            color: var(--primary);
            padding: 8px 12px;
            border-radius: 999px;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 22px;
            align-items: stretch;
            background: rgba(255,255,255,0.36);
            border: 1px solid var(--line);
            border-radius: 22px;
            box-shadow: 0 18px 38px var(--shadow);
            overflow: hidden;
            position: relative;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, rgba(140,31,31,0.05), transparent 50%);
            pointer-events: none;
        }

        .hero-copy {
            padding: 32px;
            position: relative;
            z-index: 1;
        }

        .eyebrow {
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 0.72rem;
            font-weight: 800;
            margin: 0 0 12px;
        }

        h1, h2, h3 {
            font-family: Georgia, "Times New Roman", serif;
            margin-top: 0;
            letter-spacing: -0.03em;
        }

        h1 {
            font-size: clamp(2.2rem, 5vw, 4rem);
            line-height: 0.98;
            margin-bottom: 16px;
            color: var(--primary);
        }

        .hero-copy p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            max-width: 620px;
        }

        .action-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 24px;
        }

        .passport-actions {
            margin-top: 26px;
            background: rgba(255,255,255,0.36);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            padding: 12px 18px;
            border: none;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .btn:hover { transform: translateY(-1px); }

        .btn.primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-deep));
            color: #fff;
            box-shadow: 0 16px 24px rgba(140,31,31,0.18);
        }

        .btn.secondary {
            background: rgba(212,160,23,0.09);
            color: var(--ink);
            border: 1px solid rgba(212,160,23,0.24);
        }

        .section-nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-deep));
            color: #fff;
            border-color: transparent;
            box-shadow: 0 16px 24px rgba(140,31,31,0.18);
        }

        .section-nav-link.active:hover {
            opacity: 0.94;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 28px;
        }

        .stat {
            background: rgba(255,255,255,0.52);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px 12px;
            text-align: center;
        }

        .stat strong {
            display: block;
            color: var(--primary);
            font-size: clamp(1.3rem, 2vw, 1.8rem);
            margin-bottom: 4px;
        }

        .stat span {
            color: var(--muted);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .hero-media {
            position: relative;
            min-height: 360px;
            background:
                linear-gradient(135deg, rgba(212,160,23,0.10), rgba(140,31,31,0.04)),
                repeating-linear-gradient(45deg, rgba(140,31,31,0.04), rgba(140,31,31,0.04) 10px, transparent 10px, transparent 20px),
                #f5ebdf;
            border-left: 1px solid var(--line);
        }

        .hero-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: saturate(0.9) contrast(1.04);
        }

        .floating-card {
            position: absolute;
            left: 22px;
            right: 22px;
            bottom: 18px;
            background: rgba(255,253,249,0.88);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(140,31,31,0.08);
            border-radius: 18px;
            padding: 14px 16px;
            box-shadow: 0 18px 28px rgba(47,37,31,0.08);
        }

        .floating-card .label {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 0.66rem;
            margin-bottom: 8px;
            display: block;
        }

        .floating-card h3 {
            font-size: 1.5rem;
            color: var(--ink);
            margin-bottom: 4px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1.25fr 0.75fr;
            gap: 22px;
            margin-top: 26px;
            align-items: stretch;
        }

        .content-grid > .panel,
        .content-grid > .check-in-panel {
            height: 100%;
        }

        .panel {
            background: rgba(255,255,255,0.46);
            border: 1px solid var(--line);
            border-radius: 20px;
            box-shadow: 0 12px 28px var(--shadow);
        }

        .panel-inner {
            padding: 22px 22px 18px;
        }

        .shop-tools {
            display: grid;
            grid-template-columns: minmax(120px, 1fr) auto auto auto;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .shop-search {
            width: 100%;
            min-width: 0;
            min-height: 44px;
            margin: 0;
        }

        .shop-tools .btn {
            min-height: 44px;
            padding: 12px;
            white-space: nowrap;
        }
            .shop-tools .btn.active,
            .shop-tools .btn:active {
                background: linear-gradient(135deg, var(--primary), var(--primary-deep));
                border-color: transparent;
                color: #fff;
                box-shadow: 0 10px 18px rgba(140,31,31,0.18);
            }

        .location-button {
            width: 154px;
            min-width: 154px;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
            white-space: nowrap;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .section-header h2 {
            font-size: clamp(1.5rem, 2vw, 2.1rem);
            margin: 0;
            color: var(--ink);
        }

        .tag {
            background: rgba(140,31,31,0.08);
            border: 1px solid rgba(140,31,31,0.12);
            border-radius: 999px;
            color: var(--primary);
            padding: 7px 11px;
            font-weight: 700;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .shop-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .shop-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: rgba(255,255,255,0.54);
            cursor: pointer;
        }

        .shop-item.active {
            border-color: rgba(140,31,31,0.24);
            box-shadow: inset 0 0 0 1px rgba(140,31,31,0.08);
        }

        .shop-body {
            min-width: 0;
        }

        .shop-body h3 {
            font-size: 1.2rem;
            margin-bottom: 6px;
            color: var(--ink);
        }

        .shop-body p {
            margin: 0;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .shop-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
            font-size: 0.76rem;
            color: var(--muted);
        }

        .shop-meta span {
            background: rgba(212,160,23,0.08);
            border-radius: 999px;
            padding: 6px 8px;
        }

        .mini-action {
            background: rgba(140,31,31,0.04);
            color: var(--primary);
            border: 1px solid rgba(140,31,31,0.13);
            border-radius: 10px;
            padding: 9px 12px;
            font-weight: 700;
            font-size: 0.8rem;
        }

        .check-in-panel {
            padding: 22px;
            min-width: 0;
            min-height: 430px;
            height: 100%;
        }

        .check-in-empty {
            min-height: 310px;
            margin: 16px 0 0;
            color: var(--muted);
        }

        .selected-shop {
            display: flex;
            gap: 16px;
            background: rgba(255,255,255,0.62);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 12px;
            margin: 16px 0 20px;
        }

        .selected-shop img {
            width: 96px;
            height: 96px;
            border-radius: 14px;
            object-fit: cover;
        }

        .selected-shop .label {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 0.64rem;
            margin: 0 0 6px;
        }

        .selected-shop h3 {
            font-size: 1.8rem;
            margin: 0 0 4px;
        }

        .selected-shop p {
            margin: 0;
            color: var(--muted);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        label {
            display: block;
            color: var(--muted);
            font-size: 0.82rem;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            background: #fffdf9;
            border: 1px solid rgba(86,59,48,0.12);
            border-radius: 10px;
            padding: 11px 12px;
            font: inherit;
            color: var(--ink);
        }

        .button-row {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 16px;
            max-width: 100%;
            overflow: hidden;
        }

        .shops-pagination {
            justify-content: center;
        }

        .shops-pagination-mobile {
            display: none;
        }

        .pagination-current {
            display: none;
            color: var(--muted) !important;
            font-size: 0.78rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            color: var(--primary);
            text-decoration: none;
        }

        .pagination .active {
            background: var(--primary);
            color: white;
        }

        .result-box {
            margin-top: 18px;
            background: #f8f2ea;
            border-radius: 12px;
            border: 1px solid rgba(86,59,48,0.1);
            color: var(--ink);
            padding: 14px;
            white-space: pre-wrap;
            word-break: break-word;
            min-height: 120px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .badge-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 12px;
            margin-top: 18px;
        }

        .badge-card {
            background: rgba(255,255,255,0.48);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 16px 14px;
            text-align: center;
        }

        .badge-card-shareable {
            cursor: pointer;
            transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
        }

        .badge-card-shareable:hover {
            transform: translateY(-3px);
            border-color: rgba(212,160,23,0.65);
            box-shadow: 0 12px 24px rgba(86,59,48,0.1);
        }

        .badge-card-shareable:focus-visible {
            outline: 3px solid rgba(212,160,23,0.5);
            outline-offset: 3px;
        }

        .badge-share-hint {
            margin-top: 10px !important;
            color: var(--primary) !important;
            font-size: 0.74rem !important;
            font-weight: 800;
        }

        .badge-crest {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            margin: 0 auto 10px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(212,160,23,0.18), rgba(140,31,31,0.06));
            color: var(--primary);
            font-size: 1.4rem;
            font-weight: 800;
        }

        .badge-card h4 {
            font-size: 1.05rem;
            margin-bottom: 6px;
        }

        .badge-card p {
            margin: 0;
            color: var(--muted);
            font-size: 0.8rem;
        }

        .leaderboard-intro {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .leaderboard-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 18px;
        }

        .leaderboard-row {
            display: grid;
            grid-template-columns: 54px minmax(0, 1fr) auto;
            align-items: center;
            gap: 14px;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(255,255,255,0.54);
        }

        .leaderboard-rank {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(212,160,23,0.14);
            color: var(--primary);
            font-weight: 800;
        }

        .leaderboard-user h3 {
            margin: 0 0 4px;
            font-size: 1.05rem;
            color: var(--ink);
        }

        .leaderboard-user p {
            margin: 0;
            color: var(--muted);
            font-size: 0.78rem;
        }

        .leaderboard-metrics {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .leaderboard-metrics span {
            padding: 7px 9px;
            border-radius: 999px;
            background: rgba(140,31,31,0.07);
            color: var(--primary);
            font-size: 0.76rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .leaderboard-empty {
            margin: 18px 0 0;
            color: var(--muted);
        }

        body.modal-open {
            overflow: hidden;
        }

        .badge-modal[hidden] {
            display: none;
        }

        .badge-modal {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: grid;
            place-items: center;
            padding: 20px;
        }

        .badge-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(47, 37, 31, 0.56);
            backdrop-filter: blur(4px);
        }

        .badge-modal-card {
            position: relative;
            width: min(100%, 560px);
            max-height: min(680px, calc(100vh - 40px));
            overflow: auto;
            padding: 30px 26px 24px;
            border-radius: 24px;
            background: var(--panel);
            border: 1px solid rgba(212,160,23,0.3);
            box-shadow: 0 28px 70px rgba(47,37,31,0.25);
            text-align: center;
        }

        .badge-modal-close {
            position: absolute;
            top: 12px;
            right: 14px;
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 50%;
            background: rgba(140,31,31,0.08);
            color: var(--primary);
            font-size: 1.35rem;
            cursor: pointer;
        }

        .badge-modal-icon {
            display: grid;
            place-items: center;
            width: 76px;
            height: 76px;
            margin: 4px auto 14px;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(212,160,23,0.24), rgba(140,31,31,0.1));
            color: var(--primary);
            font-size: 2rem;
            font-weight: 800;
        }

        .badge-modal-card h2 {
            margin-bottom: 8px;
            color: var(--primary);
            font-size: 2rem;
        }

        .badge-modal-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .achievement-card-preview {
            position: relative;
            overflow: hidden;
            min-height: 280px;
            margin-top: 18px;
            padding: 24px;
            border-radius: 22px;
            background: linear-gradient(135deg, #8C1F1F 0%, #4A211C 58%, #2E1815 100%);
            color: #FBF6EE;
            text-align: left;
            box-shadow: 0 18px 30px rgba(86,59,48,0.18);
        }

        .achievement-card-preview::before,
        .achievement-card-preview::after {
            position: absolute;
            content: '';
            width: 180px;
            height: 180px;
            border: 1px solid rgba(212,160,23,0.35);
            border-radius: 50%;
        }

        .achievement-card-preview::before {
            top: -92px;
            right: -54px;
        }

        .achievement-card-preview::after {
            bottom: -120px;
            left: -64px;
        }

        .achievement-card-kicker,
        .achievement-card-footer {
            position: relative;
            z-index: 1;
            color: rgba(251,246,238,0.72);
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .achievement-card-main {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 86px minmax(0, 1fr);
            gap: 16px;
            align-items: center;
            margin: 30px 0 24px;
        }

        .achievement-card-icon {
            display: grid;
            place-items: center;
            width: 82px;
            height: 82px;
            border: 2px solid rgba(212,160,23,0.75);
            border-radius: 26px;
            background: rgba(251,246,238,0.12);
            color: #F2D37B;
            font-size: 2.4rem;
            font-weight: 800;
        }

        .achievement-card-title {
            margin: 0 0 6px;
            color: #F2D37B;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(1.35rem, 4vw, 2rem);
        }

        .achievement-card-description {
            margin: 0;
            color: rgba(251,246,238,0.9) !important;
            font-size: 0.88rem;
        }

        .achievement-card-progress {
            position: relative;
            z-index: 1;
            display: inline-flex;
            margin-bottom: 18px;
            padding: 7px 11px;
            border: 1px solid rgba(212,160,23,0.38);
            border-radius: 999px;
            color: #F2D37B;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .achievement-card-footer {
            position: relative;
            z-index: 1;
        }

        .share-download-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .share-download-btn {
            padding: 10px 11px;
            border: 1px solid rgba(212,160,23,0.38);
            border-radius: 11px;
            background: rgba(212,160,23,0.1);
            color: var(--primary);
            font: inherit;
            font-size: 0.82rem;
            font-weight: 800;
            cursor: pointer;
        }

        .share-download-btn:hover {
            background: rgba(212,160,23,0.2);
        }

        .badge-modal-badge-name {
            margin: 12px 0 6px !important;
            color: var(--ink) !important;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1.45rem;
            font-weight: 700;
        }

        .share-label {
            margin-top: 22px !important;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .share-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .share-btn {
            padding: 11px 12px;
            border: 1px solid rgba(140,31,31,0.14);
            border-radius: 11px;
            background: rgba(140,31,31,0.05);
            color: var(--primary);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .share-btn:hover {
            background: rgba(212,160,23,0.15);
        }

        .share-status {
            min-height: 24px;
            margin-top: 12px !important;
            font-size: 0.82rem;
        }

        .share-note {
            margin-top: 12px !important;
            color: var(--muted);
            font-size: 0.76rem;
        }

        .badge-modal-continue {
            width: 100%;
            margin-top: 16px;
        }

        footer {
            text-align: center;
            color: var(--muted);
            padding: 28px 0 10px;
            font-size: 0.88rem;
        }

        @media (max-width: 1100px) {
            .hero, .content-grid {
                grid-template-columns: 1fr;
            }

            .hero-media {
                min-height: 260px;
            }
        }

        @media (max-width: 640px) {
            body {
                overflow-x: hidden;
            }

            .passport-shell {
                width: 100%;
                padding: 16px 12px 30px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }

            .nav {
                width: 100%;
                flex-wrap: wrap;
                gap: 10px;
            }

            .hero,
            .content-grid,
            .panel {
                width: 100%;
                min-width: 0;
            }

            .panel-inner,
            .check-in-panel {
                padding: 18px 16px;
            }

            .check-in-panel {
                min-height: 0;
            }

            .check-in-empty {
                min-height: 120px;
            }

            .hero-copy {
                padding: 24px 18px 20px;
            }

            .stats-row, .badge-grid, .form-row {
                grid-template-columns: 1fr;
            }

            .leaderboard-row {
                grid-template-columns: 46px minmax(0, 1fr);
            }

            .leaderboard-metrics {
                grid-column: 2;
                justify-content: flex-start;
            }

            .shop-item {
                grid-template-columns: 1fr;
            }

            .shop-tools {
                grid-template-columns: 1fr;
                align-items: stretch;
            }

            .shop-search,
            .shop-tools .btn {
                width: 100%;
            }

            .location-button {
                width: 100%;
                min-width: 0;
            }

            .mini-action {
                grid-column: 1 / -1;
                justify-self: start;
            }

            .shops-pagination-desktop {
                display: none;
            }

            .shops-pagination-mobile {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
            }

            .shops-pagination-mobile a,
            .shops-pagination-mobile span {
                padding: 9px 11px;
            }

            .shops-pagination-mobile .pagination-page {
                min-width: 38px;
                text-align: center;
            }
        }
    </style>
@endpush

@section('content')
<div class="passport-shell">
<main>
            <section class="hero" aria-label="{{ __('Heritage passport hero section') }}">
                <div class="hero-copy">
                    <p class="eyebrow">{{ __('Food Passport') }}</p>
                    <h1>{{ __('Your Heritage Passport') }}</h1>
                    <p>{{ __('Collect stamps from authentic heritage food stops, uncover founder stories, and unlock rewards as you explore the city’s living culinary heritage.') }}</p>

                    <div class="stats-row" aria-label="{{ __('Passport progress statistics') }}">
                        <div class="stat">
                            <strong>{{ $stats['visited'] ?? 0 }}</strong>
                            <span>{{ __('Visited') }}</span>
                        </div>
                        <div class="stat">
                            <strong>{{ $stats['completion'] ?? 0 }}%</strong>
                            <span>{{ __('Progress') }}</span>
                        </div>
                        <div class="stat">
                            <strong>{{ $stats['badges'] ?? 0 }}</strong>
                            <span>{{ __('Badges') }}</span>
                        </div>
                    </div>
                </div>

                @if (!empty($shops))
                   <div class="hero-media" aria-label="{{ __('Featured heritage shop image') }}">
                        <img id="featuredShopImage" src="{{ $shops[0]['image'] ?? '' }}" alt="{{ $shops[0]['name'] }}" style="{{ empty($shops[0]['image']) ? 'display:none;' : '' }}">
                        <div class="floating-card">
                            <span class="label">{{ __('Featured stop') }}</span>
                            <h3 id="featuredShopName">{{ $shops[0]['name'] }}</h3>
                            <p id="featuredShopFounder">{{ $shops[0]['founder'] }}</p>
                        </div>
                    </div>
                @endif
            </section>

            <section class="panel passport-actions" aria-label="{{ __('Passport sections') }}">
                <div class="panel-inner">
                    <div class="action-row" style="margin-top: 0;">
                        <a href="{{ route('passport.history') }}" class="btn secondary">{{ __('Visit History') }}</a>
                        <a href="{{ route('passport.statistics') }}" class="btn secondary">{{ __('Passport Statistics & Achievements') }}</a>
                        <a href="{{ route('passport.leaderboard') }}" class="btn secondary">{{ __('Leaderboard') }}</a>
                    </div>
                </div>
            </section>

            <section class="content-grid" id="nearby">
                <div class="panel">
                    <div class="panel-inner">
                        <div class="section-header">
                            <h2>{{ __('Nearby heritage stop') }}</h2>
                            <span class="tag">{{ __('Live') }}</span>
                        </div>

                        <form class="shop-tools" method="GET" action="{{ route('passport.index') }}#nearby">
                            <input class="shop-search" type="search" name="shop_search" value="{{ request('shop_search') }}" placeholder="{{ __('Search shops or founders') }}" aria-label="{{ __('Search shops or founders') }}">
                            @if (request('user_latitude') && request('user_longitude'))
                                <input type="hidden" name="user_latitude" value="{{ request('user_latitude') }}">
                                <input type="hidden" name="user_longitude" value="{{ request('user_longitude') }}">
                            @endif
                            <button type="submit" class="btn secondary {{ request('shop_search') ? 'active' : '' }}">{{ __('Search') }}</button>
                            @if (request('shop_search'))
                                <a href="{{ route('passport.index') }}#nearby" class="btn secondary">{{ __('Show all shops') }}</a>
                            @endif
                            <button type="button" id="btnUseLocation" class="btn secondary location-button {{ request('user_latitude') && request('user_longitude') && !request('shop_search') ? 'active' : '' }}">{{ __('Use My Location') }}</button>
                        </form>

                        @if (!empty($shops))
                            <div class="shop-list" id="shopList">
                                @foreach ($shops as $shop)
                                    <article class="shop-item {{ $loop->first ? 'active' : '' }}" data-id="{{ $shop['id'] }}" data-name="{{ $shop['name'] }}" data-founder="{{ $shop['founder'] }}" data-lat="{{ $shop['lat'] }}" data-lng="{{ $shop['lng'] }}" data-image="{{ $shop['image'] }}">
                                        <div class="shop-body">
                                            <h3>{{ $shop['name'] }}</h3>
                                            <p>{{ $shop['founder'] }}</p>
                                            <div class="shop-meta">
                                                <span>{{ $shop['distance'] }}</span>
                                                <span>{{ $shop['status'] }}</span>
                                            </div>
                                        </div>
                                        <button type="button" class="mini-action select-shop">{{ __('Select') }}</button>
                                    </article>
                                @endforeach
                            </div>

                            @if ($availableShops->total() > 0)
                                <nav class="pagination shops-pagination shops-pagination-desktop" aria-label="{{ __('Available shops pages') }}">
                                    @if ($availableShops->lastPage() > 1)
                                        @if ($availableShops->onFirstPage())
                                            <span class="pagination-previous" aria-disabled="true">{{ __('Previous') }}</span>
                                        @else
                                            <a class="pagination-previous" href="{{ $availableShops->previousPageUrl() }}#nearby">{{ __('Previous') }}</a>
                                        @endif

                                        <span class="pagination-current" aria-current="page">
                                            {{ __('Page :current of :last', ['current' => $availableShops->currentPage(), 'last' => $availableShops->lastPage()]) }}
                                        </span>

                                        @php
                                            $currentPage = $availableShops->currentPage();
                                            $lastPage = $availableShops->lastPage();
                                            $paginationPages = collect([1, 2, $currentPage - 1, $currentPage, $currentPage + 1, $lastPage - 1, $lastPage])
                                                ->filter(fn ($page) => $page >= 1 && $page <= $lastPage)
                                                ->unique()
                                                ->sort()
                                                ->values();
                                            $previousPaginationPage = null;
                                        @endphp
                                        @foreach ($paginationPages as $page)
                                            @if ($previousPaginationPage !== null && $page > $previousPaginationPage + 1)
                                                <span class="pagination-ellipsis" aria-hidden="true">...</span>
                                            @endif
                                            @if ($page === $currentPage)
                                                <span class="pagination-page active" aria-current="page">{{ $page }}</span>
                                            @else
                                                <a class="pagination-page" href="{{ $availableShops->url($page) }}#nearby"
                                                    aria-label="{{ __('Available shops page :page', ['page' => $page]) }}">
                                                    {{ $page }}
                                                </a>
                                            @endif
                                            @php $previousPaginationPage = $page; @endphp
                                        @endforeach

                                        @if ($availableShops->hasMorePages())
                                            <a class="pagination-next" href="{{ $availableShops->nextPageUrl() }}#nearby">{{ __('Next') }}</a>
                                        @else
                                            <span class="pagination-next" aria-disabled="true">{{ __('Next') }}</span>
                                        @endif
                                    @else
                                        <span class="pagination-current" aria-current="page">
                                            {{ __('Page :current of :last', ['current' => $availableShops->currentPage(), 'last' => $availableShops->lastPage()]) }}
                                        </span>
                                    @endif
                                </nav>

                                <nav class="pagination shops-pagination-mobile" aria-label="{{ __('Available shops pages') }}">
                                    @if ($availableShops->onFirstPage())
                                        <span aria-disabled="true">{{ __('Previous') }}</span>
                                    @else
                                        <a href="{{ $availableShops->previousPageUrl() }}#nearby">{{ __('Previous') }}</a>
                                    @endif

                                    @php
                                        $currentPage = $availableShops->currentPage();
                                        $lastPage = $availableShops->lastPage();
                                        $mobilePaginationPages = collect([1, $currentPage - 1, $currentPage, $currentPage + 1, $lastPage])
                                            ->filter(fn ($page) => $page >= 1 && $page <= $lastPage)
                                            ->unique()
                                            ->sort()
                                            ->values();
                                        $previousMobilePage = null;
                                    @endphp
                                    @foreach ($mobilePaginationPages as $page)
                                        @if ($previousMobilePage !== null && $page > $previousMobilePage + 1)
                                            <span class="pagination-ellipsis" aria-hidden="true">...</span>
                                        @endif
                                        @if ($page === $currentPage)
                                            <span class="pagination-page active" aria-current="page">{{ $page }}</span>
                                        @else
                                            <a class="pagination-page" href="{{ $availableShops->url($page) }}#nearby"
                                                aria-label="{{ __('Available shops page :page', ['page' => $page]) }}">{{ $page }}</a>
                                        @endif
                                        @php $previousMobilePage = $page; @endphp
                                    @endforeach

                                    @if ($availableShops->hasMorePages())
                                        <a href="{{ $availableShops->nextPageUrl() }}#nearby">{{ __('Next') }}</a>
                                    @else
                                        <span aria-disabled="true">{{ __('Next') }}</span>
                                    @endif
                                </nav>
                            @endif
                        @elseif (request('shop_search'))
                            <div class="shop-list" id="shopList" hidden></div>
                            <p id="shopEmptyMessage" style="margin: 0; color: var(--muted);">
                                {{ __('No heritage shops match your search. Try another name or founder.') }}
                            </p>
                        @else
                            <div class="shop-list" id="shopList" hidden></div>
                            <p id="shopEmptyMessage" style="margin: 0; color: var(--muted);">
                                {{ __('No approved heritage shops with GPS coordinates are available for check-in yet. Add the shop location and approve it in the Heritage Shop module.') }}
                            </p>
                        @endif
                    </div>
                </div>

                <aside class="panel check-in-panel" id="check-in">
                    <div class="section-header">
                        <h2>{{ __('Check In') }}</h2>
                        <span class="tag">{{ __('GPS') }}</span>
                    </div>

                    @if (!empty($shops))
                        <div class="selected-shop">
                            <div>
                                <p class="label">{{ __('Selected stop') }}</p>
                                <h3 id="selectedShopName">{{ $shops[0]['name'] }}</h3>
                                <p id="selectedShopFounder">{{ $shops[0]['founder'] }}</p>
                            </div>
                        </div>

                        <div class="button-row">
                            <button type="button" id="btnCheckIn" class="btn primary">{{ __('Check In') }}</button>
                        </div>

                        <pre id="result" class="result-box">{{ __('Ready to check in. Select a shop and allow location access.') }}</pre>
                    @else
                        <p class="check-in-empty">
                            @if (request('shop_search'))
                                {{ __('Select an available shop from the list to check in. Clear your search or try another shop name.') }}
                            @else
                                {{ __('Only available heritage shops with approved locations can be checked in to.') }}
                            @endif
                        </p>
                    @endif
                </aside>
            </section>

        </main>

        <footer>
            {{ __('Discover heritage flavour. Preserve the stories behind every bowl.') }}
        </footer>
    </div>

    <div id="badgeModal" class="badge-modal" hidden role="dialog" aria-modal="true" aria-labelledby="badgeModalTitle">
        <div class="badge-modal-backdrop" data-close-badge-modal></div>
        <div class="badge-modal-card">
            <button type="button" class="badge-modal-close" data-close-badge-modal aria-label="{{ __('Close badge announcement') }}">&times;</button>
            <h2 id="badgeModalTitle">{{ __('Achievement unlocked') }}</h2>
            <p>{{ __('Save this moment and share your heritage-food journey.') }}</p>
            <div id="achievementCardPreview" class="achievement-card-preview">
                <div class="achievement-card-kicker">{{ __('Warisan Makan · Heritage Passport') }}</div>
                <div class="achievement-card-main">
                    <div id="badgeModalIcon" class="achievement-card-icon">★</div>
                    <div>
                        <h3 id="badgeModalBadgeName" class="achievement-card-title">{{ __('Your new badge') }}</h3>
                        <p id="badgeModalBadgeDescription" class="achievement-card-description">{{ __('The badge you unlock will appear here.') }}</p>
                    </div>
                </div>
                <div id="badgeModalProgress" class="achievement-card-progress">{{ __('Your milestone will appear here') }}</div>
                <div class="achievement-card-footer">{{ __('Every dish has a story. Discover yours.') }}</div>
            </div>
            <p class="share-label">{{ __('Share your achievement') }}</p>
            <div class="share-actions">
                <button type="button" class="share-btn" data-share="instagram">{{ __('Prepare Instagram Story') }}</button>
                <button type="button" class="share-btn" data-share="facebook">{{ __('Prepare Facebook Post') }}</button>
                <button type="button" class="share-btn" data-share="whatsapp">{{ __('Share to WhatsApp') }}</button>
                <button type="button" class="share-btn" data-share="copy">{{ __('Copy caption') }}</button>
            </div>
            <div class="share-download-actions">
                <button type="button" class="share-download-btn" data-share="download-square">{{ __('Download square card') }}</button>
                <button type="button" class="share-download-btn" data-share="download-story">{{ __('Download story card') }}</button>
            </div>
            <p id="shareStatus" class="share-status" aria-live="polite"></p>
            <p class="share-note">{{ __('Instagram and Facebook may ask you to log in and upload the downloaded card. WhatsApp can attach the card automatically on supported devices.') }}</p>
            <button type="button" class="btn primary badge-modal-continue" data-close-badge-modal>{{ __('Continue exploring') }}</button>
        </div>
    </div>

    <script>
        const sendingCheckInRequestMessage = @json(__('Sending check-in request...'));

        const readyToCheckInMessage = @json(__('Ready to check in. Select a shop and allow location access.'));
        const unexpectedErrorMessage = @json(__('We could not complete the request. Please try again.'));
        const signInBeforeCheckInMessage = @json(__('Please sign in before checking in to your heritage passport.'));
        const sessionExpiredMessage = @json(__('Your session expired. Refresh the page and sign in again before checking in.'));
        const checkInServiceUnavailableMessage = @json(__('The check-in service is not available. Please verify the Passport routes in web.php.'));
        const serverCheckInErrorMessage = @json(__('The server could not complete your check-in. Please check the Laravel error log.'));
        const unexpectedResponseMessage = @json(__('The server returned an unexpected response. Please try again.'));
        const networkServerErrorMessage = @json(__('Network or server error: :message'));
        const selectHeritageShopMessage = @json(__('Please select a heritage shop first.'));
        const geolocationUnsupportedMessage = @json(__('Geolocation is not supported by this browser.'));
        const requestingLocationMessage = @json(__('Requesting location for your heritage check-in...'));
        const failedLocationMessage = @json(__('Failed to get location: :message'));
        const sortingNearbyMessage = @json(__('Locating...'));
        const locationReadyMessage = @json(__('Shops are now sorted by distance from your location.'));

        let shops = @json($shops);
        let activeShop = shops[0] || null;

        function setActiveShop(shop) {
            activeShop = shop;

            const selectedShopName = document.getElementById('selectedShopName');
            const selectedShopFounder = document.getElementById('selectedShopFounder');
            if (selectedShopName) selectedShopName.textContent = shop.name;
            if (selectedShopFounder) selectedShopFounder.textContent = shop.founder;

            const featuredShopName = document.getElementById('featuredShopName');
            const featuredShopFounder = document.getElementById('featuredShopFounder');
            const featuredShopImage = document.getElementById('featuredShopImage');

            if (featuredShopName) featuredShopName.textContent = shop.name;
            if (featuredShopFounder) featuredShopFounder.textContent = shop.founder;
            if (featuredShopImage && shop.image) {
                featuredShopImage.src = shop.image;
                featuredShopImage.alt = shop.name;
                featuredShopImage.style.display = '';
            } else if (featuredShopImage) {
                featuredShopImage.style.display = 'none';
            }

            document.querySelectorAll('.shop-item').forEach(item => {
                item.classList.toggle('active', Number(item.dataset.id) === Number(shop.id));
            });
        }

        function ensureCheckInControls(shop) {
            if (document.getElementById('btnCheckIn')) {
                setActiveShop(shop);
                return;
            }

            const panel = document.getElementById('check-in');
            if (!panel) return;

            const controls = document.createElement('div');
            controls.innerHTML = `
                <div class="selected-shop">
                    <div>
                        <p class="label">Selected stop</p>
                        <h3 id="selectedShopName"></h3>
                        <p id="selectedShopFounder"></p>
                    </div>
                </div>
                <div class="button-row">
                    <button type="button" id="btnCheckIn" class="btn primary">Check In</button>
                </div>
                <pre id="result" class="result-box">Ready to check in. Select a shop and allow location access.</pre>
            `;
            panel.appendChild(controls);
            out = document.getElementById('result');
            setActiveShop(shop);
            bindCheckInButton();
        }

        document.querySelectorAll('.select-shop').forEach(button => {
            button.addEventListener('click', () => {
                const article = button.closest('.shop-item');
                const shop = shops.find(item => Number(item.id) === Number(article.dataset.id));
                setActiveShop(shop);
            });
        });

        function renderShopItem(shop) {
            const article = document.createElement('article');
            article.className = 'shop-item';
            article.dataset.id = shop.id;
            article.innerHTML = `
                <div class="shop-body">
                    <h3>${shop.name}</h3>
                    <p>${shop.founder}</p>
                    <div class="shop-meta">
                        <span>${shop.distance}</span>
                        <span>${shop.status}</span>
                    </div>
                </div>
                <button type="button" class="mini-action select-shop">Select</button>
            `;
            article.querySelector('.select-shop').addEventListener('click', () => setActiveShop(shop));
            article.addEventListener('click', event => {
                if (!event.target.closest('.select-shop')) setActiveShop(shop);
            });
            return article;
        }

        function distanceBetween(firstLatitude, firstLongitude, secondLatitude, secondLongitude) {
            const earthRadius = 6371;
            const latitudeDifference = (secondLatitude - firstLatitude) * Math.PI / 180;
            const longitudeDifference = (secondLongitude - firstLongitude) * Math.PI / 180;
            const latitude = firstLatitude * Math.PI / 180;
            const secondLatitudeRadians = secondLatitude * Math.PI / 180;
            const value = Math.sin(latitudeDifference / 2) ** 2
                + Math.sin(longitudeDifference / 2) ** 2 * Math.cos(latitude) * Math.cos(secondLatitudeRadians);

            return earthRadius * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
        }

        function activateShopTool(button) {
            document.querySelectorAll('.shop-tools .btn').forEach(tool => {
                tool.classList.toggle('active', tool === button);
            });
        }

        document.querySelector('.shop-tools')?.addEventListener('submit', function (event) {
            activateShopTool(event.submitter || this.querySelector('button[type="submit"]'));
        });

        document.querySelectorAll('.shop-tools a.btn').forEach(button => {
            button.addEventListener('click', () => activateShopTool(button));
        });

        document.getElementById('btnUseLocation')?.addEventListener('click', function () {
            if (!navigator.geolocation) {
                setResult(geolocationUnsupportedMessage);
                return;
            }

            const button = this;
            activateShopTool(button);
            button.disabled = true;
            button.textContent = sortingNearbyMessage;

            navigator.geolocation.getCurrentPosition(function (position) {
                const userLatitude = position.coords.latitude;
                const userLongitude = position.coords.longitude;
                const locationUrl = new URL(window.location.href);
                locationUrl.searchParams.delete('shop_search');
                locationUrl.searchParams.set('user_latitude', userLatitude.toString());
                locationUrl.searchParams.set('user_longitude', userLongitude.toString());
                locationUrl.searchParams.set('shops_page', '1');
                locationUrl.hash = 'nearby';
                window.location.assign(locationUrl.toString());
            }, function (error) {
                button.disabled = false;
                button.textContent = @json(__('Use My Location'));
                setResult(failedLocationMessage.replace(':message', error.message || error.code));
            }, { enableHighAccuracy: true, timeout: 10000 });
        });

        const sectionNavLinks = Array.from(document.querySelectorAll('[data-section-nav]'));
        const sectionNavTargets = sectionNavLinks
            .map(link => document.getElementById(link.dataset.sectionNav))
            .filter(Boolean);

        function setActiveSection(sectionId) {
            sectionNavLinks.forEach(link => {
                const isActive = link.dataset.sectionNav === sectionId;
                link.classList.toggle('active', isActive);
                if (isActive) {
                    link.setAttribute('aria-current', 'location');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        }

        sectionNavLinks.forEach(link => {
            link.addEventListener('click', () => setActiveSection(link.dataset.sectionNav));
        });

        const initialSection = window.location.hash.replace('#', '');
        if (sectionNavTargets.some(section => section.id === initialSection)) {
            setActiveSection(initialSection);
        }

        let out = document.getElementById('result');
        const badgeModal = document.getElementById('badgeModal');
        const badgeModalIcon = document.getElementById('badgeModalIcon');
        const badgeModalBadgeName = document.getElementById('badgeModalBadgeName');
        const badgeModalBadgeDescription = document.getElementById('badgeModalBadgeDescription');
        const badgeModalProgress = document.getElementById('badgeModalProgress');
        const shareStatus = document.getElementById('shareStatus');
        let activeBadgeForShare = null;
        let badgeShareText = '';
        let reloadAfterBadgeModal = false;

        function setResult(value) {
            if (!out) {
                const emptyMessage = document.getElementById('shopEmptyMessage');
                if (emptyMessage) emptyMessage.textContent = typeof value === 'string' ? value : value.message || unexpectedErrorMessage;
                return;
            }

            if (typeof value === 'string') {
                out.textContent = value;
                return;
            }

            out.textContent = value.message || unexpectedErrorMessage;
        }

        function openBadgeModal(unlockedBadges, refreshOnClose = false) {
            const validBadges = (Array.isArray(unlockedBadges) ? unlockedBadges : [])
                .filter(badge => badge && badge.name);

            if (!validBadges.length) {
                return;
            }

            const orderedBadges = [...validBadges].sort((first, second) =>
                Number(second.threshold || 0) - Number(first.threshold || 0)
            );
            const primaryBadge = orderedBadges[0];
            const badgeNames = orderedBadges.map(badge => badge.name).join(', ');
            const additionalBadges = orderedBadges.length > 1
                ? ' ' + @json(__('Also unlocked: :badges.', ['badges' => '__BADGES__'])).replace('__BADGES__', orderedBadges.slice(1).map(badge => badge.name).join(', '))
                : '';
            const milestone = primaryBadge.threshold || primaryBadge.progress || 'new';

            activeBadgeForShare = primaryBadge;
            badgeModalIcon.textContent = primaryBadge.icon || '★';
            badgeModalBadgeName.textContent = primaryBadge.name;
            badgeModalBadgeDescription.textContent = (primaryBadge.description || @json(__('Keep exploring local food heritage.'))) + additionalBadges;
            badgeModalProgress.textContent = primaryBadge.threshold || primaryBadge.progress
                ? @json(__('Milestone reached: :count heritage visits')).replace(':count', milestone)
                : @json(__('A new story added to my food journey'));
            badgeShareText = @json(__('I just earned the :badges badge on the Warisan Makan Heritage Passport after discovering :count heritage food stories. What should I explore next?'))
                .replace(':badges', badgeNames)
                .replace(':count', milestone);
            shareStatus.textContent = '';
            badgeModal.hidden = false;
            document.body.classList.add('modal-open');
            reloadAfterBadgeModal = refreshOnClose;
        }

        function closeBadgeModal() {
            badgeModal.hidden = true;
            document.body.classList.remove('modal-open');

            if (reloadAfterBadgeModal) {
                reloadAfterBadgeModal = false;
                window.setTimeout(() => window.location.reload(), 250);
            }
        }

        async function copyShareText() {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(badgeShareText);
                return true;
            }

            const helper = document.createElement('textarea');
            helper.value = badgeShareText;
            helper.setAttribute('readonly', '');
            helper.style.position = 'fixed';
            helper.style.opacity = '0';
            document.body.appendChild(helper);
            helper.select();
            const copied = document.execCommand('copy');
            helper.remove();
            return copied;
        }

        function wrapCanvasText(context, text, x, y, maxWidth, lineHeight, maxLines = 3) {
            const words = String(text || '').split(' ');
            let line = '';
            let lineCount = 0;

            words.forEach((word, index) => {
                const testLine = line + (line ? ' ' : '') + word;
                if (context.measureText(testLine).width > maxWidth && line) {
                    context.fillText(line, x, y + lineCount * lineHeight);
                    line = word;
                    lineCount += 1;
                } else {
                    line = testLine;
                }

                if (index === words.length - 1 && lineCount < maxLines) {
                    context.fillText(line, x, y + lineCount * lineHeight);
                }
            });

            return lineCount + 1;
        }

        function drawAchievementCard(format) {
            if (!activeBadgeForShare) {
                return null;
            }

            const width = 1080;
            const height = format === 'story' ? 1920 : 1080;
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const context = canvas.getContext('2d');
            const badge = activeBadgeForShare;
            const story = format === 'story';

            context.fillStyle = '#2E1815';
            context.fillRect(0, 0, width, height);
            const gradient = context.createLinearGradient(0, 0, width, height);
            gradient.addColorStop(0, '#8C1F1F');
            gradient.addColorStop(0.58, '#4A211C');
            gradient.addColorStop(1, '#2E1815');
            context.fillStyle = gradient;
            context.fillRect(28, 28, width - 56, height - 56);

            context.strokeStyle = 'rgba(242,211,123,0.38)';
            context.lineWidth = 3;
            context.beginPath();
            context.arc(width - 80, 92, 180, 0, Math.PI * 2);
            context.stroke();
            context.beginPath();
            context.arc(56, height - 80, 180, 0, Math.PI * 2);
            context.stroke();

            context.fillStyle = 'rgba(251,246,238,0.78)';
            context.font = '800 26px Arial, sans-serif';
            context.letterSpacing = '4px';
            context.fillText('WARISAN MAKAN  ·  HERITAGE PASSPORT', 78, story ? 118 : 100);

            const centerY = story ? 720 : 490;
            context.fillStyle = 'rgba(251,246,238,0.12)';
            context.fillRect(78, centerY - 230, width - 156, story ? 520 : 440);
            context.strokeStyle = 'rgba(242,211,123,0.75)';
            context.lineWidth = 5;
            context.strokeRect(78, centerY - 230, width - 156, story ? 520 : 440);

            context.fillStyle = '#F2D37B';
            context.font = story ? '800 116px Arial, sans-serif' : '800 100px Arial, sans-serif';
            context.textAlign = 'center';
            context.fillText(badge.icon || '★', width / 2, centerY - 54);
            context.font = story ? '700 58px Georgia, serif' : '700 52px Georgia, serif';
            wrapCanvasText(context, badge.name, width / 2, centerY + 52, width - 240, 70, 2);
            context.fillStyle = 'rgba(251,246,238,0.92)';
            context.font = story ? '32px Arial, sans-serif' : '28px Arial, sans-serif';
            wrapCanvasText(context, badge.description || @json(__('A new heritage-food milestone.')), width / 2, centerY + 150, width - 260, 42, 3);

            context.textAlign = 'center';
            context.fillStyle = '#F2D37B';
            context.font = story ? '800 30px Arial, sans-serif' : '800 26px Arial, sans-serif';
            context.fillText(badge.progress ? 'MILESTONE  ' + badge.progress + '  VISITS' : 'A NEW STORY ADDED TO MY FOOD JOURNEY', width / 2, centerY + (story ? 260 : 238));

            context.fillStyle = 'rgba(251,246,238,0.82)';
            context.font = story ? '30px Arial, sans-serif' : '26px Arial, sans-serif';
            context.fillText('Every dish has a story. Discover yours.', width / 2, height - (story ? 150 : 98));
            context.fillStyle = 'rgba(251,246,238,0.58)';
            context.font = '22px Arial, sans-serif';
            context.fillText('warisan makan  ·  explore local heritage food', width / 2, height - (story ? 100 : 58));
            context.textAlign = 'start';

            return canvas;
        }

        async function downloadAchievementCard(format) {
            const isTouchDevice = 'ontouchstart' in window || (window.navigator.maxTouchPoints || 0) > 0;
            if (isTouchDevice && await shareAchievementFile(format)) {
                return true;
            }

            const canvas = drawAchievementCard(format);
            if (!canvas) {
                return false;
            }

            const slug = (activeBadgeForShare.name || 'badge').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            if (!blob) {
                return false;
            }

            const objectUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.download = 'warisan-makan-' + slug + '-' + format + '.png';
            link.href = objectUrl;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
            shareStatus.textContent = format === 'story'
                ? @json(__('Story card downloaded. Upload it to Instagram Story or WhatsApp Status.'))
                : @json(__('Square card downloaded. It is ready for your social feed.'));
            return true;
        }

        async function shareAchievementFile(format) {
            const canvas = drawAchievementCard(format);
            if (!canvas || !navigator.share || !navigator.canShare || typeof File === 'undefined') {
                return false;
            }

            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            if (!blob) {
                return false;
            }

            const slug = (activeBadgeForShare.name || 'badge').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
            const file = new File([blob], 'warisan-makan-' + slug + '-' + format + '.png', { type: 'image/png' });
            const shareData = {
                files: [file],
                title: activeBadgeForShare.name + ' · Warisan Makan',
                text: badgeShareText
            };

            if (!navigator.canShare({ files: [file] })) {
                return false;
            }

            await navigator.share(shareData);
            shareStatus.textContent = @json(__('Achievement card shared successfully.'));
            return true;
        }

        async function shareBadge(channel) {
            try {
                if (channel === 'copy') {
                    await copyShareText();
                    shareStatus.textContent = @json(__('Caption copied. Add it with your achievement card.'));
                    return;
                }

                if (channel === 'download-square') {
                    await downloadAchievementCard('square');
                    return;
                }

                if (channel === 'download-story') {
                    await downloadAchievementCard('story');
                    return;
                }

                if (channel === 'instagram') {
                    if (await shareAchievementFile('story')) {
                        return;
                    }
                    await copyShareText();
                    await downloadAchievementCard('story');
                    window.open('https://www.instagram.com/', '_blank', 'noopener');
                    shareStatus.textContent = @json(__('Instagram opened. The story card was downloaded and the caption was copied—upload the PNG after logging in.'));
                    return;
                }

                if (channel === 'facebook') {
                    if (await shareAchievementFile('square')) {
                        return;
                    }
                    await copyShareText();
                    await downloadAchievementCard('square');
                    const url = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href) + '&quote=' + encodeURIComponent(badgeShareText);
                    window.open(url, '_blank', 'noopener');
                    shareStatus.textContent = @json(__('Facebook opened. The square card was downloaded and the caption was copied—attach the PNG if needed.'));
                    return;
                }

                if (channel === 'whatsapp') {
                    shareStatus.textContent = @json(__('Preparing your WhatsApp achievement card...'));

                    const isTouchDevice = 'ontouchstart' in window || (window.navigator.maxTouchPoints || 0) > 0;
                    if (isTouchDevice) {
                        const shared = await shareAchievementFile('square');
                        if (shared) return;
                    }

                    const whatsappUrl = isTouchDevice
                        ? 'https://wa.me/?text=' + encodeURIComponent(badgeShareText)
                        : 'https://web.whatsapp.com/send?text=' + encodeURIComponent(badgeShareText);
                    await downloadAchievementCard('square');
                    const whatsappWindow = window.open(whatsappUrl, '_blank', 'noopener');
                    await copyShareText();
                    if (!whatsappWindow) {
                        shareStatus.textContent = @json(__('The card was downloaded and the caption was copied, but the browser blocked WhatsApp. Allow pop-ups or open WhatsApp Web manually.'));
                    } else {
                        shareStatus.textContent = isTouchDevice
                            ? @json(__('WhatsApp opened. Attach the downloaded card if your device did not include it automatically.'))
                            : @json(__('WhatsApp Web opened. The square card was downloaded and the caption was copied—attach the PNG in the chat.'));
                    }
                }
            } catch (error) {
                if (error && error.name === 'AbortError') {
                    shareStatus.textContent = @json(__('Sharing cancelled.'));
                    return;
                }
                shareStatus.textContent = @json(__('We could not prepare the share card. Please use the download buttons instead.'));
            }
        }

        document.querySelectorAll('[data-close-badge-modal]').forEach(element => {
            element.addEventListener('click', closeBadgeModal);
        });

        document.querySelectorAll('[data-share]').forEach(button => {
            button.addEventListener('click', () => shareBadge(button.dataset.share));
        });

        document.querySelectorAll('[data-badge-share]').forEach(badgeCard => {
            const shareSelectedBadge = () => {
                openBadgeModal([{
                    name: badgeCard.dataset.badgeName,
                    description: badgeCard.dataset.badgeDescription,
                    icon: badgeCard.dataset.badgeIcon,
                    progress: badgeCard.dataset.badgeProgress,
                    threshold: Number(badgeCard.dataset.badgeThreshold || 0)
                }], false);
            };

            badgeCard.addEventListener('click', shareSelectedBadge);
            badgeCard.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    shareSelectedBadge();
                }
            });
        });

        async function parseApiResponse(response) {
            const contentType = response.headers.get('content-type') || '';
            const responseText = await response.text();

            if (contentType.includes('application/json')) {
                try {
                    return JSON.parse(responseText);
                } catch (error) {
                    // Fall through to a friendly message for malformed JSON.
                }
            }

            if (response.redirected || response.url.includes('/login')) {
                return {
                    success: false,
                    message: signInBeforeCheckInMessage
                };
            }

            if (response.status === 401 || response.status === 403) {
                return {
                    success: false,
                    message: signInBeforeCheckInMessage
                };
            }

            if (response.status === 419) {
                return {
                    success: false,
                    message: sessionExpiredMessage
                };
            }

            if (response.status === 404 || response.status === 405) {
                return {
                    success: false,
                    message: checkInServiceUnavailableMessage
                };
            }

            if (response.status >= 500) {
                return {
                    success: false,
                    message: serverCheckInErrorMessage
                };
            }

            return {
                success: false,
                message: unexpectedResponseMessage
            };
        }

        async function submitCheckIn(payload) {
            setResult(sendingCheckInRequestMessage);


            const endpoint = '/passport/check-in';

            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            }).then(async response => {
                const json = await parseApiResponse(response);
                setResult(json);

                if (response.ok && json && json.success) {
                    const newlyUnlockedBadges = Array.isArray(json.newly_unlocked_badges)
                        ? json.newly_unlocked_badges
                        : [];

                    if (newlyUnlockedBadges.length > 0) {
                        openBadgeModal(newlyUnlockedBadges, true);
                    } else {
                        window.setTimeout(() => window.location.reload(), 1200);
                    }
                }
            }).catch(error => {
                setResult( networkServerErrorMessage.replace(':message', error.message));
            });
        }

        function bindCheckInButton() {
            document.getElementById('btnCheckIn')?.addEventListener('click', function () {
            if (!activeShop) {
                setResult(selectHeritageShopMessage);
                return;
            }
            if (!navigator.geolocation) {
                setResult(geolocationUnsupportedMessage);
                return;
            }

            setResult(requestingLocationMessage);

            navigator.geolocation.getCurrentPosition(function (position) {
                const payload = {
                    shop_id: Number(activeShop.id),
                    user_latitude: position.coords.latitude,
                    user_longitude: position.coords.longitude
                };

                submitCheckIn(payload);
            }, function (error) {
                setResult(failedLocationMessage.replace(':message', error.message || error.code));
            }, { enableHighAccuracy: true, timeout: 10000 });
            });
        }

        bindCheckInButton();
    </script>
@endsection
