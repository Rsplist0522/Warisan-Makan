<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WarisanMakan | Heritage Discovery</title>

    @php
        $shops = $shops ?? [];
        $categories = $categories ?? [];
        $selectedCategory = $selectedCategory ?? '';
        $period = $period ?? 'night';
        $alreadyDrew = $alreadyDrew ?? false;
        $currentDraw = $currentDraw ?? null;
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
        .shop-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:20px}
        .item-card{background:white;border:1px solid rgba(140,31,31,.08);border-radius:18px;padding:20px}
        .tag{display:inline-block;padding:5px 10px;border-radius:999px;background:rgba(201,139,22,.14);color:var(--gold);font-size:.8rem;font-weight:700}
        .blind-box-card{margin-top:20px;padding:24px;border-radius:20px;background:linear-gradient(135deg,#fffaf2,#f7efe4);border:1px solid rgba(140,31,31,.1)}
        .box{width:220px;height:220px;margin:30px auto;border-radius:28px;background:linear-gradient(135deg,var(--red-dark),var(--red));color:white;display:flex;align-items:center;justify-content:center;text-align:center;font-weight:800;font-size:1.3rem;cursor:pointer;box-shadow:0 20px 45px rgba(105,22,22,.25);transition:transform .25s ease,box-shadow .25s ease;user-select:none}
        .box:hover{transform:translateY(-6px) scale(1.02);box-shadow:0 28px 55px rgba(105,22,22,.32)}
        .box[data-disabled="1"]{cursor:not-allowed;opacity:.7}
        .result{display:none;margin-top:24px;padding:22px;background:white;border-radius:18px;border:1px solid rgba(140,31,31,.1)}
        .result.show{display:block}
        .result-image{width:100%;max-height:220px;object-fit:cover;border-radius:14px;margin-bottom:12px}
        .muted{color:var(--muted)}
        .time-banner{display:inline-flex;align-items:center;gap:10px;padding:10px 14px;border-radius:999px;background:rgba(201,139,22,.14);color:var(--red-dark);font-weight:700;margin-top:10px}
        .filter-row{margin-top:18px;display:flex;flex-wrap:wrap;gap:10px;align-items:center}
        .filter-row select{border:1px solid rgba(140,31,31,.18);border-radius:14px;padding:10px 14px;background:white;color:var(--text)}
        .empty-message{padding:20px;text-align:center;background:white;border-radius:16px;color:var(--muted)}
        @media(max-width:800px){.hero-grid{grid-template-columns:1fr}.shop-grid{grid-template-columns:1fr}.box{width:180px;height:180px}}
    </style>
</head>

<body>
<div class="page">

    <section class="hero">
        <div class="hero-grid">
            <div>
                <span class="eyebrow">WarisanMakan • Heritage Discovery PWA</span>
                <h1>Preserve Malaysia’s culinary heritage through every bite.</h1>
                <p class="lead">Discover forgotten food stories, celebrate traditional vendors, and let every visit feel like a small cultural expedition.</p>
                <div class="button-row">
                    <a class="btn btn-primary" href="#blind-box">Explore Blind Box</a>
                    <a class="btn btn-secondary" href="/">Go to main page</a>
                    <a class="btn btn-secondary" href="{{ route('blind-box.history') }}">View draw history</a>
                </div>
            </div>

            <div class="illustration">
                <div class="illustration-card">
                    <h3>“A heritage food trail, made playful.”</h3>
                    <p style="color:#fbeedc;opacity:.95;font-weight:500;">Warm spice-market energy, editorial storytelling, and one surprise discovery at a time.</p>
                    <p class="time-banner">It’s currently <strong>{{ ucfirst($period) }}</strong>. One surprise draw per period.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="discover" class="section">
        <h2>Heritage Shop Discovery</h2>
        <p class="lead">Browse featured vendors with founder stories and generations of flavour.</p>

        <div class="shop-grid">
            @forelse($shops as $shop)
                <article class="item-card">
                    <span class="tag">{{ $shop['category'] ?? 'Heritage' }}</span>
                    <h3>{{ $shop['name'] ?? 'Heritage Shop' }}</h3>
                    <p class="muted">{{ $shop['description'] ?? 'No description available.' }}</p>
                    <p><strong>State:</strong> {{ $shop['state'] ?? 'Malaysia' }} • <strong>Since:</strong> {{ $shop['year'] ?? 'Heritage' }}</p>
                </article>
            @empty
                <div class="empty-message">No heritage shops are available at the moment.</div>
            @endforelse
        </div>
    </section>

    <section id="blind-box" class="section">
        <h2>Blind Box Recommendation</h2>
        <p class="lead">Tap the box for a surprise heritage shop recommendation inspired by the spirit of discovery.</p>

        <div class="blind-box-card">

            @if(count($categories) > 1)
                <div class="filter-row" style="margin-bottom:12px;">
                    <form action="{{ url('/blind-box') }}" method="GET">
                        <label for="shopCategory">Filter blind box:</label>
                        <select id="shopCategory" name="category" onchange="this.form.submit()">
                            <option value="">All categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            <div id="box" class="box" data-category="{{ $selectedCategory }}" @if($alreadyDrew) data-disabled="1" @endif>
                {{ $alreadyDrew ? 'Already opened' : 'Open the box' }}
            </div>

            <div id="result" class="result @if(!empty($currentDraw)) show @endif">
                @if(!empty($currentDraw))
                    <img class="result-image" src="{{ $currentDraw['image'] ?? '' }}" alt="{{ $currentDraw['shop_name'] ?? 'Surprise' }}">
                    <h3>Surprise discovery unlocked</h3>
                    <p><strong>{{ $currentDraw['shop_name'] ?? '' }}</strong></p>
                    <p>{{ $currentDraw['description'] ?? '' }}</p>
                    <p><strong>Category:</strong> {{ $currentDraw['category'] ?? 'Heritage' }} • <strong>State:</strong> {{ $currentDraw['state'] ?? '' }} • <strong>Since:</strong> {{ $currentDraw['year'] ?? '' }}</p>

                    <div class="button-row" style="margin-top:12px;">
                        <a class="btn btn-secondary" href="/foodtrails">Explore Food Trails</a>
                        <a class="btn btn-secondary" href="/heritage-shops">Browse Heritage Shops</a>
                    </div>

                    <p class="muted" style="margin-top:12px;">You opened this Blind Box during the current <strong>{{ ucfirst($period) }}</strong>. Come back next period to try again.</p>
                @endif
            </div>

        </div>
    </section>

</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
    const box=document.getElementById('box'),result=document.getElementById('result');
    if(!box||!result)return;

    box.addEventListener('click',async function(){
        if(box.dataset.disabled==='1'){result.classList.add('show');return;}

        box.textContent='Unwrapping...';
        box.style.transform='scale(.96)';

        const category=box.dataset.category;
        const params=new URLSearchParams();
        if(category)params.set('category',category);

        try{
            const response=await fetch('/blind-box/draw'+(params.toString()?'?'+params:''),{
                method:'POST',
                headers:{
                    'X-CSRF-TOKEN':'{{ csrf_token() }}',
                    'Accept':'application/json'
                }
            });

            const data=await response.json();

            if(!response.ok){
                result.className='result show';
                result.innerHTML='<p>'+(data.error||'Something went wrong. Please try again.')+'</p>';
                box.textContent='Try again';
                box.style.transform='';
                return;
            }

            const shop=data.shop||{};

            result.className='result show';

            result.innerHTML=`
                <img class="result-image" src="${shop.image||''}" alt="${shop.name||'Heritage Shop'}">
                <h3>Surprise discovery unlocked</h3>
                <p><strong>${shop.name||'Heritage Shop'}</strong></p>
                <p>${shop.description||'No description available.'}</p>
                <p><strong>Category:</strong> ${shop.category||'Heritage'} • <strong>State:</strong> ${shop.state||'Malaysia'} • <strong>Since:</strong> ${shop.year||'Heritage'}</p>
                <div class="button-row" style="margin-top:16px;">
                    <a class="btn btn-secondary" href="/foodtrails">Explore Food Trails</a>
                    <a class="btn btn-secondary" href="#discover">Browse Heritage Shops</a>
                </div>
                <p class="muted" style="margin-top:12px;">You opened this Blind Box during the current <strong>${data.period||'period'}</strong>. Come back next period to try again.</p>
            `;

            box.textContent='Already opened';
            box.dataset.disabled='1';
            box.style.transform='';

        }catch(error){
            console.error(error);
            result.className='result show';
            result.innerHTML='<p>Something went wrong. Please try again.</p>';
            box.textContent='Try again';
            box.style.transform='';
        }
    });
});
</script>

</body>
</html>