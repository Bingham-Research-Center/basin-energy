/* Google OAuth: keep the parent BasinEnergy page open while a separate
 * browser window handles Google consent, terms and any optional 2FA.
 * Authentication is confirmed by Laravel's session status endpoint.
 */
(function () {
    'use strict';

    var active = null;
    var timer = null;
    var timeoutAt = 0;
    var runningCheck = false;
    var panel = null;
    var messageNode = null;
    var errorNode = null;
    var feedbackTimer = null;
    var MAX_WAIT_MS = 15 * 60 * 1000;

    function waitingPanel() {
        if (panel) return panel;
        panel = document.createElement('section');
        panel.className = 'google-oauth-waiting';
        panel.setAttribute('role', 'status');
        panel.setAttribute('aria-live', 'polite');
        panel.setAttribute('aria-atomic', 'true');
        panel.hidden = true;

        // Compact, wordless spinner while authorizing. Instructions remain
        // available to screen readers without interrupting the main page.
        panel.innerHTML =
            '<span class="google-oauth-indicator" aria-hidden="true"></span>' +
            '<span class="google-oauth-message google-oauth-sr-only"></span>' +
            '<span class="google-oauth-error" aria-hidden="true"></span>' +
            '<button type="button" class="google-oauth-cancel" ' +
                'aria-label="Cancel Google sign-in" title="Cancel Google sign-in">' +
                '<span aria-hidden="true">&times;</span></button>';

        document.body.appendChild(panel);
        messageNode = panel.querySelector('.google-oauth-message');
        errorNode = panel.querySelector('.google-oauth-error');
        panel.querySelector('.google-oauth-cancel').addEventListener('click', function () {
            closeChild();
            stop();
            update('', false);
        });
        return panel;
    }

    function update(message, visible, isError) {
        waitingPanel();
        if (feedbackTimer) {
            clearTimeout(feedbackTimer);
            feedbackTimer = null;
        }
        panel.classList.toggle('is-error', Boolean(isError));
        messageNode.textContent = message || '';
        errorNode.textContent = isError ? (message || 'Google sign-in was not completed.') : '';
        panel.querySelector('.google-oauth-cancel').setAttribute(
            'aria-label', isError ? 'Dismiss Google sign-in notice' : 'Cancel Google sign-in'
        );
        panel.hidden = !visible;

        // Only genuine failures show brief, compact feedback. Never show
        // instructional copy during Google's authorization process.
        if (visible && isError) {
            feedbackTimer = setTimeout(function () {
                panel.hidden = true;
                feedbackTimer = null;
            }, 6500);
        }
    }

    function stop() {
        if (timer) window.clearInterval(timer);
        timer = null;
        active = null;
        runningCheck = false;
    }

    function closeChild() {
        try {
            if (active && active.window && !active.window.closed) active.window.close();
        } catch (e) {
            // User may have closed it or browser privacy settings may isolate it.
        }
    }

    async function check() {
        if (!active || runningCheck) return;
        var current = active;
        runningCheck = true;
        try {
            var response = await fetch(current.status, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (current !== active) return;
            if (!response.ok) {
                if (response.status === 404 || response.status === 401 || response.status === 403) {
                    stop();
                    update('Unable to verify Google sign-in. Please try again.', true, true);
                }
                return;
            }
            var data = await response.json();
            if (current !== active) return;
            if (data.complete === true && typeof data.redirect === 'string' && data.redirect) {
                // Only the authenticated Laravel session may authorize completion.
                var target = new URL(data.redirect, location.origin);
                if (target.origin !== location.origin) throw new Error('Invalid redirect origin');
                closeChild();
                stop();
                window.location.assign(target.href);
                return;
            }
            if (current.window.closed) {
                stop();
                update('Google window closed before sign-in was completed. Try again.', true, true);
            } else if (Date.now() > timeoutAt) {
                closeChild();
                stop();
                update('Google sign-in timed out. Please try again.', true, true);
            }
        } catch (e) {
            if (current === active && (current.window.closed || Date.now() > timeoutAt)) {
                stop();
                update('Could not confirm Google sign-in. Please try again.', true, true);
            }
        } finally {
            runningCheck = false;
        }
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[data-google-oauth-popup]');
        if (!link) return;
        if (event.defaultPrevented || event.button !== 0 ||
            event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        event.preventDefault();
        if (active) {
            try { active.window.focus(); } catch (e) {}
            return;
        }
        var target = new URL(link.href, location.href);
        if (target.origin !== location.origin) {
            location.assign(link.href);
            return;
        }
        target.searchParams.set('popup', '1');
        var left = Math.max(0, window.screenX + (window.outerWidth - 580) / 2);
        var top = Math.max(0, window.screenY + (window.outerHeight - 730) / 2);
        var options = 'popup=yes,width=580,height=730,left=' + Math.round(left) +
            ',top=' + Math.round(top) + ',scrollbars=yes,resizable=yes';
        // window.open is called synchronously from the click event to avoid popup blockers.
        var child = window.open(target.href, 'BasinEnergyGoogleOAuth', options);
        if (!child) {
            location.assign(link.href); // Full-page OAuth fallback.
            return;
        }
        active = {
            window: child,
            status: link.getAttribute('data-google-oauth-status')
        };
        timeoutAt = Date.now() + MAX_WAIT_MS;
        update('Waiting for Google sign-in', true);
        try { child.focus(); } catch (e) {}
        timer = window.setInterval(check, 1200);
        // Don't poll immediately: the popup needs to establish its server-side marker.
    });

    window.addEventListener('message', function (event) {
        if (event.origin !== location.origin || !active || event.source !== active.window) return;
        if (!event.data || event.data.type !== 'basinenergy.google.oauth.result') return;
        if (event.data.success === true) {
            // Hint only: the server-side status endpoint decides whether to log in.
            check();
        } else {
            stop();
            update(event.data.message || 'Google sign-in was not completed.', true, true);
        }
    });
}());
