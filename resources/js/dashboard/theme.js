const themeKey = 'brc-theme';

function getStoredTheme() {
    return localStorage.getItem(themeKey);
}

function setStoredTheme(theme) {
    localStorage.setItem(themeKey, theme);
}

function getSystemTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function resolveTheme(theme) {
    if (theme === 'auto') {
        return getSystemTheme();
    }

    return theme || getSystemTheme();
}

function getPreferredTheme() {
    return getStoredTheme() || 'auto';
}

function applyTheme(theme) {
    const resolvedTheme = resolveTheme(theme);
    const isDark = resolvedTheme === 'dark';

    document.body.classList.toggle('c-dark-theme', isDark);

    document.querySelectorAll('.c-header').forEach((header) => {
        header.classList.toggle('c-header-light', !isDark);
        header.classList.toggle('c-header-dark', isDark);
    });

    updateThemeSwitcher(theme, resolvedTheme);

    window.dispatchEvent(new CustomEvent('brc:theme-changed', {
        detail: {
            theme,
            resolvedTheme
        }
    }));
}

function updateThemeSwitcher(theme, resolvedTheme) {
    const labelText = theme === 'auto'
        ? 'Auto'
        : resolvedTheme.charAt(0).toUpperCase() + resolvedTheme.slice(1);

    const iconClass = theme === 'auto'
        ? 'c-icon cil-screen-desktop mr-2'
        : resolvedTheme === 'dark'
            ? 'c-icon cil-moon mr-2'
            : 'c-icon cil-sun mr-2';

    document.querySelectorAll('[data-dashboard-theme-label]').forEach((label) => {
        label.textContent = labelText;
    });

    document.querySelectorAll('[data-dashboard-theme-icon]').forEach((icon) => {
        icon.className = iconClass;
    });

    document.querySelectorAll('[data-dashboard-theme-value]').forEach((item) => {
        const isActive = item.getAttribute('data-dashboard-theme-value') === theme;

        item.classList.toggle('active', isActive);
        item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
}

function setTheme(theme) {
    setStoredTheme(theme);
    applyTheme(theme);
}

document.addEventListener('DOMContentLoaded', () => {
    applyTheme(getPreferredTheme());

    document.querySelectorAll('[data-dashboard-theme-value]').forEach((item) => {
        item.addEventListener('click', () => {
            setTheme(item.getAttribute('data-dashboard-theme-value'));
        });
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (getPreferredTheme() === 'auto') {
            applyTheme('auto');
        }
    });
});