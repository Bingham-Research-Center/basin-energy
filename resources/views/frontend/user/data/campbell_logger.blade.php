@extends('frontend.layouts.app')

@section('title', $site['name'] ?? __('Campbell Logger'))

@section('content')
<div
    class="dashboard-page dashboard-page-pro dashboard-fill-page"
    id="horsepoolRoot"
    data-json-url="{{ route('frontend.user.data.realtime.logger.json', ['site' => $siteKey]) }}"
>
    <div class="card dashboard-chart-card dashboard-fill-card mb-4">
        <div class="card-header">
            <div>
                <h1 class="dashboard-chart-title mb-0">
                    {{ $site['name'] ?? __('Campbell Logger') }}
                </h1>

                <div class="dashboard-chart-subtitle">
                    @lang('Realtime Campbell logger packet')
                    @if(! empty($site['elevation_ft']))
                        · {{ number_format($site['elevation_ft']) }} ft
                    @endif
                </div>
            </div>

            <div class="d-flex align-items-center">
                <span id="horsepoolStatus" class="dashboard-chart-subtitle mr-3">
                    @lang('Waiting for data...')
                </span>

                <button id="btnRefreshHorsepool" type="button" class="btn btn-sm btn-outline-primary">
                    <i class="c-icon cil-reload mr-1"></i> @lang('Refresh data')
                </button>
            </div>
        </div>

        <div class="card-body dashboard-fill-card-body">
            <div id="horsepoolContent" class="dashboard-loading-panel">
                @lang('Loading station packet...')
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
@once
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endonce
<script>
(function () {
    const root = document.getElementById('horsepoolRoot');
    if (!root) return;

    const url = root.dataset.jsonUrl;
    const status = document.getElementById('horsepoolStatus');
    const refreshBtn = document.getElementById('btnRefreshHorsepool');
    const content = document.getElementById('horsepoolContent');
    const charts = [];
    let latestRecord = null;
    let latestMeta = null;

    const dashboardMetrics = [
        { id: 'battery', title: 'Battery', unit: 'V', patterns: [/batt/i, /battery/i], min: 10, max: 14.5, goodMin: 12.1 },
        { id: 'temp', title: 'Air Temp', unit: '°C', patterns: [/air.*tc/i, /temp/i, /temperature/i], min: -20, max: 45 },
        { id: 'rh', title: 'Humidity', unit: '%', patterns: [/rh/i, /humid/i], min: 0, max: 100 },
        { id: 'wind', title: 'Wind Speed', unit: 'm/s', patterns: [/wind.*speed/i, /ws/i], min: 0, max: 20 }
    ];

    function isDarkTheme() {
        return document.body.classList.contains('c-dark-theme');
    }

    function destroyCharts() {
        while (charts.length) {
            charts.pop().destroy();
        }
    }

    function safe(value) {
        return String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
    }

    function numeric(value) {
        const match = String(value ?? '').replace(/,/g, '').match(/-?\d+(\.\d+)?/);
        return match ? Number(match[0]) : null;
    }

    function findMetric(record, metric) {
        const entry = Object.entries(record).find(([key]) => metric.patterns.some(pattern => pattern.test(key)));
        if (!entry) return null;

        return { key: entry[0], raw: entry[1], value: numeric(entry[1]) };
    }

    function gaugeColor(value, metric) {
        if (value === null) return '#777';
        if (metric.id === 'battery') return value >= metric.goodMin ? '#2eb85c' : '#f9b115';
        if (metric.id === 'wind') return value > 12 ? '#e55353' : value > 7 ? '#f59e0b' : '#2eb85c';
        if (metric.id === 'temp') return value > 35 || value < -5 ? '#f59e0b' : '#2eb85c';
        if (metric.id === 'rh') return value > 80 ? '#3399ff' : '#2eb85c';
        return '#2eb85c';
    }

    function gaugeTrackColor() {
        return isDarkTheme() ? '#303847' : '#e5e7eb';
    }

    function gaugePercent(value, metric) {
        if (value === null) return 0;
        return Math.max(0, Math.min(100, ((value - metric.min) / (metric.max - metric.min)) * 100));
    }

    function renderGauge(canvasId, value, metric) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        const pct = gaugePercent(value, metric);

        charts.push(new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [pct, 100 - pct],
                    backgroundColor: [gaugeColor(value, metric), gaugeTrackColor()],
                    borderWidth: 0,
                    circumference: 220,
                    rotation: 250,
                    cutout: '72%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } }
            }
        }));
    }

    function render(record, meta) {
        latestRecord = record;
        latestMeta = meta;
        destroyCharts();

        const cards = dashboardMetrics.map(metric => {
            const found = findMetric(record, metric);
            const displayValue = found?.value === null || found?.value === undefined
                ? '--'
                : found.value.toLocaleString(undefined, { maximumFractionDigits: 2 });

            return `
                <div class="col-12 col-md-6 col-xl-3 mb-3">
                    <div class="dashboard-panel-card dashboard-gauge-card h-100">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">${safe(metric.title)}</div>
                                <div class="dashboard-panel-subtitle">${safe(found?.key || 'Not found')}</div>
                            </div>
                            <span class="dashboard-status-pill dashboard-status-pill-neutral">${safe(metric.unit)}</span>
                        </div>

                        <div class="dashboard-gauge-wrap">
                            <canvas id="gauge-${safe(metric.id)}"></canvas>
                            <div class="dashboard-gauge-center">
                                <div><span class="dashboard-gauge-value">${safe(displayValue)}</span> <span class="dashboard-stat-unit">${safe(metric.unit)}</span></div>
                                <div class="dashboard-gauge-label">${safe(found?.raw || 'No value')}</div>
                            </div>
                        </div>
                    </div>
                </div>`;
        }).join('');

        const rows = Object.entries(record).map(([key, value]) => `
            <tr>
                <td><strong>${safe(key)}</strong></td>
                <td>${safe(value)}</td>
            </tr>
        `).join('');

        content.className = 'dashboard-horsepool-content';
        content.innerHTML = `
            <div class="row dashboard-metric-row">
                ${cards}
            </div>

            <div class="dashboard-panel-card mt-2">
                <div class="dashboard-panel-header">
                    <div>
                        <div class="dashboard-panel-title">Latest logger packet</div>
                        <div class="dashboard-panel-subtitle">${safe(meta?.station || 'Horsepool')} · ${safe(meta?.source || 'Campbell logger')}</div>
                    </div>
                    <span class="dashboard-status-pill dashboard-status-pill-info">Realtime</span>
                </div>

                <div class="table-responsive dashboard-table-wrap">
                    <table class="table table-sm dashboard-data-table mb-0">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Latest value</th>
                            </tr>
                        </thead>
                        <tbody>${rows || '<tr><td colspan="2">No record values returned.</td></tr>'}</tbody>
                    </table>
                </div>
            </div>

            <div class="dashboard-chart-help mt-3">
                <i class="c-icon cil-info mr-1"></i>
                Latest packet values are parsed directly from the Campbell logger endpoint.
            </div>`;

        dashboardMetrics.forEach(metric => renderGauge(`gauge-${metric.id}`, findMetric(record, metric)?.value ?? null, metric));
    }

    async function loadHorsepool() {
        status.textContent = 'Loading...';
        refreshBtn.disabled = true;

        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' }});
            if (!response.ok) throw new Error('Request failed');

            const json = await response.json();
            render(json.record || {}, json.meta || {});
            status.textContent = `Fetched at ${new Date(json.meta?.fetched_at || Date.now()).toLocaleString()}`;
        } catch (error) {
            console.error(error);
            content.className = 'dashboard-loading-panel dashboard-loading-panel-error';
            content.textContent = 'Unable to load Horsepool station packet.';
            status.textContent = 'Load failed.';
        } finally {
            refreshBtn.disabled = false;
        }
    }

    refreshBtn.addEventListener('click', loadHorsepool);

    window.addEventListener('brc:theme-changed', () => {
        if (latestRecord) {
            render(latestRecord, latestMeta || {});
        }
    });

    loadHorsepool();
})();
</script>
@endpush