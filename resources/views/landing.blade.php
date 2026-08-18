<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>WarisanMakan — Preserve Malaysia's Culinary Heritage</title>
  <style>
    /* Color palette */
    :root{
      --primary:#8C1F1F; /* deep red */
      --accent:#D4A017; /* warm turmeric gold */
      --bg:#FBF6EE; /* warm cream */
      --ink:#32241F; /* dark brown */
      --muted:#7A6A63;
    }

    html,body{height:100%;margin:0;background:var(--bg);color:var(--ink);font-family:Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial}
    /* Simple slab-like headline using system slab if available */
    .display{font-family: 'Georgia', 'Times New Roman', serif; font-weight:700; letter-spacing: -0.02em;}

    .container{max-width:1000px;margin:0 auto;padding:20px}
    header{padding:18px 0;display:flex;align-items:center;justify-content:space-between}
    .brand{display:flex;gap:12px;align-items:center}
    .logo{width:48px;height:48px;border-radius:10px;background:linear-gradient(135deg,var(--primary),#A32B2B);display:flex;align-items:center;justify-content:center;color:white;font-weight:700}
    nav a{color:var(--ink);text-decoration:none;margin-left:14px;font-weight:600}

    .hero{display:grid;grid-template-columns:1fr;gap:18px;align-items:center;padding:18px 0}
    .hero-inner{background:white;padding:18px;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,0.04)}
    .hero h1{font-size:1.6rem;margin:0;color:var(--primary);}
    .hero p{subtle;color:var(--muted);margin-top:8px}
    .cta{display:inline-flex;gap:10px;margin-top:12px}
    .btn-primary{background:var(--primary);color:white;padding:10px 14px;border-radius:8px;border:none;font-weight:700}
    .btn-outline{background:transparent;border:1px solid #E8E2DB;padding:10px 14px;border-radius:8px}

    /* Sections */
    section{margin-top:18px}
    .cards{display:grid;grid-template-columns:1fr;gap:12px}
    .card{background:white;padding:14px;border-radius:12px;box-shadow:0 4px 10px rgba(0,0,0,.03)}

    .shop-list{display:flex;gap:10px;flex-direction:column}
    .shop{display:flex;gap:12px;align-items:center}
    .avatar{width:64px;height:64px;border-radius:8px;background:linear-gradient(135deg,#F6E6D5,#F3D9C4);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary)}
    .shop .title{font-weight:700}
    .muted{color:var(--muted);font-size:.95rem}

    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}

    footer{margin-top:28px;padding:18px 0;color:var(--muted);font-size:.95rem}

    /* Mobile first tweaks */
    @media(min-width:820px){
      .hero{grid-template-columns:1fr 420px}
      .cards{grid-template-columns:1fr 1fr}
      .grid-2{grid-template-columns:1fr 1fr}
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <div class="brand">
        <div class="logo">WM</div>
        <div>
          <div style="font-weight:800">WarisanMakan</div>
          <div style="font-size:.85rem;color:var(--muted)">Preserve, explore, and celebrate</div>
        </div>
      </div>
      <nav>
        <a href="{{ route('passport.index') }}">Food Passport</a>
        <a href="#shops">Shops</a>
        <a href="#contribute">Contribute</a>
        <a href="{{ route('login') }}">Sign in</a>
      </nav>
    </header>

    <main>
      <section class="hero">
        <div class="hero-inner">
          <h1 class="display">Discover Malaysia's living food heritage</h1>
          <p class="muted">Explore traditional vendors, collect passport stamps, earn badges, and support local stories — all in a thoughtful, mobile-first experience.</p>
          <div class="cta">
            <a class="btn-primary" href="/login">Join WarisanMakan</a>
            <a class="btn-outline" href="/foodtrails">Generate a Food Trail</a>
          </div>

          <div style="margin-top:14px;color:var(--muted);font-size:.95rem">Why now: connect visitors with authentic heritage vendors while the community documents founders' stories and recipes.</div>
        </div>

        <div class="card" style="min-height:220px;display:flex;flex-direction:column;justify-content:center;align-items:center;">
          <!-- Signature visual placeholder (subtle batik linework background) -->
          <svg width="240" height="160" viewBox="0 0 240 160" xmlns="http://www.w3.org/2000/svg">
            <rect width="240" height="160" rx="12" fill="#F8F1E6" />
            <g fill="none" stroke="#F0E0C8" stroke-width="1">
              <path d="M0 30 C40 0, 80 60, 120 30 S200 0, 240 30"/>
              <path d="M0 90 C40 60, 80 120, 120 90 S200 60, 240 90"/>
            </g>
            <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#8C1F1F" font-family="Georgia, serif" font-size="18">Kopitiam Stories</text>
          </svg>
        </div>
      </section>

      <section id="shops">
        <h2 style="margin:10px 0">Heritage Shop Discovery</h2>
        <div class="card">
          <p class="muted">Search and preview traditional vendors, founder stories, and establishment years.</p>

          <div class="shop-list" id="shopList"></div>

          <div style="margin-top:12px" class="muted">Note: shops are seeded with dummy data for local testing. Use the "Check In (Test)" button to simulate a GPS check-in.</div>
        </div>
      </section>

      <section style="margin-top:12px">
        <h2>Food Passport & Achievement</h2>
        <div class="grid-2">
          <div class="card">
            <p class="muted">Collect stamps from participating vendors and earn badges for milestones.</p>
            <ul>
              <li>GPS check-ins and verified stamps</li>
              <li>Badge collection and unlock notifications</li>
              <li>Leaderboard for friendly competition</li>
            </ul>
            <a href="/foodPassport" class="btn-primary" style="display:inline-block;margin-top:8px;padding:8px 12px">Open Passport</a>
          </div>

          <div class="card" id="passportPreview">
            <h3 style="margin:0">Try a Test Check-In</h3>
            <div class="muted" style="font-size:.95rem;margin-top:8px">Select a shop below and use your device location to perform a development check-in (no login required).</div>
            <div style="margin-top:10px">
              <select id="testShopSelect" style="width:100%;padding:8px;margin-top:8px;border-radius:8px;border:1px solid #eee"></select>
              <button id="testCheckIn" class="btn-primary" style="width:100%;margin-top:10px">Check In (Test)</button>
              <pre id="testResult" style="margin-top:10px;background:#FBF6EE;padding:8px;border-radius:8px;color:var(--muted)">No action yet.</pre>
            </div>
          </div>
        </div>
      </section>

      <section style="margin-top:12px">
        <h2>Food Trail Generator</h2>
        <div class="card">
          <p class="muted">Generate personalized walking routes between heritage vendors. (Map preview below is illustrative.)</p>
          <div style="height:160px;background:#FFF6E8;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--muted)">Map preview</div>
        </div>
      </section>

      <section style="margin-top:12px">
        <h2>Blind Box Recommendation</h2>
        <div class="card" id="blindBoxCard">
          <p class="muted">Feeling adventurous? Get a surprise heritage food recommendation. Try the blind box for a curated discovery moment.</p>
          <button id="blindBtn" class="btn-outline">Surprise Me</button>
          <div id="blindResult" style="margin-top:10px;color:var(--muted)"></div>
        </div>
      </section>

      <section id="contribute" style="margin-top:12px">
        <h2>Community Contribution</h2>
        <div class="card">
          <p class="muted">Help preserve stories. Share a shop founder's story or add historical notes. Submissions are reviewed before publishing.</p>
          <a class="btn-primary" href="{{ route('login') }}">Contribute a Story</a>
        </div>
      </section>

    </main>

    <footer>
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap">
        <div>© WarisanMakan • Malaysia</div>
        <div style="color:var(--muted)">Built for community discovery</div>
      </div>
    </footer>
  </div>

<script>
// Dummy shops dataset
const shops = [
  { id: 1, name: 'Kedai Kopi Haji', founder: 'Haji Osman (1965)', lat: 3.1390, lng: 101.6869, participating: true, published: true },
  { id: 2, name: 'Mee Udang Tok', founder: 'Aunty Siti (1978)', lat: 3.1420, lng: 101.6950, participating: true, published: true },
  { id: 3, name: 'Nasi Lemak Heritage', founder: 'Encik Rahman (1952)', lat: 3.1350, lng: 101.6880, participating: false, published: false }
];

function renderShops(){
  const list = document.getElementById('shopList');
  const select = document.getElementById('testShopSelect');
  list.innerHTML = '';
  select.innerHTML = '';

  shops.forEach(s=>{
    const el = document.createElement('div');
    el.className = 'shop';
    el.innerHTML = `
      <div class="avatar">${s.name.split(' ').map(w=>w[0]).slice(0,2).join('')}</div>
      <div>
        <div class="title">${s.name}</div>
        <div class="muted">${s.founder}</div>
        <div class="muted">${s.participating? 'Participating' : 'Not participating'} • ${s.published? 'Published' : 'Unpublished'}</div>
      </div>
    `;
    list.appendChild(el);

    const option = document.createElement('option');
    option.value = s.id;
    option.textContent = `${s.name} — ${s.founder}` + (s.participating && s.published ? '' : ' (not active)');
    select.appendChild(option);
  });
}

renderShops();

// Blind box recommendation
document.getElementById('blindBtn').addEventListener('click', ()=>{
  const active = shops.filter(s=>s.participating && s.published);
  const pick = active[Math.floor(Math.random()*active.length)];
  document.getElementById('blindResult').textContent = pick ? `${pick.name} — ${pick.founder}` : 'No active recommendations yet.';
});

// Test check-in flow
document.getElementById('testCheckIn').addEventListener('click', ()=>{
  const sel = document.getElementById('testShopSelect');
  const shopId = Number(sel.value);
  const shop = shops.find(s=>s.id===shopId);
  const out = document.getElementById('testResult');

  if(!shop){ out.textContent = 'Select a shop'; return; }
  if(!shop.participating || !shop.published){ out.textContent = 'Selected shop is not active for check-in.'; return; }

  out.textContent = 'Requesting device location...';
  if(!navigator.geolocation){ out.textContent = 'Geolocation not supported'; return; }

  navigator.geolocation.getCurrentPosition(async pos=>{
    const payload = {
      shop_id: shop.id,
      shop_latitude: shop.lat,
      shop_longitude: shop.lng,
      user_latitude: pos.coords.latitude,
      user_longitude: pos.coords.longitude,
      radius_meters: 500,
      is_participating: shop.participating,
      is_published: shop.published
    };

    out.textContent = 'Sending check-in test...';

    try{
      const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      const res = await fetch('/passport/check-in-test', {
        method: 'POST',
        headers: {
          'Content-Type':'application/json',
          'X-CSRF-TOKEN': token,
          'Accept':'application/json'
        },
        body: JSON.stringify(payload)
      });

      const json = await res.json();
      out.textContent = JSON.stringify(json, null, 2);
    }catch(err){
      out.textContent = 'Network error: ' + err.message;
    }
  }, err=>{
    out.textContent = 'Failed to get location: ' + (err.message || err.code);
  }, { enableHighAccuracy:true, timeout:10000 });
});

// Hero CTAs
document.getElementById('exploreBtn').addEventListener('click', ()=>{ location.href = '/foodPassport'; });
document.getElementById('trailBtn').addEventListener('click', ()=>{ alert('Food Trail Generator will be available when map services are enabled.'); });

</script>
</body>
</html>