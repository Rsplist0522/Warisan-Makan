<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $shop->name }} — Check In</title>
  <style>
    :root{--primary:#8C1F1F;--bg:#FBF6EE;--ink:#32241F;--muted:#7A6A63;--accent:#D4A017}
    body{margin:0;font-family:Inter, system-ui, -apple-system, 'Segoe UI', Roboto, Arial;background:var(--bg);color:var(--ink);padding:20px}
    .card{background:white;padding:16px;border-radius:12px;max-width:720px;margin:0 auto}
    .shop-image{width:100%;height:320px;border-radius:10px;background-size:cover;background-position:center;display:block}
    .shop-title{font-family: Georgia, serif;font-size:1.4rem;color:var(--primary);margin-top:12px}
    .shop-sub{color:var(--muted);margin-top:6px}
    .check-overlay{position:relative}
    .check-btn{position:absolute;right:14px;bottom:14px;background:var(--accent);border:none;padding:10px 14px;border-radius:8px;font-weight:700;color:#2b1b10}
    .info{margin-top:12px}
    .muted{color:var(--muted)}
    .result{margin-top:12px;padding:10px;background:#FBF6EE;border-radius:8px;color:var(--muted)}
    @media(min-width:800px){body{padding:40px}.card{padding:28px}}
  </style>
</head>
<body>
  <div class="card">
    <div class="check-overlay">
      <div id="shopImage" class="shop-image" style="background-image:url('{{ $shop->image }}')"></div>
      <button id="imgCheckBtn" class="check-btn">Check In</button>
    </div>

    <div>
      <div id="shopName" class="shop-title">{{ $shop->name }}</div>
      <div class="shop-sub">{{ $shop->founder }}</div>
      <div class="info muted">Tap the shop name or the image's "Check In" button to start location verification. You don't need to type anything.</div>

      <div style="margin-top:12px">
        <button id="nameCheckBtn" class="check-btn" style="position:static">Check In</button>
      </div>

      <div id="result" class="result">No check-in attempted.</div>
    </div>
  </div>

<script>
(function(){
  const shop = {
    id: {{ $shop->id }},
    name: "{{ addslashes($shop->name) }}",
    lat: {{ $shop->lat }},
    lng: {{ $shop->lng }},
    participating: {{ $shop->participating ? 'true' : 'false' }},
    published: {{ $shop->published ? 'true' : 'false' }}
  };

  const resultEl = document.getElementById('result');
  const imgBtn = document.getElementById('imgCheckBtn');
  const nameBtn = document.getElementById('nameCheckBtn');

  function setResult(v){ resultEl.textContent = typeof v === 'string' ? v : JSON.stringify(v, null, 2); }

  async function doCheckIn(){
    if(!shop.participating || !shop.published){ setResult('Shop is not active for check-in.'); return; }
    if(!navigator.geolocation){ setResult('Geolocation not supported by your browser.'); return; }

    setResult('Requesting device location...');
    navigator.geolocation.getCurrentPosition(async pos=>{
      const payload = {
        shop_id: shop.id,
        shop_latitude: shop.lat,
        shop_longitude: shop.lng,
        user_latitude: pos.coords.latitude,
        user_longitude: pos.coords.longitude,
        radius_meters: 200,
        is_participating: shop.participating,
        is_published: shop.published
      };

      setResult('Sending check-in...');
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
        setResult(json);
      }catch(err){
        setResult('Network or server error: ' + err.message);
      }

    }, err=>{ setResult('Failed to get device location: ' + (err.message || err.code)); }, { enableHighAccuracy:true, timeout:10000 });
  }

  imgBtn.addEventListener('click', doCheckIn);
  nameBtn.addEventListener('click', doCheckIn);
  document.getElementById('shopName').addEventListener('click', doCheckIn);
})();
</script>
</body>
</html>