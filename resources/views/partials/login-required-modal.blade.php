<style>
    .login-required-modal[hidden] { display: none; }
    .login-required-modal { position: fixed; inset: 0; z-index: 10; display: grid; place-items: center; padding: 20px; background: rgba(46, 36, 32, .5); animation: login-required-fade-in .18s ease-out; }
    .login-required-dialog { width: min(430px, 100%); padding: 30px; border: 1px solid rgba(66, 43, 32, .16); border-radius: 16px; background: #fffdf9; box-shadow: 0 24px 70px rgba(46, 36, 32, .3); animation: login-required-rise-in .2s ease-out; }
    .login-required-title { margin: 0 0 9px; color: #a33a2d; font-family: Georgia, serif; font-size: 1.55rem; line-height: 1.2; }
    .login-required-message { margin: 0 0 22px; color: #7b6a60; line-height: 1.55; }
    .login-required-actions { display: flex; flex-wrap: wrap; gap: 9px; }
    .login-required-button { flex: 1 1 150px; min-height: 46px; display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 0 15px; border: 1px solid transparent; border-radius: 10px; font: inherit; font-weight: 800; text-decoration: none; cursor: pointer; transition: background-color .15s ease, box-shadow .15s ease, transform .1s ease; }
    .login-required-button--secondary { border-color: rgba(66, 43, 32, .12); color: #2e2420; background: #fff; }
    .login-required-button--secondary:hover { background: #f8f3ed; }
    .login-required-mark {width: 64px; height: 64px; display: grid; place-items: center; margin-bottom: 18px;}
    .login-required-logo {width: 100%; height: 100%; object-fit: contain; display: block;}

    /* Google button */
    .login-required-button--google {
        border-color: #dcdcdc;
        color: #3c4043;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(60, 64, 67, .12), 0 1px 2px rgba(60, 64, 67, .1);
    }
    .login-required-button--google:hover {
        background: #f8f9fa;
        box-shadow: 0 2px 6px rgba(60, 64, 67, .18), 0 1px 3px rgba(60, 64, 67, .12);
    }
    .login-required-button--google:active {
        transform: translateY(1px);
        box-shadow: 0 1px 2px rgba(60, 64, 67, .1);
    }
    .login-required-button--google svg { flex-shrink: 0; }

    @keyframes login-required-fade-in { from { opacity: 0; } to { opacity: 1; } }
    @keyframes login-required-rise-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="login-required-modal" data-login-modal role="dialog" aria-modal="true" aria-labelledby="login-required-title" hidden>
    <div class="login-required-dialog">
        <div class="login-required-mark" aria-hidden="true">
            <img
                src="{{ asset('images/warisan-makan-logo.png') }}"
                alt=""
                class="login-required-logo"
            >
        </div>
        <h2 class="login-required-title" id="login-required-title">Login Required</h2>
        <p class="login-required-message" data-login-message>Please sign in with Google to access this feature.</p>
        <div class="login-required-actions">
            <a class="login-required-button login-required-button--google" href="{{ route('auth.google') }}">
                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.7-1.57 2.68-3.88 2.68-6.62z"/>
                    <path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.81.54-1.84.86-3.04.86-2.34 0-4.32-1.58-5.03-3.71H.96v2.33A9 9 0 0 0 9 18z"/>
                    <path fill="#FBBC05" d="M3.97 10.71a5.4 5.4 0 0 1 0-3.42V4.96H.96a9 9 0 0 0 0 8.08l3.01-2.33z"/>
                    <path fill="#EA4335" d="M9 3.58c1.32 0 2.51.45 3.44 1.35l2.58-2.58C13.46.89 11.43 0 9 0A9 9 0 0 0 .96 4.96l3.01 2.33C4.68 5.16 6.66 3.58 9 3.58z"/>
                </svg>
                Continue with Google
            </a>
            <button class="login-required-button login-required-button--secondary" type="button" data-login-close>Cancel</button>
        </div>
    </div>
</div>

<script>
    (() => {
        const loginModal = document.querySelector('[data-login-modal]');
        const loginMessage = loginModal.querySelector('[data-login-message]');

        const openLoginModal = (message) => {
            loginMessage.textContent = message || 'Please sign in with Google to access this feature.';
            loginModal.hidden = false;
        };

        document.querySelectorAll('[data-login-required], [data-login-trigger]').forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                openLoginModal(trigger.dataset.loginMessage);
            });
        });

        loginModal.querySelector('[data-login-close]').addEventListener('click', () => {
            loginModal.hidden = true;
        });
        loginModal.addEventListener('click', (event) => {
            if (event.target === loginModal) loginModal.hidden = true;
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') loginModal.hidden = true;
        });

        @if(session('login_required'))
            openLoginModal(@json(session('login_required')));
        @endif
    })();
</script>