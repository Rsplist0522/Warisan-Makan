@props([
    'images',
    'shopName',
    'imageService',
])

@php($galleryImages = collect($images)->values())

<section class="heritage-gallery" data-heritage-gallery aria-label="{{ __('Image gallery for :shop', ['shop' => $shopName]) }}">
    @if ($galleryImages->isEmpty())
        <div class="heritage-gallery__empty" role="img" aria-label="{{ __('No image available for :shop', ['shop' => $shopName]) }}">
            <span aria-hidden="true">◇</span>
            <strong>{{ __('Image unavailable') }}</strong>
        </div>
    @else
        <div class="heritage-gallery__stage">
            <button class="heritage-gallery__open" type="button" data-gallery-open aria-label="{{ __('Enlarge image') }}">
                <img
                    src="{{ $imageService->url($galleryImages->first()) }}"
                    alt="{{ __(':shop gallery image :number', ['shop' => $shopName, 'number' => 1]) }}"
                    width="1200"
                    height="800"
                    fetchpriority="high"
                    data-gallery-main
                >
                <span class="heritage-gallery__fallback" data-gallery-fallback hidden>{{ __('Image unavailable') }}</span>
            </button>

            @if ($galleryImages->count() > 1)
                <button class="heritage-gallery__nav heritage-gallery__nav--previous" type="button" data-gallery-previous aria-label="{{ __('Previous image') }}"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg></button>
                <button class="heritage-gallery__nav heritage-gallery__nav--next" type="button" data-gallery-next aria-label="{{ __('Next image') }}"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></button>
                <span class="heritage-gallery__counter" data-gallery-counter>1 / {{ $galleryImages->count() }}</span>
            @endif
        </div>

        @if ($galleryImages->count() > 1)
            <div class="heritage-gallery__thumbnails" data-gallery-thumbnails aria-label="{{ __('Choose gallery image') }}">
                @foreach ($galleryImages as $image)
                    <button
                        class="heritage-gallery__thumbnail {{ $loop->first ? 'is-selected' : '' }}"
                        type="button"
                        data-gallery-thumbnail
                        data-index="{{ $loop->index }}"
                        data-src="{{ $imageService->url($image) }}"
                        data-alt="{{ __(':shop gallery image :number', ['shop' => $shopName, 'number' => $loop->iteration]) }}"
                        aria-label="{{ __('View image :number of :total', ['number' => $loop->iteration, 'total' => $galleryImages->count()]) }}"
                        aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                    >
                        <img src="{{ $imageService->url($image) }}" alt="" width="112" height="76" loading="lazy" onerror="this.closest('button').classList.add('is-unavailable')">
                    </button>
                @endforeach
            </div>
        @else
            <span data-gallery-thumbnail data-index="0" data-src="{{ $imageService->url($galleryImages->first()) }}" data-alt="{{ __(':shop gallery image :number', ['shop' => $shopName, 'number' => 1]) }}" hidden></span>
        @endif

        <div class="heritage-lightbox" data-gallery-lightbox hidden role="dialog" aria-modal="true" aria-label="{{ __('Image viewer for :shop', ['shop' => $shopName]) }}">
            <button class="heritage-lightbox__backdrop" type="button" data-gallery-close aria-label="{{ __('Close image viewer') }}"></button>
            <div class="heritage-lightbox__content">
                <button class="heritage-lightbox__close" type="button" data-gallery-close aria-label="{{ __('Close image viewer') }}">×</button>
                @if ($galleryImages->count() > 1)
                    <button class="heritage-lightbox__nav heritage-lightbox__nav--previous" type="button" data-gallery-previous aria-label="{{ __('Previous image') }}"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg></button>
                    <button class="heritage-lightbox__nav heritage-lightbox__nav--next" type="button" data-gallery-next aria-label="{{ __('Next image') }}"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></button>
                @endif
                <img data-gallery-lightbox-image src="{{ $imageService->url($galleryImages->first()) }}" alt="{{ __(':shop gallery image :number', ['shop' => $shopName, 'number' => 1]) }}">
                <p><span data-gallery-lightbox-caption>{{ __(':shop gallery image :number', ['shop' => $shopName, 'number' => 1]) }}</span><span aria-hidden="true"> · </span><span data-gallery-lightbox-counter>1 / {{ $galleryImages->count() }}</span></p>
            </div>
        </div>
    @endif
</section>

@once
    @push('styles')
        <style>
            .heritage-gallery { min-width:0; }
            .heritage-gallery__stage { position:relative; overflow:hidden; border-radius:18px; background:#eadfd4; aspect-ratio:3/2; box-shadow:0 14px 28px rgba(52,31,23,.12); }
            .heritage-gallery__open { display:block; width:100%; height:100%; padding:0; border:0; background:transparent; cursor:zoom-in; }
            .heritage-gallery__open img { display:block; width:100%; height:100%; object-fit:cover; }
            .heritage-gallery__open:focus-visible, .heritage-gallery__thumbnail:focus-visible, .heritage-gallery__nav:focus-visible, .heritage-lightbox button:focus-visible { outline:3px solid #f2d37b; outline-offset:3px; }
            .heritage-gallery__fallback, .heritage-gallery__empty { width:100%; height:100%; min-height:280px; place-items:center; align-content:center; gap:8px; color:var(--wm-muted); background:linear-gradient(135deg,#f2e7da,#fbf7f1); text-align:center; }
            .heritage-gallery__fallback:not([hidden]), .heritage-gallery__empty { display:grid; }
            .heritage-gallery__empty span { color:var(--wm-gold); font-size:3rem; }
            .heritage-gallery__nav, .heritage-lightbox__nav, .heritage-lightbox__close { position:absolute; z-index:3; display:grid; width:46px; height:46px; place-items:center; border:1px solid rgba(255,255,255,.4); border-radius:50%; background:rgba(43,25,21,.78); color:#fff; font-size:1.8rem; cursor:pointer; }
            .heritage-gallery__nav svg, .heritage-lightbox__nav svg { display:block; width:24px; height:24px; fill:none; stroke:currentColor; stroke-width:2.5; stroke-linecap:round; stroke-linejoin:round; }
            .heritage-gallery__nav { top:50%; transform:translateY(-50%); }
            .heritage-gallery__nav--previous { left:12px; }
            .heritage-gallery__nav--next { right:12px; }
            .heritage-gallery__counter { position:absolute; right:12px; bottom:12px; padding:6px 10px; border-radius:999px; color:#fff; background:rgba(43,25,21,.78); font-size:.76rem; font-weight:800; }
            .heritage-gallery__thumbnails { display:flex; gap:9px; margin-top:11px; padding:3px 2px 8px; overflow-x:auto; overscroll-behavior-inline:contain; scrollbar-width:thin; }
            .heritage-gallery__thumbnail { flex:0 0 94px; height:66px; padding:3px; overflow:hidden; border:2px solid transparent; border-radius:11px; background:#fff; cursor:pointer; }
            .heritage-gallery__thumbnail img { width:100%; height:100%; border-radius:7px; object-fit:cover; }
            .heritage-gallery__thumbnail.is-selected { border-color:var(--wm-gold); box-shadow:0 0 0 2px rgba(200,148,50,.2); }
            .heritage-gallery__thumbnail.is-unavailable { opacity:.45; }
            .heritage-lightbox[hidden] { display:none; }
            .heritage-lightbox { position:fixed; z-index:1200; inset:0; display:grid; place-items:center; padding:22px; }
            .heritage-lightbox__backdrop { position:absolute; inset:0; width:100%; height:100%; border:0; background:rgba(20,12,10,.9); cursor:zoom-out; }
            .heritage-lightbox__content { position:relative; z-index:1; display:grid; width:min(1100px,100%); max-height:calc(100dvh - 44px); place-items:center; }
            .heritage-lightbox__content img { display:block; max-width:100%; max-height:calc(100dvh - 110px); border-radius:12px; object-fit:contain; }
            .heritage-lightbox__content p { margin:10px 0 0; color:#fff; text-align:center; }
            .heritage-lightbox__close { top:10px; right:10px; }
            .heritage-lightbox__nav { top:50%; transform:translateY(-50%); }
            .heritage-lightbox__nav--previous { left:10px; }
            .heritage-lightbox__nav--next { right:10px; }
            body.heritage-lightbox-open { overflow:hidden; }
            @media (max-width:620px) { .heritage-gallery__stage { border-radius:14px; } .heritage-gallery__nav { width:44px; height:44px; } .heritage-lightbox { padding:12px; } .heritage-lightbox__nav--previous { left:2px; } .heritage-lightbox__nav--next { right:2px; } }
        </style>
    @endpush

    @push('scripts')
        <script>
            (() => {
                document.querySelectorAll('[data-heritage-gallery]').forEach((gallery) => {
                    const sources = Array.from(gallery.querySelectorAll('[data-gallery-thumbnail]')).map((item) => ({ src: item.dataset.src, alt: item.dataset.alt }));
                    if (!sources.length) return;

                    const mainImage = gallery.querySelector('[data-gallery-main]');
                    const fallback = gallery.querySelector('[data-gallery-fallback]');
                    const lightbox = gallery.querySelector('[data-gallery-lightbox]');
                    const lightboxImage = gallery.querySelector('[data-gallery-lightbox-image]');
                    let index = 0;
                    let opener = null;
                    let touchStartX = null;

                    const render = (nextIndex) => {
                        index = (nextIndex + sources.length) % sources.length;
                        const selected = sources[index];
                        if (mainImage) { mainImage.hidden = false; mainImage.src = selected.src; mainImage.alt = selected.alt; }
                        if (fallback) fallback.hidden = true;
                        if (lightboxImage) { lightboxImage.src = selected.src; lightboxImage.alt = selected.alt; }
                        gallery.querySelectorAll('[data-gallery-counter], [data-gallery-lightbox-counter]').forEach((counter) => counter.textContent = (index + 1) + ' / ' + sources.length);
                        const caption = gallery.querySelector('[data-gallery-lightbox-caption]');
                        if (caption) caption.textContent = selected.alt;
                        gallery.querySelectorAll('[data-gallery-thumbnail]').forEach((thumbnail, thumbnailIndex) => {
                            const active = thumbnailIndex === index;
                            thumbnail.classList.toggle('is-selected', active);
                            if (thumbnail.matches('button')) thumbnail.setAttribute('aria-pressed', active ? 'true' : 'false');
                            if (active) thumbnail.scrollIntoView({ behavior:'smooth', block:'nearest', inline:'nearest' });
                        });
                    };

                    const move = (step) => render(index + step);
                    gallery.querySelectorAll('[data-gallery-previous]').forEach((button) => button.addEventListener('click', () => move(-1)));
                    gallery.querySelectorAll('[data-gallery-next]').forEach((button) => button.addEventListener('click', () => move(1)));
                    gallery.querySelectorAll('button[data-gallery-thumbnail]').forEach((button) => button.addEventListener('click', () => render(Number(button.dataset.index))));

                    mainImage?.addEventListener('error', () => { mainImage.hidden = true; if (fallback) fallback.hidden = false; });
                    lightboxImage?.addEventListener('error', () => { lightboxImage.alt = 'Image unavailable'; });

                    const close = () => {
                        if (!lightbox || lightbox.hidden) return;
                        lightbox.hidden = true;
                        document.body.classList.remove('heritage-lightbox-open');
                        opener?.focus();
                    };
                    gallery.querySelector('[data-gallery-open]')?.addEventListener('click', (event) => {
                        opener = event.currentTarget;
                        lightbox.hidden = false;
                        document.body.classList.add('heritage-lightbox-open');
                        gallery.querySelector('.heritage-lightbox__close')?.focus();
                    });
                    gallery.querySelectorAll('[data-gallery-close]').forEach((button) => button.addEventListener('click', close));

                    lightbox?.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape') close();
                        if (event.key === 'ArrowLeft') move(-1);
                        if (event.key === 'ArrowRight') move(1);
                        if (event.key === 'Tab') {
                            const controls = Array.from(lightbox.querySelectorAll('button:not([hidden])'));
                            const first = controls[0];
                            const last = controls[controls.length - 1];
                            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
                        }
                    });

                    [gallery.querySelector('.heritage-gallery__stage'), lightbox].filter(Boolean).forEach((surface) => {
                        surface.addEventListener('touchstart', (event) => { touchStartX = event.changedTouches[0]?.clientX ?? null; }, { passive:true });
                        surface.addEventListener('touchend', (event) => {
                            if (touchStartX === null) return;
                            const distance = (event.changedTouches[0]?.clientX ?? touchStartX) - touchStartX;
                            if (Math.abs(distance) > 45) move(distance < 0 ? 1 : -1);
                            touchStartX = null;
                        }, { passive:true });
                    });
                });
            })();
        </script>
    @endpush
@endonce
