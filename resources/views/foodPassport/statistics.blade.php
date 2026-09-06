@extends('layouts.user')

@section('title', __('Passport Statistics & Achievements'))
@section('user-topbar-title', __('Food Passport'))
@section('user-topbar-subtitle', __('Track your progress and achievements.'))

@push('styles')
<style>
    .passport-page { max-width: 1180px; margin: 0 auto; padding: 30px 24px 50px; }
    .passport-page-header { display: grid; gap: 18px; margin-bottom: 24px; }
    .passport-title-panel { position: relative; overflow: hidden; padding: 26px; border: 1px solid rgba(86,59,48,.12); border-radius: 20px; background: linear-gradient(120deg,rgba(140,31,31,.06),transparent 50%),rgba(255,255,255,.36); box-shadow: 0 18px 38px rgba(47,37,31,.08); }
    .passport-actions-panel { display: grid; gap: 12px; padding: 18px 22px; border: 1px solid rgba(86,59,48,.12); border-radius: 20px; background: linear-gradient(120deg,rgba(140,31,31,.06),transparent 50%),rgba(255,255,255,.36); box-shadow: 0 18px 38px rgba(47,37,31,.08); }
    .passport-page-panel { margin-top: 18px; padding: 22px; border: 1px solid rgba(86,59,48,.12); border-radius: 20px; background: rgba(255,255,255,.46); box-shadow: 0 12px 28px rgba(47,37,31,.08); }
    .passport-page-header h1 { margin: 0 0 8px; color: #8C1F1F; font-family: Georgia, serif; font-size: clamp(2rem, 4vw, 3.2rem); }
    .passport-page-header p { margin: 0; color: #675B54; }
    .passport-page-links { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; }
    .passport-page-links a { min-width: 190px; padding: 12px 18px; border: 1px solid rgba(212,160,23,.24); border-radius: 12px; background: rgba(212,160,23,.09); color: #2F251F; font-weight: 700; text-align: center; text-decoration: none; }
    .passport-page-links a.active { background: linear-gradient(135deg,#8C1F1F,#6D1717); color: #fff; border-color: transparent; }
    .page-back-link { justify-self: center; display: inline-flex; min-height: 38px; align-items: center; padding: 0 14px; border: 1px solid transparent; border-radius: 10px; color: #3f2a0d; background: var(--wm-gold); font-size: .82rem; font-weight: 800; text-decoration: none; }
    .page-back-link:hover { background: #d7a548; }
    .passport-page-panel { margin-top: 18px; padding: 22px; }
    .passport-page-panel h2 { margin: 0; color: #2F251F; }
    .statistics-grid, .badge-grid { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: 12px; margin-top: 18px; }
    .statistics-card { padding: 18px 14px; border: 1px solid rgba(86,59,48,.12); border-radius: 14px; background: rgba(255,255,255,.72); text-align: center; }
    .statistics-card strong { display: block; color: #8C1F1F; font-size: 1.8rem; }
    .statistics-card span { color: #675B54; font-size: .78rem; font-weight: 800; text-transform: uppercase; }
    .progress-track { height: 12px; margin-top: 18px; overflow: hidden; border-radius: 999px; background: rgba(86,59,48,.1); }
    .progress-track div { height: 100%; background: linear-gradient(135deg,#8C1F1F,#D4A017); }
    .badge-grid { grid-template-columns: repeat(3,minmax(0,1fr)); }
    .badge-card { position: relative; display: flex; min-height: 276px; flex-direction: column; align-items: center; padding: 24px 18px 18px; overflow: hidden; border: 1px solid rgba(86,59,48,.12); border-radius: 16px; background: rgba(255,255,255,.78); text-align: center; box-shadow: 0 8px 18px rgba(47,37,31,.04); }
    .badge-card::before { position: absolute; top: 0; right: 0; left: 0; height: 4px; content: ''; background: linear-gradient(90deg,#8C1F1F,#D4A017); }
    .badge-card-shareable { cursor: pointer; transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease; }
    .badge-card-shareable:hover { transform: translateY(-3px); border-color: rgba(212,160,23,.65); box-shadow: 0 16px 28px rgba(86,59,48,.12); }
    .badge-crest { display: grid; width: 58px; height: 58px; place-items: center; margin: 0 auto 14px; border: 1px solid rgba(212,160,23,.28); border-radius: 18px; background: linear-gradient(135deg,rgba(212,160,23,.2),rgba(140,31,31,.08)); color: #8C1F1F; font-size: 1.5rem; }
    .badge-card h3 { min-height: 1.35em; margin: 0 0 8px; color: #2F251F; font-size: 1.08rem; }
    .badge-card h3 + p { min-height: 2.8em; }
    .badge-card p { margin: 0; color: #675B54; font-size: .83rem; line-height: 1.5; }
    .badge-status { display: inline-flex; margin-top: 14px !important; padding: 6px 10px; border-radius: 999px; background: rgba(62,108,79,.1); font-size: .76rem !important; font-weight: 800; }
    .badge-share-slot { width: 100%; min-height: 40px; margin-top: auto; padding-top: 14px; }
    .badge-share-button { width: 100%; min-height: 40px; border: 1px solid rgba(140,31,31,.2); border-radius: 10px; background: rgba(140,31,31,.07); color: #8C1F1F; font-weight: 800; cursor: pointer; }
    .badge-share-button:hover { background: #8C1F1F; color: #fff; }
    .badge-modal[hidden] { display:none; } .badge-modal { position:fixed; inset:0; z-index:1000; display:grid; place-items:center; padding:20px; } .badge-modal-backdrop { position:absolute; inset:0; background:rgba(47,37,31,.56); } .badge-modal-card { position:relative; width:min(100%,560px); padding:28px; border-radius:24px; background:#FFFDF9; text-align:center; } .badge-modal-close { position:absolute; top:12px; right:14px; border:0; background:transparent; color:#8C1F1F; font-size:1.4rem; cursor:pointer; } .achievement-card-preview { margin-top:18px; padding:24px; border-radius:22px; background:linear-gradient(135deg,#8C1F1F,#2E1815); color:#FBF6EE; } .achievement-card-icon { margin:15px auto; color:#F2D37B; font-size:3rem; } .achievement-card-title { color:#F2D37B; font-family:Georgia,serif; } .share-actions,.share-download-actions { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; margin-top:12px; } .share-btn,.share-download-btn { padding:11px; border:1px solid rgba(140,31,31,.14); border-radius:11px; background:rgba(140,31,31,.05); color:#8C1F1F; font-weight:700; cursor:pointer; } .badge-modal-continue { width:100%; margin-top:16px; padding:12px; border:0; border-radius:11px; background:#8C1F1F; color:#fff; font-weight:800; }
    @media (max-width:760px) { .passport-page { padding:22px 16px 40px; } .statistics-grid,.badge-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="passport-page">
    <div class="passport-page-header">
        <div class="passport-title-panel"><h1>{{ __('Passport Statistics & Achievements') }}</h1><p>{{ __('See how far your food heritage journey has taken you.') }}</p></div>
        <div class="passport-actions-panel">
        <nav class="passport-page-links" aria-label="{{ __('Passport pages') }}"><a href="{{ route('passport.history') }}">{{ __('View Visit History') }}</a><a class="active" href="{{ route('passport.statistics') }}">{{ __('Passport Statistics & Achievements') }}</a><a href="{{ route('passport.leaderboard') }}">{{ __('Leaderboard') }}</a></nav>
        <a class="page-back-link" href="{{ route('passport.index') }}">{{ __('Back to Food Passport') }}</a>
        </div>
    </div>
    <section class="passport-page-panel"><h2>{{ __('Your progress') }}</h2><div class="statistics-grid"><div class="statistics-card"><strong>{{ $stats['visited'] ?? 0 }}</strong><span>{{ __('Visited shops') }}</span></div><div class="statistics-card"><strong>{{ $stats['completion'] ?? 0 }}%</strong><span>{{ __('Completion') }}</span></div><div class="statistics-card"><strong>{{ $stats['stamps'] ?? 0 }}</strong><span>{{ __('Stamps') }}</span></div><div class="statistics-card"><strong>{{ $stats['badges'] ?? 0 }}</strong><span>{{ __('Badges unlocked') }}</span></div></div><div class="progress-track"><div style="width:{{ min(100,max(0,(int)($stats['completion'] ?? 0))) }}%;"></div></div></section>
    <section class="passport-page-panel"><h2>{{ __('Passport achievements') }}</h2><div class="badge-grid">@foreach ($badges as $badge)<article class="badge-card {{ $badge['earned'] ? 'badge-card-shareable' : '' }}" @if($badge['earned']) role="button" tabindex="0" data-badge-share data-badge-name="{{ $badge['name'] }}" data-badge-description="{{ $badge['description'] }}" data-badge-icon="{{ $badge['icon'] }}" data-badge-progress="{{ $badge['progress'] }}" @endif><div class="badge-crest">{{ $badge['icon'] }}</div><h3>{{ __($badge['name']) }}</h3><p>{{ __($badge['description']) }}</p><p class="badge-status" style="color:{{ $badge['earned'] ? '#3E6C4F' : '#675B54' }}">{{ $badge['earned'] ? __('Unlocked') : __('Need :count visits',['count'=>(int)$badge['threshold']]) }}</p><div class="badge-share-slot">@if($badge['earned'])<button type="button" class="badge-share-button" data-open-badge-share>{{ __('Share') }}</button>@endif</div></article>@endforeach</div></section>
</div>
<div id="badgeModal" class="badge-modal" hidden><div class="badge-modal-backdrop" data-close-badge-modal></div><div class="badge-modal-card"><button type="button" class="badge-modal-close" data-close-badge-modal>&times;</button><h2>{{ __('Achievement unlocked') }}</h2><p>{{ __('Save this moment and share your heritage-food journey.') }}</p><div class="achievement-card-preview"><div>{{ __('Warisan Makan · Heritage Passport') }}</div><div id="badgeModalIcon" class="achievement-card-icon">★</div><h3 id="badgeModalBadgeName" class="achievement-card-title">{{ __('Your new badge') }}</h3><p id="badgeModalBadgeDescription">{{ __('The badge you unlock will appear here.') }}</p></div><p>{{ __('Share your achievement') }}</p><div class="share-actions"><button type="button" class="share-btn" data-share="instagram">{{ __('Prepare Instagram Story') }}</button><button type="button" class="share-btn" data-share="facebook">{{ __('Prepare Facebook Post') }}</button><button type="button" class="share-btn" data-share="whatsapp">{{ __('Share to WhatsApp') }}</button><button type="button" class="share-btn" data-share="copy">{{ __('Copy caption') }}</button></div><div class="share-download-actions"><button type="button" class="share-download-btn" data-share="download-square">{{ __('Download square card') }}</button><button type="button" class="share-download-btn" data-share="download-story">{{ __('Download story card') }}</button></div><p id="shareStatus"></p><button type="button" class="badge-modal-continue" data-close-badge-modal>{{ __('Continue exploring') }}</button></div></div>
@endsection

@push('scripts')
<script>
(() => { const modal=document.getElementById('badgeModal'); if(!modal)return; let badge=null,shareText=''; const icon=document.getElementById('badgeModalIcon'),name=document.getElementById('badgeModalBadgeName'),description=document.getElementById('badgeModalBadgeDescription'),status=document.getElementById('shareStatus'); const canvasFor=format=>{const c=document.createElement('canvas'),w=1080,h=format==='story'?1920:1080;c.width=w;c.height=h;const x=c.getContext('2d'),g=x.createLinearGradient(0,0,w,h);g.addColorStop(0,'#8C1F1F');g.addColorStop(1,'#2E1815');x.fillStyle=g;x.fillRect(0,0,w,h);x.textAlign='center';x.fillStyle='#F2D37B';x.font='110px Arial';x.fillText(badge.icon||'★',w/2,h*.38);x.font='700 58px Georgia';x.fillText(badge.name,w/2,h*.5);x.fillStyle='#FBF6EE';x.font='30px Arial';x.fillText('Every dish has a story. Discover yours.',w/2,h-120);return c;}; const download=f=>{const a=document.createElement('a');a.download='warisan-makan-badge-'+f+'.png';a.href=canvasFor(f).toDataURL('image/png');a.click();status.textContent=f==='story'?@json(__('Story card downloaded.')):@json(__('Square card downloaded.'));}; const copy=async()=>{if(navigator.clipboard)await navigator.clipboard.writeText(shareText);status.textContent=@json(__('Caption copied. Add it with your achievement card.'));}; const share=async c=>{if(c==='copy')return copy();if(c==='download-square')return download('square');if(c==='download-story')return download('story');if(c==='instagram'){await copy();download('story');window.open('https://www.instagram.com/','_blank');}if(c==='facebook'){await copy();download('square');window.open('https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(location.href)+'&quote='+encodeURIComponent(shareText),'_blank');}if(c==='whatsapp'){download('square');window.open('https://wa.me/?text='+encodeURIComponent(shareText),'_blank');}}; const open=card=>{badge={name:card.dataset.badgeName,description:card.dataset.badgeDescription,icon:card.dataset.badgeIcon,progress:card.dataset.badgeProgress};icon.textContent=badge.icon||'★';name.textContent=badge.name;description.textContent=badge.description;shareText='I just earned the '+badge.name+' badge on the Warisan Makan Heritage Passport.';modal.hidden=false;document.body.classList.add('modal-open');}; document.querySelectorAll('[data-badge-share]').forEach(card=>{card.onclick=e=>{if(!e.target.closest('[data-open-badge-share]'))open(card);};card.querySelector('[data-open-badge-share]')?.addEventListener('click',e=>{e.stopPropagation();open(card);});});document.querySelectorAll('[data-close-badge-modal]').forEach(e=>e.onclick=()=>{modal.hidden=true;document.body.classList.remove('modal-open');});document.querySelectorAll('[data-share]').forEach(e=>e.onclick=()=>share(e.dataset.share));})();
</script>
@endpush
