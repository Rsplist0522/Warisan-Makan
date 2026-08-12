<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WarisanMakan | Heritage Discovery</title>
    <style>
        :root {
            --red: #8c1f1f;
            --red-dark: #691616;
            --cream: #f7efe4;
            --cream-2: #efe0c9;
            --text: #2f241d;
            --muted: #6f5845;
            --gold: #c98b16;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #fcf7ef 0%, var(--cream) 100%);
            color: var(--text);
            line-height: 1.6;
        }
        a { color: inherit; text-decoration: none; }
        .page { max-width: 1200px; margin: 0 auto; padding: 24px 18px 48px; }
        .hero, .section { background: rgba(255,255,255,0.78); border: 1px solid rgba(140,31,31,0.08); border-radius: 24px; box-shadow: 0 14px 40px rgba(69,34,18,0.08); backdrop-filter: blur(10px); }
        .hero { padding: 24px; display: grid; gap: 20px; }
        .eyebrow { display: inline-block; padding: 6px 12px; background: rgba(201,139,22,0.16); color: var(--gold); border-radius: 999px; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
        h1, h2, h3 { font-family: Georgia, "Times New Roman", serif; margin: 0 0 8px; }
        h1 { font-size: clamp(2rem, 4vw, 3.2rem); line-height: 1.1; }
        .lead { font-size: 1rem; color: var(--muted); max-width: 680px; }
        .button-row { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 12px 16px; border-radius: 999px; font-weight: 700; border: none; cursor: pointer; }
        .btn-primary { background: var(--red); color: white; }
        .btn-secondary { background: #fff; color: var(--red-dark); border: 1px solid rgba(140,31,31,0.18); }
        .hero-grid { display: grid; gap: 20px; grid-template-columns: 1.3fr 0.9fr; align-items: center; }
        .illustration { min-height: 260px; border-radius: 20px; background: linear-gradient(135deg, var(--red-dark), var(--red)); position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; color: white; padding: 24px; }
        .illustration::before { content: ""; position: absolute; inset: 0; background: radial-gradient(circle at top left, rgba(255,255,255,0.26), transparent 40%), repeating-linear-gradient(120deg, rgba(255,255,255,0.08) 0 2px, transparent 2px 12px); }
        .illustration-card { position: relative; z-index: 1; background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.22); padding: 20px; border-radius: 18px; width: 100%; }
        .section { padding: 22px; margin-top: 20px; }
        .shop-grid { display: grid; gap: 16px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .item-card { padding: 16px; border-radius: 18px; background: #fffdf8; border: 1px solid rgba(140,31,31,0.08); }
        .tag { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 0.78rem; background: rgba(201,139,22,0.16); color: var(--gold); font-weight: 700; margin-bottom: 8px; }
        .blind-box-card { background: linear-gradient(135deg, #fff8eb 0%, #f9e8c6 100%); border: 1px solid rgba(140,31,31,0.14); padding: 22px; border-radius: 22px; display: grid; gap: 14px; margin-top: 18px; }
        .box { width: 100%; max-width: 240px; aspect-ratio: 1; margin: 0 auto; border-radius: 26px; background: linear-gradient(145deg, var(--red-dark), var(--red)); box-shadow: 0 18px 36px rgba(105,22,22,0.26); display: flex; align-items: center; justify-content: center; color: white; text-align: center; padding: 18px; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; cursor: pointer; transition: transform 0.25s ease, box-shadow 0.25s ease; border: 2px solid rgba(255,255,255,0.2); }
        .box:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(105,22,22,0.32); }
        .result { display: none; padding: 16px; border-radius: 16px; background: white; border: 1px solid rgba(140,31,31,0.1); }
        .result.show { display: block; }
        .result-image { width: 100%; max-height: 220px; object-fit: cover; border-radius: 14px; margin-bottom: 12px; }
        .muted { color: var(--muted); }
        @media (max-width: 800px) { .hero-grid { grid-template-columns: 1fr; } .shop-grid { grid-template-columns: 1fr; } }
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
                    <a class="btn btn-secondary" href="#discover">Browse Heritage Shops</a>
                </div>
            </div>
            <div class="illustration">
                <div class="illustration-card">
                    <h3>“A heritage food trail, made playful.”</h3>
                    <p style="color: #fbeedc; opacity: 0.95; font-weight: 500;">Warm spice-market energy, editorial storytelling, and one surprise discovery at a time.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="discover" class="section">
        <h2>Heritage Shop Discovery</h2>
        <p class="lead">Browse featured vendors with founder stories and generations of flavour.</p>
        <div class="shop-grid">
            @foreach($shops as $shop)
                <article class="item-card">
                    <span class="tag">{{ $shop['category'] }}</span>
                    <h3>{{ $shop['name'] }}</h3>
                    <p class="muted">{{ $shop['description'] }}</p>
                    <p><strong>State:</strong> {{ $shop['state'] }} • <strong>Since:</strong> {{ $shop['year'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section id="blind-box" class="section">
        <h2>Blind Box Recommendation</h2>
        <p class="lead">Tap the box for a surprise heritage shop recommendation inspired by the spirit of discovery.</p>
        <div class="blind-box-card">
            <div id="box" class="box">Open the box</div>
            <div id="result" class="result"></div>
        </div>
    </section>
</div>

<script>
    const box = document.getElementById('box');
    const result = document.getElementById('result');

    box.addEventListener('click', async () => {
        box.textContent = 'Unwrapping...';
        box.style.transform = 'scale(0.96)';

        try {
            const response = await fetch('/blind-box/draw', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });

            const data = await response.json();
            const shop = data.shop;
            result.className = 'result show';
            result.innerHTML = `
                <img class="result-image" src="${shop.image}" alt="${shop.name}">
                <h3>Surprise discovery unlocked</h3>
                <p><strong>${shop.name}</strong></p>
                <p>${shop.description}</p>
                <p><strong>Category:</strong> ${shop.category} • <strong>State:</strong> ${shop.state} • <strong>Since:</strong> ${shop.year}</p>
            `;
            box.textContent = 'Try another surprise';
        } catch (error) {
            result.className = 'result show';
            result.innerHTML = '<p>Something went wrong. Please try again.</p>';
            box.textContent = 'Try again';
        }
    });
</script>
</body>
</html>
