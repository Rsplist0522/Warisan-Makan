<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WarisanMakan | Blind Box Surprise</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    @php
        $shops = $shops ?? [];
        $states = $states ?? [];
        $categories = $categories ?? ['Main Dishes', 'Desserts', 'Drinks'];
        $activeFilters = $activeFilters ?? ['state' => '', 'category' => ''];
        $period = $period ?? 'night';
        $periodInfo = $periodInfo ?? ['key' => 'night', 'label' => 'Night', 'tag' => 'Dinner time', 'icon' => '🌙'];
        $alreadyDrew = $alreadyDrew ?? false;
        $currentDraw = $currentDraw ?? null;

        $heritageShopsUrl = '/heritage-shops';
        $foodtrailUrl = '/foodtrails';
    @endphp

    <style>
        :root{--red:#8c1f1f;--red-dark:#691616;--cream:#f7efe4;--cream-2:#efe0c9;--text:#2f241d;--muted:#6f5845;--gold:#c98b16;--bg-start:#fcf7ef;--bg-end:#f7efe4}
        *{box-sizing:border-box}
        body{margin:0;font-family:"Segoe UI",Arial,sans-serif;background:linear-gradient(135deg,var(--bg-start),var(--bg-end));color:var(--text);line-height:1.6}
        a{color:inherit;text-decoration:none}
        .page{max-width:1200px;margin:0 auto;padding:24px 18px 48px}
        .hero,.section{background:rgba(255,255,255,.92);border:1px solid rgba(140,31,31,.08);border-radius:24px;box-shadow:0 14px 40px rgba(69,34,18,.08);backdrop-filter:blur(10px)}
        .hero{padding:24px;display:grid;gap:20px}
        .hero-grid{display:grid;gap:20px;grid-template-columns:1.3fr .9fr;align-items:center}
        .eyebrow{display:inline-block;padding:6px 12px;background:rgba(201,139,22,.16);color:var(--gold);border-radius:999px;font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
        h1,h2,h3{font-family:Georgia,"Times New Roman",serif;margin:0 0 8px}
        h1{font-size:clamp(2rem,4vw,3.2rem);line-height:1.1}
        .lead{font-size:1rem;color:var(--muted);max-width:680px}
        .button-row{display:flex;gap:12px;flex-wrap:wrap;margin-top:16px}
        .btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:999px;font-weight:700;border:none;cursor:pointer}
        .btn-primary{background:var(--red);color:white}
        .btn-secondary{background:white;color:var(--red-dark);border:1px solid rgba(140,31,31,.18)}
        .illustration{min-height:260px;border-radius:20px;background:linear-gradient(135deg,var(--red-dark),var(--red));position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;color:white;padding:24px}
        .illustration-card{position:relative;z-index:1;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.22);padding:20px;border-radius:18px;width:100%}
        .section{padding:28px;margin-top:24px}

        .period-banner{display:inline-flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:14px;padding:10px 18px;border-radius:999px;background:rgba(201,139,22,.14);color:var(--red-dark);border:1px solid rgba(201,139,22,.35);font-weight:600}
        .period-banner .period-text em{color:var(--muted);font-style:normal;font-size:.92em}
        .period-rule{margin-left:6px;padding-left:12px;border-left:2px solid rgba(140,31,31,.25);font-size:.9em;color:var(--muted)}

        .filter-row{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:18px 0 6px}
        .filter-row select{border:1px solid rgba(140,31,31,.18);border-radius:14px;padding:10px 14px;background:white;color:var(--text);font-size:.9rem}
        .filter-reset{font-size:.85rem;color:var(--red-dark);text-decoration:underline;cursor:pointer}

        .blind-box-card{margin-top:12px;padding:28px;border-radius:24px;background:linear-gradient(135deg,#fffaf2,#f7efe4);border:1px solid rgba(140,31,31,.1);position:relative;overflow:hidden}
        .blind-box-card::before{content:"";position:absolute;inset:0;pointer-events:none;background:radial-gradient(600px 200px at 50% -10%,rgba(201,139,22,.10),transparent 60%),radial-gradient(400px 200px at 50% 110%,rgba(140,31,31,.06),transparent 60%)}
        .box-stage{position:relative;margin:10px auto 0;width:260px;height:300px;display:flex;align-items:center;justify-content:center}

        .ribbon-ring{position:absolute;inset:-30px;pointer-events:none}
        .ribbon-ring span{position:absolute;border-radius:50%;border:2px dashed rgba(140,31,31,.28);animation:ribbon-spin 22s linear infinite}
        .ribbon-ring span:nth-child(1){inset:0;animation-duration:26s}
        .ribbon-ring span:nth-child(2){inset:18px;animation-duration:18s;animation-direction:reverse;border-color:rgba(201,139,22,.35)}
        .ribbon-ring span:nth-child(3){inset:36px;animation-duration:12s;border-style:solid;border-width:1px;border-color:rgba(140,31,31,.15)}
        .ribbon-ring span:nth-child(4){inset:-14px;animation-duration:34s;animation-direction:reverse;border-style:dotted;border-color:rgba(201,139,22,.25)}
        @keyframes ribbon-spin{to{transform:rotate(360deg)}}

        .sparkles{position:absolute;inset:-40px;pointer-events:none}
        .sparkles i{position:absolute;width:7px;height:7px;border-radius:50%;background:radial-gradient(circle,var(--gold),rgba(201,139,22,0));animation:sparkle 2.4s ease-in-out infinite}
        .sparkles i:nth-child(1){top:8%;left:12%;animation-delay:0s}
        .sparkles i:nth-child(2){top:4%;left:60%;animation-delay:.5s}
        .sparkles i:nth-child(3){top:18%;right:8%;animation-delay:1s}
        .sparkles i:nth-child(4){bottom:22%;left:4%;animation-delay:.3s}
        .sparkles i:nth-child(5){bottom:10%;left:30%;animation-delay:1.2s}
        .sparkles i:nth-child(6){bottom:6%;right:22%;animation-delay:.7s}
        .sparkles i:nth-child(7){top:40%;left:2%;animation-delay:1.6s}
        .sparkles i:nth-child(8){top:45%;right:2%;animation-delay:.9s}
        @keyframes sparkle{0%,100%{opacity:.15;transform:scale(.6)}50%{opacity:1;transform:scale(1.5)}}

        .box{position:relative;z-index:2;width:220px;height:220px;cursor:pointer;user-select:none;-webkit-tap-highlight-color:transparent}
        .box-lid{position:absolute;top:0;left:-12px;right:-12px;height:46px;border-radius:18px 18px 4px 4px;background:linear-gradient(180deg,#a32828,var(--red-dark));box-shadow:0 6px 0 rgba(0,0,0,.15),0 10px 18px rgba(105,22,22,.3)}
        .box-body{position:absolute;bottom:0;left:0;right:0;height:178px;border-radius:14px;background:linear-gradient(135deg,var(--red-dark),var(--red));box-shadow:0 20px 45px rgba(105,22,22,.28);display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;gap:8px;transition:transform .25s ease,box-shadow .25s ease}
        .box-body::before{content:"";position:absolute;inset:8px;border-radius:10px;border:2px dashed rgba(255,255,255,.35);pointer-events:none}
        .box:hover .box-body{transform:translateY(-6px) scale(1.03);box-shadow:0 28px 55px rgba(105,22,22,.35)}
        .box-question{font-size:4.2rem;font-weight:800;font-family:Georgia,serif;text-shadow:0 3px 8px rgba(0,0,0,.25);line-height:1}
        .box-label{font-size:.85rem;letter-spacing:.28em;font-weight:800;text-transform:uppercase;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.3);border-radius:999px;padding:4px 14px}
        .box-shake{animation:box-shake .6s ease}
        @keyframes box-shake{0%,100%{transform:translateX(0) rotate(0)}15%{transform:translateX(-8px) rotate(-4deg)}30%{transform:translateX(8px) rotate(4deg)}45%{transform:translateX(-6px) rotate(-3deg)}60%{transform:translateX(6px) rotate(3deg)}75%{transform:translateX(-3px) rotate(-1deg)}90%{transform:translateX(3px)}}
        .box[data-disabled="1"]{cursor:not-allowed;opacity:.75}
        .box[data-disabled="1"] .box-body{box-shadow:0 10px 20px rgba(105,22,22,.15)}

        .result{display:none;margin-top:30px;padding:26px;background:linear-gradient(160deg,#ffffff 0%,#fdf6ea 100%);border-radius:24px;border:1px solid rgba(201,139,22,.3);box-shadow:0 24px 60px rgba(69,34,18,.14);position:relative;overflow:hidden}
        .result.show{display:block}
        .reveal-header{margin-bottom:18px}
        .renewal-tag{display:inline-block;padding:8px 20px;border-radius:999px;font-weight:800;font-size:.95rem;letter-spacing:.06em;color:var(--red-dark);background:linear-gradient(90deg,rgba(201,139,22,.18),rgba(250,214,132,.35),rgba(201,139,22,.18));background-size:200% 100%;animation:shimmer 2.2s linear infinite}
        @keyframes shimmer{to{background-position:-200% 0}}

        .result-content{display:grid;grid-template-columns:260px 1fr;gap:22px;align-items:start}
        .result-media{position:relative;width:260px;height:260px;flex-shrink:0}
        .result-image{width:260px;height:260px;object-fit:cover;border-radius:18px;border:3px solid rgba(201,139,22,.45);box-shadow:0 14px 30px rgba(69,34,18,.18)}
        .year-badge{position:absolute;bottom:12px;right:12px;padding:6px 12px;border-radius:999px;background:linear-gradient(135deg,var(--red-dark),var(--red));color:white;font-size:.8rem;font-weight:800;box-shadow:0 4px 10px rgba(105,22,22,.3)}
        .result-info{padding-top:4px}
        .result-info .shop-name{font-size:1.6rem;color:var(--red-dark);margin-bottom:8px}
        .shop-desc{color:var(--muted);margin:0 0 14px}
        .shop-meta{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px}
        .meta-chip{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:999px;background:white;border:1px solid rgba(140,31,31,.16);font-size:.9rem}
        .meta-chip em{font-style:normal;color:var(--muted);font-size:.85em}

        .next-steps{margin-top:20px}
        .next-steps-label{font-size:.78rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:10px}
        .cta-row{display:flex;flex-wrap:wrap;gap:14px}
        .cta-btn{display:inline-flex;align-items:center;gap:10px;padding:14px 22px;border-radius:16px;border:none;cursor:pointer;font-weight:800;font-size:.98rem;letter-spacing:.01em;box-shadow:0 10px 22px rgba(69,34,18,.18);transition:transform .18s ease,box-shadow .18s ease}
        .cta-btn:hover{transform:translateY(-3px);box-shadow:0 16px 30px rgba(69,34,18,.26)}
        .cta-btn .cta-icon{font-size:1.25rem;line-height:1}
        .cta-btn .cta-arrow{margin-left:4px;opacity:.7;transition:transform .18s ease}
        .cta-btn:hover .cta-arrow{transform:translateX(4px);opacity:1}
        .cta-primary{background:linear-gradient(135deg,var(--red-dark),var(--red));color:#fff}
        .cta-outline{background:#fff;color:var(--red-dark);border:2px solid var(--red-dark)}

        .result-footnote{margin:20px 0 0;color:var(--muted);font-size:.92rem}
        .result-footnote .period-name{text-transform:capitalize}

        .shop-grid{display:grid;gap:16px;margin-top:20px;grid-template-columns:repeat(2,minmax(0,1fr))}
        .item-card{background:#ffffff;border:1px solid rgba(140,31,31,.08);border-radius:18px;overflow:hidden;box-shadow:0 10px 24px rgba(69,34,18,.06);transition:transform .2s ease,box-shadow .2s ease}
        .item-card:hover{transform:translateY(-4px);box-shadow:0 16px 32px rgba(69,34,18,.12)}
        .item-media{position:relative;width:100%;height:200px;overflow:hidden}
        .item-media img{width:100%;height:200px;object-fit:cover}
        .item-media .year-badge{bottom:10px;right:10px;font-size:.72rem}
        .item-body{padding:14px 16px 18px}
        .tag{display:inline-block;padding:4px 10px;background:rgba(201,139,22,.14);color:var(--gold);border-radius:999px;font-size:.72rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;margin-bottom:8px}
        .item-body h3{font-size:1.05rem;margin-bottom:6px}
        .item-body .muted{color:var(--muted);font-size:.85rem;margin:0 0 10px;line-height:1.5}
        .item-meta{display:flex;align-items:center;gap:10px;font-size:.8rem;color:var(--muted)}
        .item-meta strong{color:var(--text)}
        .item-meta .state-chip{display:inline-flex;align-items:center;gap:4px;background:var(--cream);border:1px solid rgba(140,31,31,.12);padding:4px 10px;border-radius:999px;font-size:.78rem}
        .empty-message{text-align:center;padding:30px 20px;color:var(--muted)}

        @media(max-width:800px){
            .hero-grid{grid-template-columns:1fr}
            .box-stage{width:220px;height:270px}
            .box{width:180px;height:180px}
            .result-content{grid-template-columns:1fr;justify-items:center;text-align:center}
            .shop-meta{justify-content:center}
            .cta-row{justify-content:center}
            .shop-grid{grid-template-columns:1fr}
        }
    </style>
</head>

<body>
<div class="page">

    <section class="hero">
        <div class="hero-grid">
            <div>
                <span class="eyebrow">WarisanMakan • Heritage Discovery PWA</span>
                <h1>Preserve Malaysia's culinary heritage through every bite.</h1>
                <p class="lead">Discover forgotten food stories, celebrate traditional vendors, and let every visit feel like a small cultural expedition.</p>
                <div class="button-row">
                    <a class="btn btn-primary" href="#blind-box">Explore Blind Box</a>
                    <a class="btn btn-secondary" href="/">Go to main page</a>
                </div>
            </div>

            <div class="illustration">
                <div class="illustration-card">
                    <h3>"A heritage food trail, made playful."</h3>
                    <p style="color:#fbeedc;opacity:.95;font-weight:500;">Warm spice-market energy, editorial storytelling, and one surprise discovery at a time.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="discover" class="section">
        <h2>{{ __('Heritage Shop Discovery') }}</h2>
        <p class="lead">All heritage shops currently in the Blind Box pool — the surprise pick comes from this list.</p>

        @if(empty($shops))
            <div class="empty-message">
                <p><strong>No heritage shops match your current filters.</strong></p>
                <p>Try widening your filters below.</p>
            </div>
        @else
            <div class="shop-grid">
                @foreach($shops as $shop)
                    <article class="item-card">
                        <div class="item-media">
                            <img src="{{ $shop['image'] ?? '' }}" alt="{{ $shop['name'] ?? '' }}">
                            @if(!empty($shop['year']))
                                <span class="year-badge">Est. {{ $shop['year'] }}</span>
                            @endif
                        </div>
                        <div class="item-body">
                            <span class="tag">{{ $shop['category'] ?? 'Heritage' }}</span>
                            <h3>{{ $shop['name'] ?? '' }}</h3>
                            <p class="muted">{{ $shop['description'] ?? '' }}</p>
                            <div class="item-meta">
                                <span class="state-chip">📍 {{ $shop['state'] ?? 'Malaysia' }}</span>
                                <span>Since {{ $shop['year'] ?? 'Heritage' }}</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section id="blind-box" class="section">
        <h2>Blind Box Recommendation</h2>
        <p class="lead">Set your filters, then tap the box for a surprise heritage shop recommendation.</p>

        <div class="period-banner animate__animated animate__fadeIn">
            <span class="period-icon">{{ $periodInfo['icon'] }}</span>
            <span class="period-text">It is currently <strong>{{ $periodInfo['label'] }}</strong> &nbsp;·&nbsp; <em>({{ $periodInfo['tag'] }})</em></span>
            <span class="period-rule">One surprise draw per period</span>
        </div>

        <form action="{{ url('/blind-box') }}#blind-box" method="GET">
            <div class="filter-row">
                <select name="state" onchange="this.form.submit()">
                    <option value="">All states</option>
                    @foreach($states as $option)
                        <option value="{{ $option }}" @selected($activeFilters['state'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>

                <select name="category" onchange="this.form.submit()">
                    <option value="">All food categories</option>
                    @foreach($categories as $option)
                        <option value="{{ $option }}" @selected($activeFilters['category'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>

                @if($activeFilters['state'] !== '' || $activeFilters['category'] !== '')
                    <a class="filter-reset" href="{{ url('/blind-box') }}#blind-box">Reset filters</a>
                @endif
            </div>
        </form>

        <div class="blind-box-card">
            <div class="box-stage">
                <div class="ribbon-ring"><span></span><span></span><span></span><span></span></div>

                <div class="sparkles">
                    <i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i>
                </div>

                <div id="box" class="box animate__animated animate__pulse animate__infinite animate__slow"
                     data-state="{{ $activeFilters['state'] }}"
                     data-category="{{ $activeFilters['category'] }}"
                     @if($alreadyDrew) data-disabled="1" @endif>
                    <div class="box-lid"></div>
                    <div class="box-body">
                        <div class="box-question">{{ $alreadyDrew ? '✓' : '?' }}</div>
                        <div class="box-label">{{ $alreadyDrew ? 'OPENED' : 'OPEN ME' }}</div>
                    </div>
                </div>
            </div>

            <div id="result" class="result"></div>
        </div>
    </section>

</div>

<script>
    const BLIND_BOX_CONFIG = {
        drawUrl: '{{ route('blind-box.draw') }}',
        csrfToken: '{{ csrf_token() }}',
        heritageShopsUrl: '{{ $heritageShopsUrl }}',
        foodtrailUrl: '{{ $foodtrailUrl }}',
        initialDraw: @json($currentDraw),
        alreadyDrew: @json($alreadyDrew),
        periodKey: @json($periodInfo['key']),
    };

    document.addEventListener('DOMContentLoaded', function () {
        const box = document.getElementById('box');
        const result = document.getElementById('result');

        if (!box || !result) return;

        function confettiBurst() {
            const colors = ['#8c1f1f', '#c98b16', '#691616', '#f7c948', '#d96c6c'];

            for (let i = 0; i < 48; i++) {
                const p = document.createElement('div');
                const size = 6 + Math.random() * 6;

                Object.assign(p.style, {
                    position: 'fixed',
                    left: '50%',
                    top: '50%',
                    width: size + 'px',
                    height: size + 'px',
                    background: colors[Math.floor(Math.random() * colors.length)],
                    borderRadius: Math.random() > .5 ? '50%' : '2px',
                    pointerEvents: 'none',
                    zIndex: 9999
                });

                document.body.appendChild(p);

                const angle = Math.random() * Math.PI * 2;
                const velocity = 250 + Math.random() * 350;
                const dx = Math.cos(angle) * velocity;
                const dy = Math.sin(angle) * velocity - 200;

                p.animate([
                    { transform: 'translate(0,0) rotate(0deg)', opacity: 1 },
                    { transform: 'translate(' + dx + 'px, ' + (dy + 500) + 'px) rotate(' + (Math.random() * 720) + 'deg)', opacity: 0 }
                ], {
                    duration: 1200 + Math.random() * 600,
                    easing: 'cubic-bezier(.2,.6,.4,1)'
                }).onfinish = function () {
                    p.remove();
                };
            }
        }

        function renderResult(shop, periodKey, { animateIn = false } = {}) {
            const mediaAnim = animateIn ? 'animate__animated animate__zoomIn' : '';
            const infoAnim = animateIn ? 'animate__animated animate__fadeInRight' : '';

            result.className = 'result show';

            result.innerHTML = `
                <div class="reveal-header">
                    <span class="renewal-tag">✨ Surprise Discovery Unlocked ✨</span>
                </div>

                <div class="result-content">
                    <div class="result-media ${mediaAnim}">
                        <img class="result-image" src="${shop.image || ''}" alt="${shop.name || shop.shop_name || 'Heritage Shop'}">
                        <span class="year-badge">Est. ${shop.year || 'Heritage'}</span>
                    </div>

                    <div class="result-info ${infoAnim}">
                        <h3 class="shop-name">${shop.name || shop.shop_name || 'Heritage Shop'}</h3>
                        <p class="shop-desc">${shop.description || 'No description available.'}</p>

                        <div class="shop-meta">
                            <span class="meta-chip"><em>Category:</em> <strong>${shop.category || 'Heritage'}</strong></span>
                            <span class="meta-chip"><em>State:</em> <strong>${shop.state || 'Malaysia'}</strong></span>
                        </div>

                        <div class="next-steps">
                            <div class="next-steps-label">What would you like to do next?</div>

                            <div class="cta-row">
                                <a class="cta-btn cta-primary" href="${BLIND_BOX_CONFIG.foodtrailUrl}">
                                    <span class="cta-icon">🧭</span> Explore Food Trails <span class="cta-arrow">→</span>
                                </a>

                                <a class="cta-btn cta-outline" href="${BLIND_BOX_CONFIG.heritageShopsUrl}">
                                    <span class="cta-icon">🏮</span> Browse Heritage Shops <span class="cta-arrow">→</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="result-footnote">
                    You opened this Blind Box during the
                    <strong class="period-name">${periodKey || 'current'}</strong>
                    period. Come back next period for another surprise!
                </p>
            `;
        }

        function renderError(message, label) {
            result.className = 'result show';
            result.innerHTML = `
                <div class="reveal-header">
                    <span class="renewal-tag">⏳ ${label}</span>
                </div>
                <p>${message}</p>
            `;
        }

        if (BLIND_BOX_CONFIG.alreadyDrew && BLIND_BOX_CONFIG.initialDraw) {
            renderResult(BLIND_BOX_CONFIG.initialDraw, BLIND_BOX_CONFIG.periodKey, {
                animateIn: false
            });
        }

        box.addEventListener('click', async function () {
            if (box.dataset.disabled === '1') {
                result.classList.add('show');
                return;
            }

            box.dataset.disabled = '1';
            box.classList.remove('animate__pulse', 'animate__infinite', 'animate__slow');
            box.classList.add('box-shake');

            const params = new URLSearchParams();

            if (box.dataset.state) {
                params.set('state', box.dataset.state);
            }

            if (box.dataset.category) {
                params.set('category', box.dataset.category);
            }

            try {
                const response = await fetch(
                    BLIND_BOX_CONFIG.drawUrl + (params.toString() ? '?' + params : ''),
                    {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': BLIND_BOX_CONFIG.csrfToken,
                            'Accept': 'application/json'
                        }
                    }
                );

                const data = await response.json();

                if (!response.ok) {
                    renderError(
                        data.error || 'Something went wrong. Please try again.',
                        'Try Again Later'
                    );

                    box.querySelector('.box-question').textContent = '!';
                    box.querySelector('.box-label').textContent = 'TRY LATER';
                    box.classList.remove('box-shake');

                    return;
                }

                renderResult(
                    data.shop || {},
                    (data.period_info || {}).key,
                    { animateIn: true }
                );

                confettiBurst();

                box.querySelector('.box-question').textContent = '✓';
                box.querySelector('.box-label').textContent = 'OPENED';
                box.classList.remove('box-shake');

            } catch (error) {
                console.error(error);

                renderError(
                    'Something went wrong. Please try again.',
                    'Oops'
                );

                box.querySelector('.box-question').textContent = '!';
                box.querySelector('.box-label').textContent = 'TRY AGAIN';

                box.dataset.disabled = '';
                box.classList.remove('box-shake');
            }
        });
    });
</script>

@include('partials.chatbox')

</body>
</html>
