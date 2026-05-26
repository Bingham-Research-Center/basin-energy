@extends('frontend.layouts.app')

@section('title', __('Realtime Ozone'))

@section('content')
<div
    class="dashboard-page dashboard-page-pro dashboard-fill-page"
    id="realtimeOzoneRoot"
    data-json-url="{{ route('frontend.user.data.realtime.ozone.json') }}"
>
    <div class="card dashboard-chart-card dashboard-fill-card mb-4">
        <div class="card-header">
            <div>
                <h2 class="dashboard-chart-title mb-0">@lang('Realtime Uinta Basin Ozone')</h2>
                <div class="dashboard-chart-subtitle" id="ozoneStatus">@lang('Loading realtime air quality data...')</div>
            </div>

            <button id="btnRefreshOzone" type="button" class="btn btn-sm btn-outline-primary">
                <i class="c-icon cil-reload mr-1"></i> @lang('Refresh data')
            </button>
        </div>

        <div class="card-body dashboard-fill-card-body">
            <div class="row dashboard-metric-row mb-4">
                <div class="col-12 col-md-6 col-xl-3 mb-3 mb-xl-0">
                    <div class="dashboard-stat-card card h-100">
                        <div class="card-body">
                            <div class="dashboard-stat-label">@lang('Basin latest avg')</div>
                            <div class="dashboard-stat-value"><span id="latestAvg">--</span> <span class="dashboard-stat-unit">ppb</span></div>
                            <div class="dashboard-stat-note" id="latestAvgLabel">@lang('Waiting for data')</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3 mb-3 mb-xl-0">
                    <div class="dashboard-stat-card card h-100">
                        <div class="card-body">
                            <div class="dashboard-stat-label">@lang('Peak station')</div>
                            <div class="dashboard-stat-value"><span id="peakValue">--</span> <span class="dashboard-stat-unit">ppb</span></div>
                            <div class="dashboard-stat-note" id="peakLabel">@lang('Waiting for data')</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3 mb-3 mb-md-0">
                    <div class="dashboard-stat-card card h-100">
                        <div class="card-body">
                            <div class="dashboard-stat-label">@lang('Active stations')</div>
                            <div class="dashboard-stat-value" id="stationCount">--</div>
                            <div class="dashboard-stat-note">@lang('Stations returning ozone observations')</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="dashboard-stat-card card h-100">
                        <div class="card-body">
                            <div class="dashboard-stat-label">@lang('Current category')</div>
                            <div class="dashboard-stat-value" id="categoryValue">--</div>
                            <div class="dashboard-stat-note">@lang('Based on latest station average')</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row dashboard-fill-content-row">
                <div class="col-12 col-xl-8 mb-4 mb-xl-0 dashboard-fill-column">
                    <div class="dashboard-panel-card h-100">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">@lang('Ozone trend')</div>
                                <div class="dashboard-panel-subtitle">@lang('Station observations over the latest available period')</div>
                            </div>
                            <span class="dashboard-status-pill dashboard-status-pill-info" id="timeRangePill">@lang('Last 7 days')</span>
                        </div>

                        <div class="dashboard-chart-shell dashboard-chart-shell-fill">
                            <canvas id="ozoneChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4 dashboard-fill-column">
                    <div class="dashboard-panel-card h-100">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">@lang('Latest station ranking')</div>
                                <div class="dashboard-panel-subtitle">@lang('Highest current station values')</div>
                            </div>
                            <span class="dashboard-status-pill dashboard-status-pill-neutral">@lang('Latest')</span>
                        </div>

                        <div class="dashboard-station-list" id="stationList">@lang('Loading...')</div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12 col-xl-5 mb-4 mb-xl-0">
                    <div class="dashboard-panel-card h-100">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">@lang('Network gauge')</div>
                                <div class="dashboard-panel-subtitle">@lang('Latest basin average')</div>
                            </div>
                            <span class="dashboard-status-pill dashboard-status-pill-neutral">0-100 ppb</span>
                        </div>

                        <div class="dashboard-gauge-wrap">
                            <canvas id="networkGauge"></canvas>
                            <div class="dashboard-gauge-center">
                                <div><span class="dashboard-gauge-value" id="gaugeValue">--</span> <span class="dashboard-stat-unit">ppb</span></div>
                                <div class="dashboard-gauge-label" id="gaugeLabel">@lang('Latest basin average')</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-7">
                    <div class="dashboard-panel-card h-100">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">@lang('Station latest bars')</div>
                                <div class="dashboard-panel-subtitle">@lang('Latest value comparison by station')</div>
                            </div>
                            <span class="dashboard-status-pill dashboard-status-pill-neutral">@lang('Comparison')</span>
                        </div>

                        <div class="dashboard-chart-shell dashboard-chart-shell-sm">
                            <canvas id="stationBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-chart-help mt-3">
                <i class="c-icon cil-info mr-1"></i>
                @lang('Ozone values are displayed in ppb. Refresh pulls the latest realtime endpoint data.')
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
@once
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1/dist/chartjs-adapter-luxon.umd.min.js"></script>
@endonce
<script>
(function () {
    const root = document.getElementById('realtimeOzoneRoot');
    if (!root) return;

    const url = root.dataset.jsonUrl;
    const status = document.getElementById('ozoneStatus');
    const refreshBtn = document.getElementById('btnRefreshOzone');
    let trendChart = null;
    let gaugeChart = null;
    let barChart = null;
    let latestJson = null;

    function isDarkTheme() {
        return document.body.classList.contains('c-dark-theme');
    }

    function chartTextColor() {
        return isDarkTheme() ? '#cbd5e1' : '#374151';
    }

    function chartMutedColor() {
        return isDarkTheme() ? '#94a3b8' : '#6b7280';
    }

    function chartGridColor() {
        return isDarkTheme() ? 'rgba(148, 163, 184, 0.14)' : 'rgba(107, 114, 128, 0.18)';
    }

    function chartTooltipBg() {
        return isDarkTheme() ? '#111827' : '#ffffff';
    }

    function safe(value) {
        return String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
    }

    function colorForOzone(value) {
        if (value === null || Number.isNaN(value)) return '#777';
        if (value <= 54) return '#2eb85c';
        if (value <= 70) return '#f9b115';
        if (value <= 85) return '#f59e0b';
        return '#e55353';
    }

    function categoryForOzone(value) {
        if (value === null || Number.isNaN(value)) return { text: '--', css: '' };
        if (value <= 54) return { text: 'Good', css: 'dashboard-text-success' };
        if (value <= 70) return { text: 'Moderate', css: 'dashboard-text-warning' };
        if (value <= 85) return { text: 'Elevated', css: 'dashboard-text-orange' };
        return { text: 'High', css: 'dashboard-text-danger' };
    }

    function latestPoint(series) {
        const data = series.data || [];
        return data.length ? data[data.length - 1] : null;
    }

    function number(value, digits = 1) {
        return value === null || value === undefined || Number.isNaN(value) ? '--' : Number(value).toFixed(digits);
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function destroyCharts() {
        if (trendChart) trendChart.destroy();
        if (gaugeChart) gaugeChart.destroy();
        if (barChart) barChart.destroy();
        trendChart = null;
        gaugeChart = null;
        barChart = null;
    }

    function renderStats(json) {
        const stations = json.series || [];
        const latest = stations.map(station => ({ station, point: latestPoint(station) })).filter(item => item.point);
        const latestValues = latest.map(item => Number(item.point.y)).filter(value => !Number.isNaN(value));
        const avg = latestValues.length ? latestValues.reduce((sum, value) => sum + value, 0) / latestValues.length : null;
        const peak = latest.reduce((best, item) => !best || Number(item.point.y) > Number(best.point.y) ? item : best, null);
        const category = categoryForOzone(avg);

        setText('latestAvg', number(avg));
        setText('latestAvgLabel', latest.length ? `Average from ${latest.length} latest station readings` : 'No latest readings');
        setText('peakValue', number(peak ? peak.point.y : null));
        setText('peakLabel', peak ? (peak.station.name || peak.station.station) : 'No station peak');
        setText('stationCount', stations.length);
        setText('categoryValue', category.text);
        setText('gaugeValue', number(avg));

        const categoryEl = document.getElementById('categoryValue');
        categoryEl.className = `dashboard-stat-value ${category.css}`;

        const sorted = latest.sort((a, b) => Number(b.point.y) - Number(a.point.y));
        document.getElementById('stationList').innerHTML = sorted.map(item => `
            <div class="dashboard-station-row">
                <div>
                    <div class="dashboard-station-name">${safe(item.station.name || item.station.station)}</div>
                    <div class="dashboard-station-meta">${safe(item.station.station || '')} · ${safe(new Date(item.point.x).toLocaleString())}</div>
                </div>
                <div class="dashboard-station-value" style="color:${colorForOzone(Number(item.point.y))};">${number(item.point.y)} ppb</div>
            </div>
        `).join('') || '<div class="dashboard-control-help">No station values returned.</div>';

        renderGauge(avg);
        renderBars(sorted.slice(0, 12));
    }

    function renderTrend(json) {
        const palette = ['#2eb85c', '#3399ff', '#f9b115', '#f59e0b', '#e55353', '#8b5cf6', '#06b6d4', '#84cc16'];

        const datasets = (json.series || []).map((item, index) => ({
            label: item.station || item.name,
            data: item.data || [],
            parsing: false,
            tension: 0.35,
            pointRadius: 0,
            pointHoverRadius: 3,
            borderWidth: 2,
            borderColor: palette[index % palette.length],
            backgroundColor: 'transparent'
        }));

        if (trendChart) trendChart.destroy();
        trendChart = new Chart(document.getElementById('ozoneChart'), {
            type: 'line',
            data: { datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'nearest', intersect: false },
                scales: {
                    x: {
                        type: 'time',
                        grid: { color: chartGridColor() },
                        ticks: { color: chartTextColor(), maxRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Ozone concentration (ppb)', color: chartMutedColor() },
                        grid: { color: chartGridColor() },
                        ticks: { color: chartTextColor() }
                    }
                },
                plugins: {
                    legend: { position: 'bottom', labels: { color: chartTextColor(), boxWidth: 10, usePointStyle: true } },
                    tooltip: {
                        backgroundColor: chartTooltipBg(),
                        titleColor: chartTextColor(),
                        bodyColor: chartTextColor(),
                        borderColor: chartGridColor(),
                        borderWidth: 1
                    }
                }
            }
        });
    }

    function renderGauge(avg) {
        if (gaugeChart) gaugeChart.destroy();
        const value = avg === null ? 0 : Math.max(0, Math.min(100, avg));
        gaugeChart = new Chart(document.getElementById('networkGauge'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [value, 100 - value],
                    backgroundColor: [colorForOzone(avg), isDarkTheme() ? '#303847' : '#e5e7eb'],
                    borderWidth: 0,
                    circumference: 220,
                    rotation: 250,
                    cutout: '72%'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
        });
    }

    function renderBars(rows) {
        if (barChart) barChart.destroy();
        barChart = new Chart(document.getElementById('stationBarChart'), {
            type: 'bar',
            data: {
                labels: rows.map(row => row.station.station || row.station.name),
                datasets: [{
                    label: 'Latest ozone',
                    data: rows.map(row => Number(row.point.y)),
                    backgroundColor: rows.map(row => colorForOzone(Number(row.point.y))),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false }, ticks: { color: chartTextColor() } },
                    y: { beginAtZero: true, grid: { color: chartGridColor() }, ticks: { color: chartTextColor() } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    function renderAll(json) {
        latestJson = json;
        renderStats(json);
        renderTrend(json);
    }

    async function loadOzone() {
        status.textContent = 'Loading...';
        refreshBtn.disabled = true;

        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) throw new Error('Request failed');

            const json = await response.json();
            renderAll(json);
            status.textContent = `Loaded ${json.meta?.count || 0} station(s). Updated ${new Date().toLocaleString()}.`;
        } catch (error) {
            console.error(error);
            status.textContent = 'Unable to load ozone data.';
        } finally {
            refreshBtn.disabled = false;
        }
    }

    refreshBtn.addEventListener('click', loadOzone);

    window.addEventListener('brc:theme-changed', () => {
        if (latestJson) {
            destroyCharts();
            renderAll(latestJson);
        }
    });

    loadOzone();
})();
</script>
@endpush