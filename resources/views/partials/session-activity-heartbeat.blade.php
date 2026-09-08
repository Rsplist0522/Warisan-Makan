@auth
<script>
    (() => {
        if (window.__warisanSessionActivityHeartbeat) return;
        window.__warisanSessionActivityHeartbeat = true;

        const heartbeatUrl = @json(route('session.activity'));
        const csrfToken = @json(csrf_token());
        const loginUrl = @json(auth()->user()->isAdmin() ? route('admin.login') : route('login'));
        const inactivityTimeout = @json((int) (auth()->user()->isAdmin()
            ? config('session.admin_inactivity_timeout', 30)
            : config('session.user_inactivity_timeout', 30)));
        const heartbeatInterval = Math.min(60000, Math.max(10000, Math.floor(inactivityTimeout * 1000 / 3)));
        let lastHeartbeatAt = 0;
        let heartbeatInFlight = false;
        let sessionExpired = false;
        let navigationCheckInFlight = false;

        const sendHeartbeat = () => {
            if (document.hidden || heartbeatInFlight || sessionExpired) return;

            heartbeatInFlight = true;
            fetch(heartbeatUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((response) => {
                if (response.status === 401) {
                    sessionExpired = true;
                    const modal = document.querySelector('[data-session-expired-modal]');
                    if (modal?.hidden) {
                        response.clone().json().then((payload) => {
                            if (payload.session_expired && window.showSessionExpired) {
                                window.showSessionExpired(loginUrl);
                            }
                        }).catch(() => {});
                    }
                    return;
                }

                if (response.ok) lastHeartbeatAt = Date.now();
            }).catch(() => {
                // A later user activity can retry if the request failed transiently.
            }).finally(() => {
                heartbeatInFlight = false;
            });
        };

        const recordActivity = () => {
            if (document.hidden || sessionExpired) return;

            const now = Date.now();
            if (now - lastHeartbeatAt >= heartbeatInterval) sendHeartbeat();
        };

        const checkSessionBeforeNavigation = (continueNavigation) => {
            if (sessionExpired || navigationCheckInFlight) return;

            navigationCheckInFlight = true;
            fetch(heartbeatUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((response) => {
                if (response.status === 401) {
                    sessionExpired = true;
                    response.clone().json().then((payload) => {
                        if (payload.session_expired && window.showSessionExpired) {
                            window.showSessionExpired(loginUrl);
                        }
                    }).catch(() => {});
                    return;
                }

                if (response.ok) {
                    lastHeartbeatAt = Date.now();
                    continueNavigation();
                }
            }).catch(() => {
                // Leave the original action paused if the session check fails.
            }).finally(() => {
                navigationCheckInFlight = false;
            });
        };

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (link.target && link.target !== '_self' || link.hasAttribute('download')) return;

            const targetUrl = new URL(link.href, window.location.href);
            if (targetUrl.origin !== window.location.origin || targetUrl.hash && targetUrl.pathname === window.location.pathname) return;
            if (link.dataset.sessionNavigationChecked === 'true') {
                delete link.dataset.sessionNavigationChecked;
                return;
            }

            event.preventDefault();
            checkSessionBeforeNavigation(() => {
                link.dataset.sessionNavigationChecked = 'true';
                link.click();
            });
        }, true);

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || form.dataset.sessionNavigationChecked === 'true') {
                if (form instanceof HTMLFormElement) delete form.dataset.sessionNavigationChecked;
                return;
            }

            const targetUrl = new URL(form.action || window.location.href, window.location.href);
            if (targetUrl.origin !== window.location.origin || form.target && form.target !== '_self') return;

            event.preventDefault();
            checkSessionBeforeNavigation(() => {
                form.dataset.sessionNavigationChecked = 'true';
                form.requestSubmit();
            });
        }, true);

        ['pointermove', 'mousedown', 'keydown', 'scroll', 'wheel', 'touchstart', 'touchmove'].forEach((eventName) => {
            window.addEventListener(eventName, recordActivity, { passive: true });
        });
    })();
</script>
@endauth
