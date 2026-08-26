<style>
    .login-required-modal[hidden] { display: none; }
    .login-required-modal { position: fixed; inset: 0; z-index: 10; display: grid; place-items: center; padding: 20px; background: rgba(46, 36, 32, .5); animation: login-required-fade-in .18s ease-out; }
    .login-required-dialog { width: min(430px, 100%); padding: 30px; border: 1px solid rgba(66, 43, 32, .16); border-radius: 16px; background: #fffdf9; box-shadow: 0 24px 70px rgba(46, 36, 32, .3); animation: login-required-rise-in .2s ease-out; }
    .login-required-mark { width: 42px; height: 42px; display: grid; place-items: center; margin-bottom: 18px; border-radius: 12px; color: #3f2a0d; background: rgba(200, 148, 50, .24); font-family: Georgia, serif; font-weight: 800; }
    .login-required-title { margin: 0 0 9px; color: #a33a2d; font-family: Georgia, serif; font-size: 1.55rem; line-height: 1.2; }
    .login-required-message { margin: 0 0 22px; color: #7b6a60; line-height: 1.55; }
    .login-required-actions { display: flex; flex-wrap: wrap; gap: 9px; }
    .login-required-button { flex: 1 1 150px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; padding: 0 15px; border: 1px solid transparent; border-radius: 10px; font: inherit; font-weight: 800; text-decoration: none; cursor: pointer; }
    .login-required-button--primary { color: #3f2a0d; background: #c89432; }
    .login-required-button--primary:hover { background: #b98529; }
    .login-required-button--secondary { border-color: rgba(66, 43, 32, .12); color: #2e2420; background: #fff; }
    .login-required-button--secondary:hover { background: #f8f3ed; }
    @keyframes login-required-fade-in { from { opacity: 0; } to { opacity: 1; } }
    @keyframes login-required-rise-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="login-required-modal" data-login-modal role="dialog" aria-modal="true" aria-labelledby="login-required-title" hidden>
    <div class="login-required-dialog">
        <div class="login-required-mark" aria-hidden="true">W</div>
        <h2 class="login-required-title" id="login-required-title">Login Required</h2>
        <p class="login-required-message" data-login-message>Please sign in with Google to access this feature.</p>
        <div class="login-required-actions">
            <a class="login-required-button login-required-button--primary" href="{{ route('auth.google') }}">Continue with Google</a>
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