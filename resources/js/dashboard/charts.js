window.BrcCharts = {
    isDark() {
        return document.body.classList.contains('c-dark-theme');
    },

    themeMode() {
        return this.isDark() ? 'dark' : 'light';
    },

    textColor() {
        return this.isDark() ? '#e5e7eb' : '#111827';
    },

    mutedColor() {
        return this.isDark() ? '#9ca3af' : '#6b7280';
    },

    gridColor() {
        return this.isDark() ? '#374151' : '#e5e7eb';
    },

    baseOptions(extra = {}) {
        return {
            chart: {
                toolbar: {
                    show: true
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 450
                },
                foreColor: this.textColor()
            },
            theme: {
                mode: this.themeMode()
            },
            grid: {
                borderColor: this.gridColor()
            },
            tooltip: {
                theme: this.themeMode()
            },
            dataLabels: {
                enabled: false
            },
            ...extra
        };
    }
};