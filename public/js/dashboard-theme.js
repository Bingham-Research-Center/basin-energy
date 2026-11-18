/* Shared CoreUI dashboard theme controller for Administrator and User layouts.
 * The tiny Blade bootstrap applies the saved preference before the page paints.
 */
(function () {
    'use strict';

    if (window.BrcTheme) return;

    var KEY = 'brc-theme';
    var CHOICES = ['light', 'dark', 'auto'];
    var root = document.documentElement;
    var systemMedia = window.matchMedia
        ? window.matchMedia('(prefers-color-scheme: dark)')
        : null;
    var motionMedia = window.matchMedia
        ? window.matchMedia('(prefers-reduced-motion: reduce)')
        : null;
    var stopAnimation = null;
    var initialized = false;

    function valid(theme) {
        return CHOICES.indexOf(theme) !== -1 ? theme : 'auto';
    }

    function readPreference() {
        try {
            return valid(window.localStorage.getItem(KEY));
        } catch (error) {
            // Private-browsing and managed browsers may block localStorage.
            return 'auto';
        }
    }

    function savePreference(theme) {
        try {
            window.localStorage.setItem(KEY, theme);
        } catch (error) {
            // The selected mode still applies for this page.
        }
    }

    function resolve(preference) {
        if (preference !== 'auto') return preference;
        return systemMedia && systemMedia.matches ? 'dark' : 'light';
    }

    var preference = readPreference();
    var resolved = root.getAttribute('data-brc-resolved-theme') || resolve(preference);

    function refreshControls() {
        var labels = {light: 'Light', dark: 'Dark', auto: 'Auto'};
        var icons = {
            light: 'c-icon cil-sun',
            dark: 'c-icon cil-moon',
            auto: 'c-icon cil-screen-desktop'
        };

        document.querySelectorAll('[data-dashboard-theme-label]').forEach(function (label) {
            label.textContent = labels[preference];
        });

        document.querySelectorAll('[data-dashboard-theme-icon]').forEach(function (icon) {
            icon.className = icons[preference];
        });

        document.querySelectorAll('[data-dashboard-theme-toggle]').forEach(function (toggle) {
            toggle.setAttribute('aria-label', 'Theme: ' + labels[preference]);
            toggle.setAttribute('title', 'Theme: ' + labels[preference]);
        });

        document.querySelectorAll('[data-dashboard-theme-value]').forEach(function (button) {
            var selected = button.getAttribute('data-dashboard-theme-value') === preference;
            button.classList.toggle('active', selected);
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
    }

    function apply(next, animate) {
        next = valid(next);
        var nextResolved = resolve(next);
        var preferenceChanged = next !== preference;
        var resolutionChanged = nextResolved !== resolved;
        var transition = Boolean(animate && resolutionChanged &&
            !(motionMedia && motionMedia.matches));

        if (stopAnimation !== null) {
            clearTimeout(stopAnimation);
            stopAnimation = null;
        }
        root.classList.remove('brc-theme-changing');

        if (transition) {
            // Capture the current appearance before activating transitions.
            void document.body.offsetWidth;
            root.classList.add('brc-theme-changing');
        }

        preference = next;
        resolved = nextResolved;
        root.setAttribute('data-brc-theme', preference);
        root.setAttribute('data-brc-resolved-theme', resolved);
        root.style.colorScheme = resolved;
        document.body.classList.toggle('c-dark-theme', resolved === 'dark');

        document.querySelectorAll('.c-header').forEach(function (header) {
            header.classList.toggle('c-header-dark', resolved === 'dark');
            header.classList.toggle('c-header-light', resolved !== 'dark');
        });

        refreshControls();

        if (transition) {
            stopAnimation = setTimeout(function () {
                root.classList.remove('brc-theme-changing');
                stopAnimation = null;
            }, 300);
        }

        if (preferenceChanged || resolutionChanged) {
            window.dispatchEvent(new CustomEvent('brc:theme-changed', {
                detail: {theme: preference, resolvedTheme: resolved}
            }));
        }
    }

    function setTheme(theme) {
        if (CHOICES.indexOf(theme) === -1) return;
        savePreference(theme);
        apply(theme, true);
    }

    function initialize() {
        if (initialized) return;
        initialized = true;

        // The body is already pre-themed by the layout bootstrap.
        apply(readPreference(), false);

        document.addEventListener('click', function (event) {
            var target = event.target.closest('[data-dashboard-theme-value]');
            if (!target) return;
            event.preventDefault();
            setTheme(target.getAttribute('data-dashboard-theme-value'));
        });

        function systemChanged() {
            if (preference === 'auto') apply('auto', true);
        }
        if (systemMedia) {
            if (typeof systemMedia.addEventListener === 'function') {
                systemMedia.addEventListener('change', systemChanged);
            } else if (typeof systemMedia.addListener === 'function') {
                systemMedia.addListener(systemChanged);
            }
        }

        window.addEventListener('storage', function (event) {
            if (event.key === KEY || event.key === null) {
                apply(readPreference(), true);
            }
        });
    }

    window.BrcTheme = {
        getPreference: function () { return preference; },
        getResolvedTheme: function () { return resolved; },
        setTheme: setTheme
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, {once: true});
    } else {
        initialize();
    }
}());
