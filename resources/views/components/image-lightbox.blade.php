<div class="image-lightbox" data-image-lightbox hidden role="dialog" aria-modal="true" aria-label="{{ __('Enlarged food image') }}">
    <button class="image-lightbox__backdrop" type="button" data-image-lightbox-close aria-label="{{ __('Close enlarged image') }}"></button>
    <figure class="image-lightbox__content">
        <button class="image-lightbox__close" type="button" data-image-lightbox-close aria-label="{{ __('Close enlarged image') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
        </button>
        <img data-image-lightbox-image alt="">
        <figcaption data-image-lightbox-caption></figcaption>
    </figure>
</div>

@once
    @push('styles')
        <style>
            .enlargeable-image { position:relative; display:block; width:100%; padding:0; overflow:hidden; border:0; background:transparent; color:inherit; cursor:zoom-in; text-align:inherit; }
            .enlargeable-image:focus-visible { outline:3px solid var(--wm-gold, #c89432); outline-offset:3px; }
            .enlargeable-image__hint { position:absolute; right:10px; bottom:10px; display:grid; width:36px; height:36px; place-items:center; border:1px solid rgba(255,255,255,.5); border-radius:50%; color:#fff; background:rgba(43,25,21,.78); opacity:.9; transition:transform .18s ease, opacity .18s ease; pointer-events:none; }
            .enlargeable-image__hint svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
            .enlargeable-image:hover .enlargeable-image__hint { transform:scale(1.06); opacity:1; }
            .image-lightbox[hidden] { display:none; }
            .image-lightbox { position:fixed; z-index:1300; inset:0; display:grid; place-items:center; padding:22px; }
            .image-lightbox__backdrop { position:absolute; inset:0; width:100%; height:100%; border:0; background:rgba(20,12,10,.92); cursor:zoom-out; }
            .image-lightbox__content { position:relative; z-index:1; display:grid; width:min(1100px,100%); max-height:calc(100dvh - 44px); place-items:center; margin:0; }
            .image-lightbox__content img { display:block; max-width:100%; max-height:calc(100dvh - 105px); border-radius:12px; object-fit:contain; box-shadow:0 24px 70px rgba(0,0,0,.4); }
            .image-lightbox__content figcaption { margin-top:10px; color:#fff; text-align:center; }
            .image-lightbox__close { position:absolute; z-index:2; top:10px; right:10px; display:grid; width:46px; height:46px; place-items:center; border:1px solid rgba(255,255,255,.45); border-radius:50%; color:#fff; background:rgba(43,25,21,.82); cursor:pointer; }
            .image-lightbox__close svg { width:24px; height:24px; fill:none; stroke:currentColor; stroke-width:2.4; stroke-linecap:round; }
            .image-lightbox button:focus-visible { outline:3px solid #f2d37b; outline-offset:3px; }
            body.image-lightbox-open { overflow:hidden; }
            @media (max-width:620px) { .image-lightbox { padding:12px; } .image-lightbox__content img { max-height:calc(100dvh - 85px); } }
            @media (prefers-reduced-motion:reduce) { .enlargeable-image__hint { transition:none; } }
        </style>
    @endpush

    @push('scripts')
        <script>
            (() => {
                const lightbox = document.querySelector('[data-image-lightbox]');
                if (!lightbox) return;

                const image = lightbox.querySelector('[data-image-lightbox-image]');
                const caption = lightbox.querySelector('[data-image-lightbox-caption]');
                const closeButton = lightbox.querySelector('.image-lightbox__close');
                let opener = null;

                const close = () => {
                    if (lightbox.hidden) return;
                    lightbox.hidden = true;
                    document.body.classList.remove('image-lightbox-open');
                    image.removeAttribute('src');
                    opener?.focus();
                };

                document.addEventListener('click', (event) => {
                    const trigger = event.target.closest('[data-image-enlarge]');
                    if (!trigger) return;

                    const src = trigger.dataset.imageSrc || '';
                    if (!src) return;

                    const alt = trigger.dataset.imageAlt || '';
                    opener = trigger;
                    image.src = src;
                    image.alt = alt;
                    caption.textContent = alt;
                    lightbox.hidden = false;
                    document.body.classList.add('image-lightbox-open');
                    closeButton?.focus();
                });

                lightbox.querySelectorAll('[data-image-lightbox-close]').forEach((button) => button.addEventListener('click', close));
                lightbox.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') close();
                    if (event.key === 'Tab') {
                        event.preventDefault();
                        closeButton?.focus();
                    }
                });
            })();
        </script>
    @endpush
@endonce
