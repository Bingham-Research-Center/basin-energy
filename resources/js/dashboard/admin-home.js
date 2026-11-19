import Chart from 'chart.js/auto';

const STATIONS = [
    { slug: 'seven-sisters', name: 'Seven Sisters', color: '#2eb85c' },
    { slug: 'horsepool', name: 'Horsepool', color: '#3399ff' },
    { slug: 'castle-peak', name: 'Castle Peak', color: '#f9b115' },
    { slug: 'roosevelt', name: 'Roosevelt', color: '#8b5cf6' }
];

function initialiseAdminOzoneTrend() {
    const root = document.getElementById('adminOzoneTrend');
    if (!root || root.dataset.initialised === 'true') return;

    const canvas = document.getElementById('adminOzoneChart');
    const status = document.getElementById('adminOzoneStatus');
    const refreshButton = document.getElementById('adminOzoneRefresh');
    const urlTemplate = root.dataset.historyUrlTemplate;

    if (!canvas || !status || !urlTemplate || !urlTemplate.includes('__STATION__')) {
        if (status) status.textContent = 'Ozone history endpoint is not configured.';
        return;
    }

    root.dataset.initialised = 'true';
    let chart = null;
    let latestDatasets = [];

    const isDark = () => document.body.classList.contains('c-dark-theme');
    const color = () => isDark() ? '#e7edf6' : '#34445b';
    const muted = () => isDark() ? '#aebbd0' : '#65758d';
    const grid = () => isDark() ? 'rgba(163,181,207,.16)' : 'rgba(67,83,107,.15)';

    function formatTimestamp(value) {
        if (typeof value !== 'string' || !value.trim()) return null;

        // Campbell history strings represent local station clock times.
        const date = new Date(value.trim().replace(' ', 'T'));
        return Number.isFinite(date.getTime()) ? date.getTime() : null;
    }

    function render() {
        if (chart) chart.destroy();

        chart = new Chart(canvas, {
            type: 'line',
            data: { datasets: latestDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { mode: 'nearest', intersect: false },
                scales: {
                    x: {
                        type: 'linear',
                        title: { display: true, text: 'Observation time', color: muted() },
                        grid: { color: grid() },
                        ticks: {
                            color: color(),
                            maxRotation: 0,
                            maxTicksLimit: 7,
                            callback: value => new Date(value).toLocaleString('en-US', {
                                month: 'short', day: 'numeric', hour: 'numeric'
                            })
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: '1-hour ozone (ppb)', color: muted() },
                        grid: { color: grid() },
                        ticks: { color: color() }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: color(), usePointStyle: true, boxWidth: 10, padding: 18 }
                    },
                    tooltip: {
                        backgroundColor: isDark() ? '#1d2735' : '#fff',
                        titleColor: color(),
                        bodyColor: color(),
                        borderColor: grid(),
                        borderWidth: 1,
                        callbacks: {
                            label: item => item.dataset.label + ': ' + item.parsed.y.toFixed(1) + ' ppb'
                        }
                    }
                }
            }
        });
    }

    async function load() {
        if (refreshButton) refreshButton.disabled = true;
        status.textContent = 'Loading the latest Campbell Logger observations...';

        const results = await Promise.all(STATIONS.map(async station => {
            try {
                const url = urlTemplate.replace('__STATION__', encodeURIComponent(station.slug));
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' }
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);

                const body = await response.json();
                if (body.ok !== true || !Array.isArray(body.data)) {
                    throw new Error('Invalid station history response');
                }

                const points = body.data.map(point => {
                    const x = formatTimestamp(point.x);
                    const y = point.y === null || point.y === undefined || point.y === ''
                        ? NaN : Number(point.y);
                    return Number.isFinite(x) && Number.isFinite(y) ? { x, y } : null;
                }).filter(Boolean);

                return { station, points };
            } catch (error) {
                console.warn('Admin dashboard: Campbell history for ' + station.slug, error);
                return { station, points: [] };
            }
        }));

        const available = results.filter(result => result.points.length > 0);
        latestDatasets = available.map(({ station, points }) => ({
            label: station.name,
            data: points,
            parsing: false,
            borderColor: station.color,
            backgroundColor: station.color,
            tension: 0.3,
            pointRadius: points.length === 1 ? 5 : 0,
            pointHoverRadius: 5,
            borderWidth: 2.5,
            showLine: points.length > 1,
            spanGaps: false
        }));

        if (latestDatasets.length) {
            render();
            status.textContent = available.length + ' of ' + STATIONS.length +
                ' stations reporting. Displaying recent hourly ozone measurements.';
        } else {
            if (chart) {
                chart.destroy();
                chart = null;
            }
            status.textContent =
                'Recent Campbell ozone observations are unavailable. You can retry or open the monitoring dashboard.';
        }
        if (refreshButton) refreshButton.disabled = false;
    }

    if (refreshButton) refreshButton.addEventListener('click', load);
    window.addEventListener('brc:theme-changed', () => {
        if (latestDatasets.length) render();
    });

    load();
}

// Shared backend.js also loads on User pages, so initialize only if the
// Admin overview exists. Handles already-ready and normal document lifecycles.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialiseAdminOzoneTrend, { once: true });
} else {
    initialiseAdminOzoneTrend();
}
