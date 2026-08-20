<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Blind Box History') }}</title>
    <style>
        :root {
            --red-dark: #691616;
            --cream: #f7efe4;
            --text: #2f241d;
            --muted: #6f5845;
            --card: #ffffff;
        }
        body { font-family: "Segoe UI", Arial, sans-serif; margin: 0; background: linear-gradient(135deg, #fcf7ef 0%, var(--cream) 100%); color: var(--text); }
        .page { max-width: 1020px; margin: 0 auto; padding: 24px 18px 48px; }
        .hero, .section { background: rgba(255,255,255,0.92); border: 1px solid rgba(140,31,31,0.08); border-radius: 24px; box-shadow: 0 14px 36px rgba(69,34,18,0.08); padding: 24px; }
        h1, h2 { font-family: Georgia, "Times New Roman", serif; margin: 0 0 12px; }
        p { color: var(--muted); margin: 0 0 16px; }
        .button-row { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 16px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 12px 18px; border-radius: 999px; font-weight: 700; border: none; cursor: pointer; }
        .btn-secondary { background: #fff; color: var(--red-dark); border: 1px solid rgba(140,31,31,0.18); }
        .history-grid { display: grid; gap: 16px; margin-top: 20px; }
        .history-card { background: var(--card); border-radius: 18px; padding: 18px; border: 1px solid rgba(140,31,31,0.08); box-shadow: 0 10px 24px rgba(69,34,18,0.06); }
        .history-card h3 { margin-top: 0; }
        .history-meta { font-size: 0.95rem; color: var(--muted); margin-bottom: 10px; }
        .history-card img { width: 100%; max-height: 220px; object-fit: cover; border-radius: 14px; margin-bottom: 12px; }
        @media (max-width: 800px) { .history-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="page">
    <section class="hero">
        <h1>{{ __('Blind Box History') }}</h1>
        <p>{{ __('All of your surprise discoveries are recorded here so the heritage trail stays memorable.') }}</p>
        <div class="button-row">
            <a class="btn btn-secondary" href="/blind-box">{{ __('Back to Blind Box') }}</a>
            <a class="btn btn-secondary" href="/heritage-shops">{{ __('Browse Heritage Shops') }}</a>
            <a class="btn btn-secondary" href="/foodtrails">{{ __('Start a Food Trail') }}</a>
        </div>
    </section>

    <section class="section">
        <h2>{{ __('Recent draws') }}</h2>
        @if(empty($drawHistory))
            <p>{{ __('No draws yet. Open the blind box to discover a heritage shop and save that surprise in your browser history.') }}</p>
        @else
            <div class="history-grid">
                @foreach($drawHistory as $draw)
                    <article class="history-card">
                        @if(!empty($draw['image']))
                            <img src="{{ $draw['image'] }}" alt="{{ $draw['shop_name'] }}">
                        @endif
                        <div class="history-meta">{{ ucfirst($draw['period']) }} draw · {{ \Carbon\Carbon::parse($draw['drawn_at'])->format('M j, Y H:i') }}</div>
                        <h3>{{ $draw['shop_name'] }}</h3>
                        <p><strong>Category:</strong> {{ $draw['category'] ?: 'Heritage' }} · <strong>State:</strong> {{ $draw['state'] ?: 'Malaysia' }}</p>
                        <p>{{ $draw['description'] }}</p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
</body>
</html>
