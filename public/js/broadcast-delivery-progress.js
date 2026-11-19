/* Poll only the Broadcast Livewire table, without a full page reload. */
(function () {
    'use strict';

    function refreshProgress() {
        if (document.hidden || !window.Livewire) return;
        var container = document.querySelector('[data-broadcast-progress-table]');
        var table = container && container.querySelector('[wire\\:id]');
        if (!table) return;

        var instance = window.Livewire.find(table.getAttribute('wire:id'));
        if (instance && typeof instance.$refresh === 'function') {
            instance.$refresh();
        }
    }

    window.setInterval(refreshProgress, 10000);
})();
