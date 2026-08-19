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
    body.modal-open{overflow:hidden}
    .badge-modal[hidden]{display:none}
    .badge-modal{position:fixed;inset:0;z-index:1000;display:grid;place-items:center;padding:20px}
    .badge-modal-backdrop{position:absolute;inset:0;background:rgba(47,37,31,.56);backdrop-filter:blur(4px)}
    .badge-modal-card{position:relative;width:min(100%,480px);max-height:calc(100vh - 40px);overflow:auto;padding:30px 26px 24px;border-radius:24px;background:#fff;border:1px solid rgba(212,160,23,.3);box-shadow:0 28px 70px rgba(47,37,31,.25);text-align:center}
    .badge-modal-close{position:absolute;top:12px;right:14px;width:34px;height:34px;border:0;border-radius:50%;background:rgba(140,31,31,.08);color:var(--primary);font-size:1.35rem;cursor:pointer}
    .badge-modal-icon{display:grid;place-items:center;width:76px;height:76px;margin:4px auto 14px;border-radius:24px;background:linear-gradient(135deg,rgba(212,160,23,.24),rgba(140,31,31,.1));color:var(--primary);font-size:2rem;font-weight:800}
    .badge-modal-card h2{margin:0 0 8px;color:var(--primary);font-family:Georgia,serif;font-size:2rem}
    .badge-modal-card p{margin:0;color:var(--muted);line-height:1.6}
    .badge-modal-badge-name{margin:12px 0 6px!important;color:var(--ink)!important;font-family:Georgia,serif;font-size:1.45rem;font-weight:700}
    .share-label{margin-top:22px!important;font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .share-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:12px}
    .share-btn{padding:11px 12px;border:1px solid rgba(140,31,31,.14);border-radius:11px;background:rgba(140,31,31,.05);color:var(--primary);font:inherit;font-weight:700;cursor:pointer}
    .share-status{min-height:24px;margin-top:12px!important;font-size:.82rem}
    .badge-modal-continue{width:100%;margin-top:16px;background:var(--primary);color:#fff;border:0;padding:11px 14px;border-radius:8px;font-weight:700;cursor:pointer}
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

      <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
        <button id="nameCheckBtn" class="check-btn" style="position:static">Check In</button>
        <button id="demoCheckBtn" class="check-btn" style="position:static;background:#F2E5D0">Use Demo Location</button>
        <button id="resetDemoBtn" class="check-btn" style="position:static;background:#f1ddd0">Reset Demo</button>
      </div>

      <div id="result" class="result">No check-in attempted.</div>
    </div>
  </div>

  <div id="badgeModal" class="badge-modal" hidden role="dialog" aria-modal="true" aria-labelledby="badgeModalTitle">
    <div class="badge-modal-backdrop" data-close-badge-modal></div>
    <div class="badge-modal-card">
      <button type="button" class="badge-modal-close" data-close-badge-modal aria-label="Close badge announcement">&times;</button>
      <div id="badgeModalIcon" class="badge-modal-icon">★</div>
      <h2 id="badgeModalTitle">Congratulations!</h2>
      <p>You have unlocked a new Heritage Passport badge.</p>
      <p id="badgeModalBadgeName" class="badge-modal-badge-name">Heritage Explorer</p>
      <p id="badgeModalBadgeDescription">Keep exploring and sharing the stories behind local food.</p>
      <p class="share-label">Would you like to share your achievement?</p>
      <div class="share-actions">
        <button type="button" class="share-btn" data-share="instagram">Instagram</button>
        <button type="button" class="share-btn" data-share="facebook">Facebook</button>
        <button type="button" class="share-btn" data-share="whatsapp">WhatsApp</button>
        <button type="button" class="share-btn" data-share="copy">Copy message</button>
      </div>
      <p id="shareStatus" class="share-status" aria-live="polite"></p>
      <button type="button" class="badge-modal-continue" data-close-badge-modal>Continue exploring</button>
    </div>
  </div>

<script>
(function(){
  const shop = {
    id: {{ $shop->id }},
    name: "{{ addslashes($shop->name) }}",
    latitude: @json($shop->lat),
    longitude: @json($shop->lng),
    participating: {{ $shop->participating ? 'true' : 'false' }},
    published: {{ $shop->published ? 'true' : 'false' }}
  };

  const resultEl = document.getElementById('result');
  const imgBtn = document.getElementById('imgCheckBtn');
  const nameBtn = document.getElementById('nameCheckBtn');
  const demoBtn = document.getElementById('demoCheckBtn');
  const resetBtn = document.getElementById('resetDemoBtn');
  const badgeModal = document.getElementById('badgeModal');
  const badgeModalIcon = document.getElementById('badgeModalIcon');
  const badgeModalBadgeName = document.getElementById('badgeModalBadgeName');
  const badgeModalBadgeDescription = document.getElementById('badgeModalBadgeDescription');
  const shareStatus = document.getElementById('shareStatus');
  let badgeShareText = '';
  let reloadAfterBadgeModal = false;

  function setResult(v){
    resultEl.textContent = typeof v === 'string'
      ? v
      : (v.message || 'We could not complete the check-in. Please try again.');
  }

  function openBadgeModal(unlockedBadges){
    const primaryBadge = unlockedBadges[0];
    const badgeNames = unlockedBadges.map(badge => badge.name).join(', ');
    const additionalBadges = unlockedBadges.length > 1
      ? ' Also unlocked: ' + unlockedBadges.slice(1).map(badge => badge.name).join(', ') + '.'
      : '';

    badgeModalIcon.textContent = primaryBadge.icon || '★';
    badgeModalBadgeName.textContent = primaryBadge.name;
    badgeModalBadgeDescription.textContent = (primaryBadge.description || 'Keep exploring local food heritage.') + additionalBadges;
    badgeShareText = 'I just unlocked the ' + badgeNames + ' badge' + (unlockedBadges.length > 1 ? 's' : '') + ' on the Warisan Makan Heritage Passport!';
    shareStatus.textContent = '';
    badgeModal.hidden = false;
    document.body.classList.add('modal-open');
    reloadAfterBadgeModal = true;
  }

  function closeBadgeModal(){
    badgeModal.hidden = true;
    document.body.classList.remove('modal-open');
    if(reloadAfterBadgeModal){
      reloadAfterBadgeModal = false;
      window.setTimeout(() => window.location.reload(), 250);
    }
  }

  async function copyShareText(){
    if(navigator.clipboard && navigator.clipboard.writeText){
      await navigator.clipboard.writeText(badgeShareText);
      return;
    }

    const helper = document.createElement('textarea');
    helper.value = badgeShareText;
    helper.setAttribute('readonly','');
    helper.style.position = 'fixed';
    helper.style.opacity = '0';
    document.body.appendChild(helper);
    helper.select();
    document.execCommand('copy');
    helper.remove();
  }

  async function shareBadge(channel){
    try{
      if(channel === 'copy'){
        await copyShareText();
        shareStatus.textContent = 'Badge message copied. You can paste it anywhere.';
        return;
      }
      if(channel === 'instagram'){
        await copyShareText();
        window.open('https://www.instagram.com/', '_blank', 'noopener');
        shareStatus.textContent = 'Message copied. Paste it into your Instagram post or story.';
        return;
      }
      if(channel === 'facebook'){
        window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href) + '&quote=' + encodeURIComponent(badgeShareText), '_blank', 'noopener');
        shareStatus.textContent = 'Facebook sharing opened in a new tab.';
        return;
      }
      if(channel === 'whatsapp'){
        window.open('https://wa.me/?text=' + encodeURIComponent(badgeShareText + ' ' + window.location.href), '_blank', 'noopener');
        shareStatus.textContent = 'WhatsApp sharing opened in a new tab.';
      }
    }catch(error){
      shareStatus.textContent = 'We could not prepare the share message. Please copy it manually.';
    }
  }

  document.querySelectorAll('[data-close-badge-modal]').forEach(element => element.addEventListener('click', closeBadgeModal));
  document.querySelectorAll('[data-share]').forEach(button => button.addEventListener('click', () => shareBadge(button.dataset.share)));

  async function sendCheckIn(payload){
    setResult('Sending check-in...');

    try{
      const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      const res = await fetch('/passport/check-in', {
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

      if (res.ok && json.success) {
        const newlyUnlockedBadges = Array.isArray(json.newly_unlocked_badges) ? json.newly_unlocked_badges : [];
        if(newlyUnlockedBadges.length > 0){
          openBadgeModal(newlyUnlockedBadges);
        }else{
          window.setTimeout(() => window.location.reload(), 900);
        }
      }
    }catch(err){
      setResult('Network or server error. Please try again.');
    }
  }

  async function doCheckIn(){
    if(!shop.participating || !shop.published){ setResult('Shop is not active for check-in.'); return; }
    if(!navigator.geolocation){ setResult('Geolocation is not supported by this browser.'); return; }

    setResult('Requesting device location...');
    navigator.geolocation.getCurrentPosition(pos=>{
      sendCheckIn({
        shop_id: shop.id,
        user_latitude: pos.coords.latitude,
        user_longitude: pos.coords.longitude,
      });
    }, err=>{ setResult('Unable to get your device location. Please allow location access and try again.'); }, { enableHighAccuracy:true, timeout:10000 });
  }

  function doDemoCheckIn(){
    if(!shop.participating || !shop.published){ setResult('Shop is not active for check-in.'); return; }
    if(shop.latitude === null || shop.longitude === null){ setResult('This shop does not have a demo location yet.'); return; }

    setResult('Using the demo location near this shop...');
    sendCheckIn({
      shop_id: shop.id,
      user_latitude: Number((Number(shop.latitude) + 0.0002).toFixed(6)),
      user_longitude: Number((Number(shop.longitude) + 0.0002).toFixed(6)),
      demo_mode: true
    });
  }

  async function resetDemo(){
    if(!window.confirm('Reset the demo passport and start again?')) return;

    setResult('Resetting the demo passport...');

    try{
      const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      const res = await fetch('/passport/reset-demo', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        }
      });
      const json = await res.json();
      setResult(json);

      if(res.ok && json.success){
        window.setTimeout(() => window.location.reload(), 700);
      }
    }catch(err){
      setResult('We could not reset the demo right now. Please try again.');
    }
  }

  imgBtn.addEventListener('click', doCheckIn);
  nameBtn.addEventListener('click', doCheckIn);
  demoBtn.addEventListener('click', doDemoCheckIn);
  resetBtn.addEventListener('click', resetDemo);
  document.getElementById('shopName').addEventListener('click', doCheckIn);
})();
</script>
</body>
</html>