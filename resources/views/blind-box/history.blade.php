<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blind Box History</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root{
            --red:#8c1f1f;--red-dark:#691616;--cream:#f7efe4;--cream-2:#efe0c9;
            --text:#2f241d;--muted:#6f5845;--gold:#c98b16;
            --bg-start:#fcf7ef;--bg-end:#f7efe4;
        }
        body{font-family:"Segoe UI",Arial,sans-serif;margin:0;background:linear-gradient(135deg,var(--bg-start),var(--bg-end));color:var(--text);}
        .page{max-width:1020px;margin:0 auto;padding:24px 18px 48px}
        .hero,.section{background:rgba(255,255,255,.92);border:1px solid rgba(140,31,31,.08);border-radius:24px;box-shadow:0 14px 36px rgba(69,34,18,.08);padding:24px}
        h1,h2{font-family:Georgia,"Times New Roman",serif;margin:0 0 12px}
        p{color:var(--muted);margin:0 0 16px}
        .button-row{display:flex;flex-wrap:wrap;gap:12px;margin-top:16px}
        .btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 18px;border-radius:999px;font-weight:700;border:none;cursor:pointer}
        .btn-secondary{background:#fff;color:var(--red-dark);border:1px solid rgba(140,31,31,.18)}
        .history-grid{display:grid;gap:16px;margin-top:20px}

        .history-card{
            display:grid;grid-template-columns:200px 1fr;gap:18px;align-items:start;
            background:#ffffff;border-radius:18px;padding:18px;
            border:1px solid rgba(140,31,31,.08);box-shadow:0 10px 24px rgba(69,34,18,.06);
        }
        .history-card .media{position:relative;width:200px;height:200px;flex-shrink:0}
        .history-card img{
            width:200px;height:200px;object-fit:cover;border-radius:14px;
            border:3px solid rgba(201,139,22,.45);
        }
        .history-card .halal-badge{
            position:absolute;top:10px;left:10px;padding:5px 11px;border-radius:999px;
            background:rgba(255,255,255,.95);color:var(--text);font-size:.75rem;font-weight:800;
            box-shadow:0 4px 10px rgba(0,0,0,.12);
        }
        .history-card .halal-badge.halal{color:#1e7a3c}
        .history-card .halal-badge.non-halal{color:#b33a3a}
        .history-card .year-badge{
            position:absolute;bottom:10px;right:10px;padding:5px 11px;border-radius:999px;
            background:linear-gradient(135deg,var(--red-dark),var(--red));color:white;
            font-size:.75rem;font-weight:800;box-shadow:0 4px 10px rgba(105,22,22,.3);
        }
        .history-card h3{margin-top:0;color:var(--red-dark)}
        .history-meta{font-size:.9rem;color:var(--muted);margin-bottom:8px}
        .history-card .meta-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px}
        .history-card .meta-chip{
            display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:999px;
            background:var(--cream);border:1px solid rgba(140,31,31,.14);font-size:.85rem;
        }
        .history-card .meta-chip em{font-style:normal;color:var(--muted);font-size:.85em}
        .history-card .meta-chip strong.halal{color:#1e7a3c}
        .history-card .meta-chip strong.non-halal{color:#b33a3a}

        .empty-state{text-align:center;padding:30px 20px;background:white;border-radius:16px;color:var(--muted)}
        .empty-state .big-icon{font-size:3rem;margin-bottom:10px;display:block}

        @media(max-width:800px){
            .history-grid{grid-template-columns:1fr}
            .history-card{grid-template-columns:1fr;justify-items:center;text-align:center}
            .history-card .meta-chips{justify-content:center}
        }
    </style>
</head>
<body>
<div class="page">
    <section class="hero animate__animated animate__fadeInDown">
        <h1>Blind Box History</h1>
        <p>All of your surprise discoveries are recorded here so the heritage trail stays memorable.</p>
        <div class="button-row">
            <a class="btn btn-secondary" href="/blind-box">Back to Blind Box</a>
            <a class="btn btn-secondary" href="/heritage-shops">Browse Heritage Shops</a>
            <a class="btn btn-secondary" href="/foodtrails">Start a Food Trail</a>
        </div>
    </section>

    <section class="section">
        <h2>Recent draws</h2>
        @if(empty($drawHistory))
            <div class="empty-state">
                <span class="big-icon">🎁</span>
                <p><strong>No draws yet.</strong></p>
                <p>Open the Blind Box to discover a heritage shop and your surprise will appear here.</p>
            </div>
        @else
            <div class="history-grid">
                @foreach($drawHistory as $draw)
                    <article class="history-card animate__animated animate__fadeInUp">
                        <div class="media">
                            @if(!empty($draw['image']))
                                <img src="{{ $draw['image'] }}" alt="{{ $draw['shop_name'] }}">
                            @endif
                            <span class="halal-badge {{ !empty($draw['halal']) && $draw['halal'] ? 'halal' : 'non-halal' }}">
                                {{ !empty($draw['halal']) && $draw['halal'] ? '✓ Halal' : '✕ Non-Halal' }}
                            </span>
                            @if(!empty($draw['year']))
                                <span class="year-badge">Est. {{ $draw['year'] }}</span>
                            @endif
                        </div>
                        <div>
                            <div class="history-meta">{{ $draw['period_label'] ?? ucfirst($draw['period']) }} draw · {{ \Carbon\Carbon::parse($draw['drawn_at'])->format('M j, Y H:i') }}</div>
                            <h3>{{ $draw['shop_name'] }}</h3>
                            <p style="margin-bottom:10px">{{ $draw['description'] }}</p>
                            <div class="meta-chips">
                                <span class="meta-chip"><em>Category:</em> <strong>{{ $draw['category'] ?: 'Heritage' }}</strong></span>
                                <span class="meta-chip"><em>State:</em> <strong>{{ $draw['state'] ?: 'Malaysia' }}</strong></span>
                                <span class="meta-chip"><em>Status:</em> <strong class="{{ !empty($draw['halal']) && $draw['halal'] ? 'halal' : 'non-halal' }}">{{ !empty($draw['halal']) && $draw['halal'] ? 'Halal' : 'Non-Halal' }}</strong></span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
</body>
</html>
