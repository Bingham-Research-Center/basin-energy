/* Shared SweetAlert2 notifications for the Admin and User dashboards.
 * Backend.js already provides window.Swal; there is no extra dependency.
 * Frontend public/marketing pages are deliberately not affected.
 */
(function () {
    'use strict';

    var pending = Promise.resolve();
    var allowed = {success: true, error: true, warning: true, info: true, question: true};

    function iconFor(value) {
        return allowed[value] ? value : 'info';
    }

    function fallbackToast(message, icon, position, timer) {
        // If the compiled bundle failed to load, still show actionable feedback
        // rather than silently dropping all flash messages.
        var el = document.createElement('div');
        el.setAttribute('role', 'status');
        el.className = 'alert alert-' + (icon === 'error' ? 'danger' : icon);
        el.textContent = message;
        el.style.cssText = 'position:fixed;' + (position === 'top-start' ? 'left:20px;' : 'right:20px;') +
            'top:20px;max-width:min(450px,90vw);z-index:20000;';
        document.body.appendChild(el);
        return new Promise(function (resolve) {
            setTimeout(function () { el.remove(); resolve(); }, timer);
        });
    }

    function showNow(message, icon, options) {
        if (!message || !String(message).trim()) return Promise.resolve();
        icon = iconFor(icon);
        options = options || {};
        // Preserve the dashboard default; the role-toggle page opts into
        // a top-left, three-second toast without changing other notifications.
        var position = options.position === 'top-start' ? 'top-start' : 'top-end';
        var timer = Number(options.timer);
        if (!Number.isInteger(timer) || timer < 1000 || timer > 10000) {
            timer = icon === 'error' ? 5500 : 3500;
        }
        if (!window.Swal || typeof window.Swal.fire !== 'function') {
            return fallbackToast(String(message), icon, position, timer);
        }
        return window.Swal.fire({
            toast: true,
            position: position,
            icon: icon,
            titleText: String(message),
            showConfirmButton: false,
            timer: timer,
            timerProgressBar: true
        });
    }

    window.BrcToasts = {
        show: function (message, icon, options) {
            pending = pending.catch(function () {}).then(function () {
                return showNow(message, icon || 'success', options);
            });
            return pending;
        },

        confirm: function (options) {
            options = options || {};
            if (window.Swal && typeof window.Swal.fire === 'function') {
                return window.Swal.fire({
                    titleText: String(options.title || 'Confirm action?'),
                    text: String(options.text || ''),
                    icon: options.icon || 'question',
                    showCancelButton: true,
                    reverseButtons: true,
                    focusCancel: true,
                    confirmButtonText: options.confirmButtonText || 'Continue',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: options.danger ? '#d33' : '#3085d6'
                }).then(function (result) {
                    return Boolean(result.isConfirmed || result.value);
                });
            }
            return Promise.resolve(window.confirm(
                String(options.title || 'Confirm action?') + '\n' + String(options.text || '')
            ));
        }
    };

    // Server-driven flash messages are JSON encoded by the shared
    // resources/views/includes/partials/messages.blade.php partial.
    // Use titleText (never raw HTML) to avoid rendering user strings as markup.
    var data = document.getElementById('brc-dashboard-toast-data');
    if (!data) return;
    try {
        var messages = JSON.parse(data.textContent || '[]');
        if (!Array.isArray(messages)) return;
        messages.forEach(function (item) {
            if (item && typeof item.message === 'string') {
                window.BrcToasts.show(item.message, iconFor(item.icon));
            }
        });
    } catch (error) {
        // Invalid JSON should not break other dashboard scripts.
    }
}());
