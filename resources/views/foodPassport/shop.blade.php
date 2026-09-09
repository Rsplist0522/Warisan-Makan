@extends('layouts.user')

@section('title', $shop->name.' - Check In')
@section('user-topbar-title', __('Food Passport'))
@section('user-topbar-subtitle', __('Check in to a heritage shop and unlock passport progress.'))

@section('user-topbar-actions')
<a class="user-topbar-link" href="{{ route('passport.index') }}">{{ __('Food Passport') }}</a>
<a class="user-topbar-link" href="{{ route('heritage-shops.index') }}">{{ __('Heritage Shops') }}</a>
@endsection

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    :root{--primary:#8C1F1F;--bg:#FBF6EE;--ink:#32241F;--muted:#7A6A63;--accent:#D4A017}
    body{margin:0;font-family:Inter, system-ui, -apple-system, 'Segoe UI', Roboto, Arial;background:var(--bg);color:var(--ink)}
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
    .badge-modal-card{position:relative;width:min(100%,560px);max-height:calc(100vh - 40px);overflow:auto;padding:30px 26px 24px;border-radius:24px;background:#fff;border:1px solid rgba(212,160,23,.3);box-shadow:0 28px 70px rgba(47,37,31,.25);text-align:center}
    .badge-modal-close{position:absolute;top:12px;right:14px;width:34px;height:34px;border:0;border-radius:50%;background:rgba(140,31,31,.08);color:var(--primary);font-size:1.35rem;cursor:pointer}
    .badge-modal-card h2{margin:0 0 8px;color:var(--primary);font-family:Georgia,serif;font-size:2rem}
    .badge-modal-card p{margin:0;color:var(--muted);line-height:1.6}
    .achievement-card-preview{position:relative;overflow:hidden;min-height:280px;margin-top:18px;padding:24px;border-radius:22px;background:#2E1815;color:#FBF6EE;text-align:left;box-shadow:0 18px 30px rgba(86,59,48,.18)}
    .achievement-card-preview:before{position:absolute;inset:0;content:'';pointer-events:none;background:radial-gradient(circle at 88% 8%,rgba(212,160,23,.3),transparent 32%),linear-gradient(135deg,#8C1F1F 0%,#4A211C 58%,#2E1815 100%)}
    .achievement-card-preview:after{position:absolute;inset:12px;content:'';pointer-events:none;border:1px solid rgba(242,211,123,.42);border-radius:15px}
    .achievement-card-kicker,.achievement-card-footer{position:relative;z-index:1;color:rgba(251,246,238,.72);font-size:.7rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
    .achievement-card-main{position:relative;z-index:1;display:grid;grid-template-columns:86px minmax(0,1fr);gap:16px;align-items:center;margin:30px 0 24px}
    .achievement-card-icon{display:grid;place-items:center;width:82px;height:82px;border:2px solid #F2D37B;border-radius:50%;background:#8C1F1F;color:#F2D37B;font-size:2.4rem;font-weight:800;box-shadow:0 0 0 7px rgba(242,211,123,.1)}
    .achievement-card-title{margin:0 0 6px;color:#F2D37B;font-family:Georgia,serif;font-size:clamp(1.35rem,4vw,2rem)}
    .achievement-card-description{margin:0;color:rgba(251,246,238,.9)!important;font-size:.88rem}
    .achievement-card-progress{position:relative;z-index:1;display:inline-flex;margin-bottom:18px;padding:7px 11px;border:1px solid rgba(212,160,23,.38);border-radius:999px;color:#F2D37B;font-size:.78rem;font-weight:800}
    .share-label{margin-top:22px!important;font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .share-actions,.share-download-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:12px}
    .share-download-actions{margin-top:10px}
    .share-btn,.share-download-btn{padding:11px 12px;border:1px solid rgba(140,31,31,.14);border-radius:11px;background:rgba(140,31,31,.05);color:var(--primary);font:inherit;font-weight:700;cursor:pointer}
    .share-btn.active,.share-download-btn.active{background:linear-gradient(135deg,var(--primary),#6D1717);border-color:transparent;color:#fff;box-shadow:0 10px 18px rgba(140,31,31,.18)}
    .share-download-btn{border-color:rgba(212,160,23,.38);background:rgba(212,160,23,.1);font-size:.82rem}
    .share-download-btn:first-child{background:var(--primary);border-color:var(--primary);color:#fff}
    .share-status{min-height:24px;margin-top:12px!important;font-size:.82rem}
    .share-note{margin-top:12px!important;color:var(--muted);font-size:.76rem}
    .badge-modal-continue{width:100%;margin-top:16px;background:var(--primary);color:#fff;border:0;padding:11px 14px;border-radius:8px;font-weight:700;cursor:pointer}
    @media(min-width:800px){.card{padding:28px}}
  </style>
@endpush

@section('content')
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
      </div>

      <div id="result" class="result">No check-in attempted.</div>
    </div>
  </div>

  <div id="badgeModal" class="badge-modal" hidden role="dialog" aria-modal="true" aria-labelledby="badgeModalTitle">
    <div class="badge-modal-backdrop" data-close-badge-modal></div>
    <div class="badge-modal-card">
      <button type="button" class="badge-modal-close" data-close-badge-modal aria-label="Close badge announcement">&times;</button>
      <h2 id="badgeModalTitle">Achievement unlocked</h2>
      <p>Save this moment and share your heritage-food journey.</p>
      <div class="achievement-card-preview">
        <div class="achievement-card-kicker">Warisan Makan · Heritage Passport</div>
        <div class="achievement-card-main">
          <div id="badgeModalIcon" class="achievement-card-icon">★</div>
          <div>
            <h3 id="badgeModalBadgeName" class="achievement-card-title">Your new badge</h3>
            <p id="badgeModalBadgeDescription" class="achievement-card-description">The badge you unlock will appear here.</p>
          </div>
        </div>
        <div id="badgeModalProgress" class="achievement-card-progress">Your milestone will appear here</div>
        <div class="achievement-card-footer">Every dish has a story. Discover yours.</div>
      </div>
      <p class="share-label">Share your achievement</p>
      <div class="share-actions">
        <button type="button" class="share-btn" data-share="instagram">Prepare Instagram Story</button>
        <button type="button" class="share-btn" data-share="facebook">Prepare Facebook Post</button>
        <button type="button" class="share-btn" data-share="whatsapp">Share to WhatsApp</button>
        <button type="button" class="share-btn" data-share="copy">Copy caption</button>
      </div>
      <div class="share-download-actions">
        <button type="button" class="share-download-btn" data-share="download-square">Download square card</button>
        <button type="button" class="share-download-btn" data-share="download-story">Download story card</button>
      </div>
      <p id="shareStatus" class="share-status" aria-live="polite"></p>
      <p class="share-note">On supported phones, choose Instagram, Facebook, or WhatsApp from the system share sheet. Desktop browsers download the card for manual upload.</p>
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
  const badgeModal = document.getElementById('badgeModal');
  const badgeModalIcon = document.getElementById('badgeModalIcon');
  const badgeModalBadgeName = document.getElementById('badgeModalBadgeName');
  const badgeModalBadgeDescription = document.getElementById('badgeModalBadgeDescription');
  const badgeModalProgress = document.getElementById('badgeModalProgress');
  const shareStatus = document.getElementById('shareStatus');
  let activeBadgeForShare = null;
  let badgeShareText = '';
  let reloadAfterBadgeModal = false;

  function setResult(v){
    resultEl.textContent = typeof v === 'string'
      ? v
      : (v.message || 'We could not complete the check-in. Please try again.');
  }

  function openBadgeModal(unlockedBadges, refreshOnClose = false){
    const validBadges = (Array.isArray(unlockedBadges) ? unlockedBadges : [])
      .filter(badge => badge && badge.name);

    if(!validBadges.length) return;

    const orderedBadges = [...validBadges].sort((first, second) =>
      Number(second.threshold || 0) - Number(first.threshold || 0)
    );
    const primaryBadge = orderedBadges[0];
    const badgeNames = orderedBadges.map(badge => badge.name).join(', ');
    const additionalBadges = orderedBadges.length > 1
      ? ' Also unlocked: ' + orderedBadges.slice(1).map(badge => badge.name).join(', ') + '.'
      : '';
    const milestone = primaryBadge.threshold || primaryBadge.progress || 'new';

    activeBadgeForShare = primaryBadge;
    badgeModalIcon.textContent = primaryBadge.icon || '★';
    badgeModalBadgeName.textContent = primaryBadge.name;
    badgeModalBadgeDescription.textContent = (primaryBadge.description || 'Keep exploring local food heritage.') + additionalBadges;
    badgeModalProgress.textContent = primaryBadge.threshold || primaryBadge.progress
      ? 'Milestone reached: ' + milestone + ' heritage visits'
      : 'A new story added to my food journey';
    badgeShareText = 'I just earned the ' + badgeNames + ' badge on the Warisan Makan Heritage Passport after discovering ' + milestone + ' heritage food stories. What should I explore next?';
    shareStatus.textContent = '';
    badgeModal.hidden = false;
    document.body.classList.add('modal-open');
    reloadAfterBadgeModal = refreshOnClose;
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

  function wrapCanvasText(context, text, x, y, maxWidth, lineHeight, maxLines){
    const words = String(text || '').split(' ');
    let line = '';
    let lineCount = 0;

    words.forEach((word, index) => {
      const testLine = line + (line ? ' ' : '') + word;
      if(context.measureText(testLine).width > maxWidth && line){
        context.fillText(line, x, y + lineCount * lineHeight);
        line = word;
        lineCount += 1;
      }else{
        line = testLine;
      }
      if(index === words.length - 1 && lineCount < maxLines) context.fillText(line, x, y + lineCount * lineHeight);
    });
  }

  function drawAchievementCard(format){
    if(!activeBadgeForShare) return null;

    const width = 1080;
    const height = format === 'story' ? 1920 : 1080;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const context = canvas.getContext('2d');
    const badge = activeBadgeForShare;
    const story = format === 'story';
    const panelY = story ? 500 : 170;
    const panelHeight = story ? 980 : 740;
    const centerX = width / 2;

    context.fillStyle = '#2E1815';
    context.fillRect(0, 0, width, height);
    const gradient = context.createLinearGradient(0, 0, width, height);
    gradient.addColorStop(0, '#8C1F1F');
    gradient.addColorStop(.55, '#5B2820');
    gradient.addColorStop(1, '#2E1815');
    context.fillStyle = gradient;
    context.fillRect(34, 34, width - 68, height - 68);

    context.strokeStyle = 'rgba(242,211,123,.34)';
    context.lineWidth = 2;
    context.strokeRect(52, 52, width - 104, height - 104);
    context.beginPath();
    context.arc(width - 60, 100, 210, 0, Math.PI * 2);
    context.stroke();
    context.beginPath();
    context.arc(60, height - 90, 230, 0, Math.PI * 2);
    context.stroke();

    context.fillStyle = 'rgba(251,246,238,.78)';
    context.font = '800 26px Arial, sans-serif';
    context.fillText('WARISAN MAKAN  /  HERITAGE PASSPORT', 82, story ? 130 : 112);
    context.fillStyle = '#F2D37B';
    context.font = '800 22px Arial, sans-serif';
    context.fillText(story ? 'A NEW CHAPTER IN YOUR FOOD JOURNEY' : 'ACHIEVEMENT UNLOCKED', 82, story ? 176 : 158);

    context.save();
    context.shadowColor = 'rgba(25,12,9,.3)';
    context.shadowBlur = 28;
    context.shadowOffsetY = 14;
    context.fillStyle = '#FBF6EE';
    context.fillRect(80, panelY, width - 160, panelHeight);
    context.restore();
    context.strokeStyle = 'rgba(212,160,23,.72)';
    context.lineWidth = 4;
    context.strokeRect(96, panelY + 16, width - 192, panelHeight - 32);

    const sealY = panelY + 150;
    context.fillStyle = '#8C1F1F';
    context.beginPath();
    context.arc(centerX, sealY, 92, 0, Math.PI * 2);
    context.fill();
    context.strokeStyle = '#D4A017';
    context.lineWidth = 8;
    context.stroke();

    context.textAlign = 'center';
    context.fillStyle = '#F2D37B';
    context.font = '800 92px Arial, sans-serif';
    context.fillText(badge.icon || '★', centerX, sealY + 32);
    context.fillStyle = '#8C1F1F';
    context.font = '800 22px Arial, sans-serif';
    context.fillText('MILESTONE UNLOCKED', centerX, panelY + 300);
    context.fillStyle = '#32241F';
    context.font = story ? '700 58px Georgia, serif' : '700 52px Georgia, serif';
    wrapCanvasText(context, badge.name, centerX, panelY + 380, width - 260, 68, 2);
    context.fillStyle = '#7A6A63';
    context.font = story ? '30px Arial, sans-serif' : '28px Arial, sans-serif';
    wrapCanvasText(context, badge.description || 'A new heritage-food milestone.', centerX, panelY + 530, width - 270, 42, 3);
    context.fillStyle = '#8C1F1F';
    context.fillStyle = '#F2D37B';
    context.font = '800 25px Arial, sans-serif';
    context.fillText(badge.progress ? 'HERITAGE VISITS  /  ' + badge.progress : 'A NEW STORY ADDED TO MY FOOD JOURNEY', centerX, panelY + panelHeight - 92);

    context.fillStyle = 'rgba(251,246,238,.9)';
    context.font = story ? '30px Arial, sans-serif' : '28px Arial, sans-serif';
    context.fillText('Every dish has a story. Discover yours.', centerX, height - (story ? 170 : 116));
    context.fillStyle = '#F2D37B';
    context.font = '22px Arial, sans-serif';
    context.fillText('warisan makan  /  explore local heritage food', centerX, height - (story ? 112 : 68));
    context.textAlign = 'start';

    return canvas;
  }

  async function downloadAchievementCard(format){
    const canvas = drawAchievementCard(format);
    if(!canvas) return false;

    const slug = (activeBadgeForShare.name || 'badge').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
    if(!blob) return false;

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
      ? 'Story card downloaded. Upload it to Instagram Story or WhatsApp Status.'
      : 'Square card downloaded. It is ready for your social feed.';
    return true;
  }

  async function shareAchievementFile(format){
    const canvas = drawAchievementCard(format);
    if(!canvas || !navigator.share || !navigator.canShare || typeof File === 'undefined') return false;

    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
    if(!blob) return false;

    const slug = (activeBadgeForShare.name || 'badge').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    const file = new File([blob], 'warisan-makan-' + slug + '-' + format + '.png', { type: 'image/png' });
    if(!navigator.canShare({ files: [file] })) return false;

    await navigator.share({
      files: [file],
      title: activeBadgeForShare.name + ' · Warisan Makan',
      text: badgeShareText
    });
    shareStatus.textContent = 'Achievement card shared successfully.';
    return true;
  }

  async function shareBadge(channel){
    try{
      if(channel === 'copy'){
        await copyShareText();
        shareStatus.textContent = 'Caption copied. Add it with your achievement card.';
        return;
      }
      if(channel === 'download-square'){
        await downloadAchievementCard('square');
        return;
      }
      if(channel === 'download-story'){
        await downloadAchievementCard('story');
        return;
      }
      if(channel === 'instagram'){
        shareStatus.textContent = 'Choose Instagram from your phone share sheet.';
        const isMobileDevice = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
        const instagramWindow = isMobileDevice ? null : window.open('', '_blank');

        if(isMobileDevice){
          try{
            if(await shareAchievementFile('story')) return;
          }catch(error){
            if(error && error.name === 'AbortError'){
              shareStatus.textContent = 'Sharing cancelled.';
              return;
            }
            console.warn('Native Instagram sharing was unavailable:', error);
          }
        }

        try{ await copyShareText(); }catch(error){ console.warn('Caption copy failed:', error); }
        try{ await downloadAchievementCard('story'); }catch(error){ console.warn('Story card download failed:', error); }
        if(instagramWindow) instagramWindow.location.href = 'https://www.instagram.com/';
        else window.location.assign('https://www.instagram.com/');
        shareStatus.textContent = 'Instagram opened. Upload the downloaded story card if native sharing was unavailable.';
        return;
      }
      if(channel === 'facebook'){
        shareStatus.textContent = 'Choose Facebook from your phone share sheet.';
        const isMobileDevice = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
        const facebookWindow = isMobileDevice ? null : window.open('', '_blank');

        if(isMobileDevice){
          try{
            if(await shareAchievementFile('square')) return;
          }catch(error){
            if(error && error.name === 'AbortError'){
              shareStatus.textContent = 'Sharing cancelled.';
              return;
            }
            console.warn('Native Facebook sharing was unavailable:', error);
          }
        }

        const facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href) + '&quote=' + encodeURIComponent(badgeShareText);
        try{ await copyShareText(); }catch(error){ console.warn('Caption copy failed:', error); }
        try{ await downloadAchievementCard('square'); }catch(error){ console.warn('Post card download failed:', error); }
        if(facebookWindow) facebookWindow.location.href = facebookUrl;
        else window.location.assign(facebookUrl);
        shareStatus.textContent = 'Facebook opened. Upload the downloaded post card if native sharing was unavailable.';
        return;
      }
      if(channel === 'whatsapp'){
        shareStatus.textContent = 'Preparing your WhatsApp achievement card...';
        const isMobileDevice = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
        const whatsappWindow = isMobileDevice ? null : window.open('', '_blank');

        if(isMobileDevice){
          try{
            if(await shareAchievementFile('square')) return;
          }catch(error){
            if(error && error.name === 'AbortError'){
              shareStatus.textContent = 'Sharing cancelled.';
              return;
            }
            console.warn('Native achievement sharing was unavailable:', error);
          }
        }

        const whatsappUrl = isMobileDevice
          ? 'https://wa.me/?text=' + encodeURIComponent(badgeShareText)
          : 'https://web.whatsapp.com/send?text=' + encodeURIComponent(badgeShareText);

        try{
          await downloadAchievementCard('square');
        }catch(error){
          console.warn('Achievement card download failed:', error);
        }

        try{
          await copyShareText();
        }catch(error){
          console.warn('Caption copy failed:', error);
        }

        if(whatsappWindow) whatsappWindow.location.href = whatsappUrl;
        else window.location.assign(whatsappUrl);
        shareStatus.textContent = isMobileDevice
          ? 'WhatsApp opened. Attach the downloaded card if it was not included automatically.'
          : 'WhatsApp Web opened. Attach the downloaded card to your message.';
      }
    }catch(error){
      if(error && error.name === 'AbortError'){
        shareStatus.textContent = 'Sharing cancelled.';
        return;
      }
      shareStatus.textContent = 'We could not prepare the share card. Please use the download buttons instead.';
    }
  }

  document.querySelectorAll('[data-close-badge-modal]').forEach(element => element.addEventListener('click', closeBadgeModal));
  function activateShareButton(button){
    const shareButtons = button.closest('.badge-modal-card')?.querySelectorAll('[data-share]') || [];
    shareButtons.forEach(item => item.classList.toggle('active', item === button));
  }

  document.querySelectorAll('[data-share]').forEach(button => {
    button.addEventListener('click', () => activateShareButton(button));
    button.addEventListener('click', () => shareBadge(button.dataset.share));
  });

  async function parseApiResponse(response){
    const contentType = response.headers.get('content-type') || '';
    const responseText = await response.text();

    if(contentType.includes('application/json')){
      try{
        return JSON.parse(responseText);
      }catch(error){
        // Fall through to a friendly message for malformed JSON.
      }
    }

    if(response.redirected || response.url.includes('/login')){
      return { success:false, message:'Please sign in before checking in to your heritage passport.' };
    }
    if(response.status === 401 || response.status === 403){
      return { success:false, message:'Please sign in before checking in to your heritage passport.' };
    }
    if(response.status === 419){
      return { success:false, message:'Your session expired. Refresh the page and sign in again before checking in.' };
    }
    if(response.status === 404 || response.status === 405){
      return { success:false, message:'The check-in service is not available. Please verify the Passport routes in web.php.' };
    }
    if(response.status >= 500){
      return { success:false, message:'The server could not complete your check-in. Please check the Laravel error log.' };
    }

    return { success:false, message:'The server returned an unexpected response. Please try again.' };
  }

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
      const json = await parseApiResponse(res);
      setResult(json);

      if (res.ok && json && json.success) {
        const newlyUnlockedBadges = Array.isArray(json.newly_unlocked_badges) ? json.newly_unlocked_badges : [];
        if(newlyUnlockedBadges.length > 0){
          openBadgeModal(newlyUnlockedBadges, true);
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

  imgBtn.addEventListener('click', doCheckIn);
  nameBtn.addEventListener('click', doCheckIn);
  document.getElementById('shopName').addEventListener('click', doCheckIn);
})();
</script>
@endsection
