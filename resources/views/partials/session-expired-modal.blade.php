@php
    $sessionExpiredLoginUrl = $sessionExpiredLoginUrl ?? $loginUrl ?? route('login');
    $sessionExpiredVisible = $visible ?? false;
@endphp

<style>
    .session-expired-modal[hidden] { display: none; }
    .session-expired-modal { position: fixed; inset: 0; z-index: 100; display: grid; place-items: center; padding: 20px; background: rgba(46, 36, 32, .56); }
    .session-expired-dialog { width: min(430px, 100%); padding: 30px; border: 1px solid rgba(66, 43, 32, .16); border-radius: 16px; background: #fffdf9; box-shadow: 0 24px 70px rgba(46, 36, 32, .3); }
    .session-expired-title { margin: 0 0 10px; color: #a33a2d; font-family: Georgia, serif; font-size: 1.55rem; line-height: 1.2; }
    .session-expired-message { margin: 0 0 22px; color: #7b6a60; line-height: 1.55; }
    .session-expired-button { width: 100%; min-height: 46px; border: 0; border-radius: 10px; color: #fff; background: #a33a2d; font: inherit; font-weight: 800; cursor: pointer; }
    .session-expired-button:hover { background: #872f25; }
</style>

<div class="session-expired-modal" data-session-expired-modal role="dialog" aria-modal="true" aria-labelledby="session-expired-title" aria-describedby="session-expired-message" @if (! $sessionExpiredVisible) hidden @endif>
    <div class="session-expired-dialog">
        <h2 class="session-expired-title" id="session-expired-title">{{ __('Session Expired') }}</h2>
        <p class="session-expired-message" id="session-expired-message">{{ __('Your session has expired due to inactivity. Please sign in again to continue.') }}</p>
        <button class="session-expired-button" data-session-expired-ok type="button">{{ __('OK') }}</button>
    </div>
</div>

<script>
    (() => {
        const modal = document.querySelector('[data-session-expired-modal]');
        const okButton = modal?.querySelector('[data-session-expired-ok]');
        if (!modal || !okButton) return;

        const showSessionExpired = (loginUrl) => {
            if (modal.dataset.sessionExpiredShown === 'true') return;
            modal.dataset.sessionExpiredShown = 'true';
            modal.dataset.loginUrl = loginUrl || @json($sessionExpiredLoginUrl);
            modal.hidden = false;
            okButton.focus();
        };

        okButton.addEventListener('click', () => {
            if (modal.dataset.sessionExpiredRedirecting === 'true') return;
            modal.dataset.sessionExpiredRedirecting = 'true';
            window.location.assign(modal.dataset.loginUrl || @json($sessionExpiredLoginUrl));
        });

        window.showSessionExpired = showSessionExpired;

        if (@json($sessionExpiredVisible)) showSessionExpired(@json($sessionExpiredLoginUrl));

        if (! window.__sessionExpiredFetchWrapped && window.fetch) {
            window.__sessionExpiredFetchWrapped = true;
            const originalFetch = window.fetch.bind(window);
            window.fetch = async function () {
                const response = await originalFetch.apply(window, arguments);
                if (response.status === 401) {
                    try {
                        const payload = await response.clone().json();
                        if (payload.session_expired) showSessionExpired(@json($sessionExpiredLoginUrl));
                    } catch {
                        // Leave non-session-expiry 401 responses unchanged.
                    }
                }
                return response;
            };
        }
    })();
</script>
