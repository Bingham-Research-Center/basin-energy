/* Role Management only. Livewire updates the table HTML and emits a
 * success event once the default role change commits. The event is bridged
 * to the dashboard's shared toast UI without reloading the page.
 */
(function () {
    'use strict';

    var installed = false;

    function onDefaultRoleUpdated(payload) {
        // Livewire v3 passes event detail to Livewire.on listeners.
        // Accept a CustomEvent too for compatibility with external dispatchers.
        var detail = payload && payload.detail ? payload.detail : payload;
        var message = detail && detail.message;

        if (typeof message !== 'string' || !message.trim()) return;

        if (window.BrcToasts && typeof window.BrcToasts.show === 'function') {
            window.BrcToasts.show(message, 'success', {
                position: 'top-end',
                timer: 3000
            });
        }
    }

    function register() {
        if (installed || !window.Livewire || typeof window.Livewire.on !== 'function') return;
        installed = true;
        window.Livewire.on('brc-default-role-updated', onDefaultRoleUpdated);
    }

    // Works whether the script executes before or after livewire:init.
    document.addEventListener('livewire:init', register, {once: true});
    register();
}());
