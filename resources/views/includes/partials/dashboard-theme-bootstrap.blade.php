{{-- Run before sidebar/header markup to prevent light-to-dark flashes. --}}
<script>
(function () {
    var mode = 'auto';
    try {
        var stored = window.localStorage.getItem('brc-theme');
        if (stored === 'light' || stored === 'dark' || stored === 'auto') {
            mode = stored;
        }
    } catch (error) {
        // Browsers may deny storage access; follow the operating system.
    }
    var dark = window.matchMedia &&
        window.matchMedia('(prefers-color-scheme: dark)').matches;
    var resolved = mode === 'auto' ? (dark ? 'dark' : 'light') : mode;
    document.documentElement.setAttribute('data-brc-theme', mode);
    document.documentElement.setAttribute('data-brc-resolved-theme', resolved);
    document.documentElement.style.colorScheme = resolved;
    document.body.classList.toggle('c-dark-theme', resolved === 'dark');
}());
</script>
