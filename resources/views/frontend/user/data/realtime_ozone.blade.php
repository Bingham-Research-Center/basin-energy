@extends('frontend.layouts.app')

@section('title', __('Realtime Ozone'))

@push('after-styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

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
                            <div class="dashboard-stat-label">@lang('Campbell 1-hour average')</div>
                            <div class="dashboard-stat-value"><span id="latestAvg">--</span> <span class="dashboard-stat-unit">ppb</span></div>
                            <div class="dashboard-stat-note" id="latestAvgLabel">@lang('Waiting for data')</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3 mb-3 mb-xl-0">
                    <div class="dashboard-stat-card card h-100">
                        <div class="card-body">
                            <div class="dashboard-stat-label">@lang('Highest 1-hour ozone')</div>
                            <div class="dashboard-stat-value"><span id="peakValue">--</span> <span class="dashboard-stat-unit">ppb</span></div>
                            <div class="dashboard-stat-note" id="peakLabel">@lang('Waiting for data')</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3 mb-3 mb-md-0">
                    <div class="dashboard-stat-card card h-100">
                        <div class="card-body">
                            <div class="dashboard-stat-label">@lang('Reporting stations')</div>
                            <div class="dashboard-stat-value" id="stationCount">--</div>
                            <div class="dashboard-stat-note">@lang('Stations returning ozone observations')</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="dashboard-stat-card card h-100">
                        <div class="card-body">
                            <div class="dashboard-stat-label">@lang('Campbell 8-hour average')</div>
                            <div class="dashboard-stat-value" id="categoryValue">--</div>
                            <div class="dashboard-stat-note">@lang('Mean of available station 8-hour averages')</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campbell monitoring stations -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="dashboard-panel-card">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">@lang('Campbell Monitoring Stations')</div>
                                <div class="dashboard-panel-subtitle">@lang('Latest available ozone observations from Uinta Basin monitoring stations')</div>
                            </div>
                            <span class="dashboard-status-pill dashboard-status-pill-info">Campbell Loggers</span>
                        </div>

                        <div class="row" id="campbellStationCards">
                            @foreach (['seven-sisters' => 'Seven Sisters', 'horsepool' => 'Horsepool', 'castle-peak' => 'Castle Peak', 'roosevelt' => 'Roosevelt'] as $site => $name)
                                <div class="col-12 col-md-6 col-xl-3 mb-3">
                                    <div class="dashboard-stat-card card h-100">
                                        <div class="card-body">
                                            <div class="dashboard-stat-label">{{ $name }}</div>
                                            <div class="dashboard-stat-value" id="campbell-{{ $site }}-ozone">-- <span class="dashboard-stat-unit">ppb</span></div>
                                            <div class="dashboard-stat-note">1-hour ozone average</div>
                                            <hr>
                                            <div class="dashboard-stat-note">
                                                8-hour average:
                                                <strong id="campbell-{{ $site }}-8h">--</strong> ppb
                                            </div>
                                            <div class="dashboard-stat-note mt-2" id="campbell-{{ $site }}-status">
                                                Loading measurements...
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Atmospheric conditions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="dashboard-panel-card">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">@lang('Atmospheric Conditions')</div>
                                <div class="dashboard-panel-subtitle">@lang('Latest available meteorological observations from Campbell monitoring stations')</div>
                            </div>
                            <span class="dashboard-status-pill dashboard-status-pill-info">@lang('Weather Monitoring')</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>@lang('Station')</th>
                                        <th>@lang('Temperature')</th>
                                        <th>@lang('Humidity')</th>
                                        <th>@lang('Wind Speed')</th>
                                        <th>@lang('Solar Radiation')</th>
                                        <th>@lang('Snow Depth')</th>
                                    </tr>
                                </thead>
                                <tbody id="atmosphericConditionsBody">
                                    <tr>
                                        <td colspan="6">@lang('Loading atmospheric measurements...')</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="dashboard-panel-card">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">@lang('Ozone trend')</div>
                                <div class="dashboard-panel-subtitle">@lang('Station observations over the latest available period')</div>
                            </div>
                            <span class="dashboard-status-pill dashboard-status-pill-info" id="timeRangePill">@lang('Last 24 hours')</span>
                        </div>

                        <div class="dashboard-chart-shell" style="height: 380px; min-height: 380px;">
                            <canvas id="ozoneChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12 col-xl-6 mb-4 mb-xl-0">
                    <div class="dashboard-panel-card h-100">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">@lang('Latest station rankings')</div>
                                <div class="dashboard-panel-subtitle">@lang('Highest current station values')</div>
                            </div>
                            <span class="dashboard-status-pill dashboard-status-pill-neutral">@lang('Latest')</span>
                        </div>

                        <div class="dashboard-station-list" id="stationList">@lang('Loading...')</div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="dashboard-panel-card h-100">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">@lang('Station comparison')</div>
                                <div class="dashboard-panel-subtitle">@lang('Latest ozone concentration by station')</div>
                            </div>
                            <span class="dashboard-status-pill dashboard-status-pill-neutral">@lang('Comparison')</span>
                        </div>

                        <div class="dashboard-chart-shell dashboard-chart-shell-sm">
                            <canvas id="stationBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campbell station status -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="dashboard-panel-card">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">Campbell Station Status</div>
                                <div class="dashboard-panel-subtitle">Latest observation times and measurement availability</div>
                            </div>
                            <span class="dashboard-status-pill dashboard-status-pill-info">4 monitoring sites</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Station</th>
                                        <th>Observation Time</th>
                                        <th>1-Hour Ozone</th>
                                        <th>8-Hour Ozone</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="campbellStatusBody">
                                    <tr><td colspan="5">Loading station status...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monitoring station map -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="dashboard-panel-card">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">@lang('Monitoring Station Map')</div>
                                <div class="dashboard-panel-subtitle">@lang('Campbell monitoring locations across the Uinta Basin')</div>
                            </div>
                            <span class="dashboard-status-pill dashboard-status-pill-info">@lang('Station Locations')</span>
                        </div>

                        <div id="uintaMonitoringMap" style="height: 400px; width: 100%; border-radius: 8px;"></div>

                        <div class="dashboard-chart-help mt-3">
                            @lang('Select a station marker to view its latest available ozone measurements.')
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
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const root = document.getElementById('realtimeOzoneRoot');
    if (!root) return;

    const url = root.dataset.jsonUrl;
    const status = document.getElementById('ozoneStatus');
    const refreshBtn = document.getElementById('btnRefreshOzone');
    let trendChart = null;
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
        if (barChart) barChart.destroy();
        trendChart = null;
        barChart = null;
    }

    function renderStats(json) {
        const stations = json.series || [];
        const latest = stations.map(station => ({ station, point: latestPoint(station) })).filter(item => item.point);
        const latestValues = latest.map(item => Number(item.point.y)).filter(value => !Number.isNaN(value));
        const avg = latestValues.length ? latestValues.reduce((sum, value) => sum + value, 0) / latestValues.length : null;
        const peak = latest.reduce((best, item) => !best || Number(item.point.y) > Number(best.point.y) ? item : best, null);
        const category = categoryForOzone(avg);

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

        renderBars(sorted.slice(0, 12));
    }

    const campbellHistory = {};

    function renderTrend() {
        const stations = [
            { key: 'seven-sisters', name: 'Seven Sisters', color: '#2eb85c' },
            { key: 'horsepool', name: 'Horsepool', color: '#3399ff' },
            { key: 'castle-peak', name: 'Castle Peak', color: '#f9b115' },
            { key: 'roosevelt', name: 'Roosevelt', color: '#8b5cf6' }
        ];

        const datasets = stations.map(station => {
            const observations = campbellHistory[station.key] || [];

            const data = observations
                .map(point => {
                    const timestamp = String(point.x || '').trim();

                    // Campbell timestamps are station-local clock times.
                    // Parse consistently without assuming UTC.
                    const date = new Date(
                        timestamp.replace(' ', 'T')
                    );

                    const value = Number(point.y);

                    if (!Number.isFinite(date.getTime()) ||
                        !Number.isFinite(value)) {
                        return null;
                    }

                    return { x: date.getTime(), y: value };
                })
                .filter(Boolean);

            return {
                label: station.name,
                data,
                parsing: false,
                tension: 0.3,
                pointRadius: data.length === 1 ? 6 : 0,
                pointHoverRadius: 6,
                borderWidth: 2.5,
                borderColor: station.color,
                backgroundColor: station.color,
                showLine: data.length > 1,
                spanGaps: false
            };
        });

        if (trendChart) trendChart.destroy();

        trendChart = new Chart(document.getElementById('ozoneChart'), {
            type: 'line',
            data: { datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'nearest',
                    intersect: false
                },
                scales: {
                    x: {
                        type: 'linear',
                        ticks: {
                            color: chartTextColor(),
                            maxRotation: 0,
                            maxTicksLimit: 8,
                            callback: value => {
                                const date = new Date(value);
                                return date.toLocaleString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                    hour: 'numeric'
                                });
                            }
                        },
                        title: {
                            display: true,
                            text: 'Observation time',
                            color: chartMutedColor()
                        },
                        grid: { color: chartGridColor() },
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '1-hour ozone (ppb)',
                            color: chartMutedColor()
                        },
                        grid: { color: chartGridColor() },
                        ticks: { color: chartTextColor() }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: chartTextColor(),
                            boxWidth: 12,
                            usePointStyle: true,
                            padding: 18
                        }
                    },
                    tooltip: {
                        backgroundColor: chartTooltipBg(),
                        titleColor: chartTextColor(),
                        bodyColor: chartTextColor(),
                        borderColor: chartGridColor(),
                        borderWidth: 1,
                        callbacks: {
                            label: context =>
                                `${context.dataset.label}: ${context.parsed.y.toFixed(1)} ppb`
                        }
                    }
                }
            }
        });
    }

    async function loadCampbellHistory() {
        const sites = [
            'seven-sisters',
            'horsepool',
            'castle-peak',
            'roosevelt'
        ];

        await Promise.all(sites.map(async site => {
            try {
                const response = await fetch(
                    `/data/realtime/logger/${site}/history/json`,
                    { headers: { 'Accept': 'application/json' } }
                );

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const json = await response.json();

                if (!json.ok) {
                    throw new Error('Historical measurements unavailable');
                }

                campbellHistory[site] = json.data || [];
            } catch (error) {
                console.error(`Campbell history ${site}:`, error);
                campbellHistory[site] = [];
            }
        }));

        renderTrend();
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

    function formatCampbellTime(timestamp) {
        if (!timestamp) return 'Unavailable';

        const date = new Date(String(timestamp).trim().replace(' ', 'T'));

        if (Number.isNaN(date.getTime())) {
            return String(timestamp);
        }

        return date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    function renderCampbellRankings() {
        const colors = {
            'seven-sisters': '#2eb85c',
            'horsepool': '#3399ff',
            'castle-peak': '#f9b115',
            'roosevelt': '#8b5cf6'
        };

        const rows = campbellSites.map(site => {
            const record = campbellWeather[site];
            if (!record) return null;

            const raw = site === 'roosevelt'
                ? record.O3_Hr
                : record.O3_ppb_1h;

            if (raw === null || raw === undefined || raw === '') {
                return null;
            }

            const value = Number(raw);
            if (!Number.isFinite(value)) return null;

            return {
                site,
                name: campbellNames[site],
                value,
                timestamp: record.TimeStamp || ''
            };
        }).filter(Boolean).sort((a, b) => b.value - a.value);

        document.getElementById('stationList').innerHTML = rows.map(row => `
            <div class="dashboard-station-row">
                <div>
                    <div class="dashboard-station-name">${safe(row.name)}</div>
                    <div class="dashboard-station-meta">${safe(formatCampbellTime(row.timestamp))}</div>
                </div>
                <div class="dashboard-station-value" style="color:${colors[row.site]};">
                    ${number(row.value)} ppb
                </div>
            </div>
        `).join('') || '<div class="dashboard-control-help">No Campbell ozone measurements available.</div>';

        if (barChart) barChart.destroy();

        barChart = new Chart(document.getElementById('stationBarChart'), {
            type: 'bar',
            data: {
                labels: rows.map(row => row.name),
                datasets: [{
                    label: '1-hour ozone (ppb)',
                    data: rows.map(row => row.value),
                    backgroundColor: rows.map(row => colors[row.site]),
                    borderRadius: 5,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: chartTextColor() }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Ozone (ppb)',
                            color: chartMutedColor()
                        },
                        grid: { color: chartGridColor() },
                        ticks: { color: chartTextColor() }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: context =>
                                `${context.parsed.y.toFixed(1)} ppb`
                        }
                    }
                }
            }
        });
    }

    function renderAll(json) {
        latestJson = json;
        // Synoptic data remains available without overwriting Campbell rankings.
    }

    async function loadOzone() {
        status.textContent = 'Loading...';
        refreshBtn.disabled = true;

        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) throw new Error('Request failed');

            const json = await response.json();
            renderAll(json);
            // Campbell station availability is displayed after its measurements load.
        } catch (error) {
            console.error(error);
            status.textContent = 'Unable to load ozone data.';
        } finally {
            refreshBtn.disabled = false;
        }
    }

    const campbellSites = ['seven-sisters', 'horsepool', 'castle-peak', 'roosevelt'];

    // Campbell monitoring station map
    const monitoringLocations = {
        'seven-sisters': [39.9826, -109.3443],
        'horsepool': [40.144, -109.467],
        'castle-peak': [40.0509, -110.0194],
        'roosevelt': [40.2942, -110.0090]
    };

    let monitoringMap = null;
    const monitoringMarkers = {};

    function initializeMonitoringMap() {
        const container = document.getElementById('uintaMonitoringMap');

        if (!container || typeof L === 'undefined') return;

        monitoringMap = L.map(container).setView([40.12, -109.7], 9);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(monitoringMap);

        Object.entries(monitoringLocations).forEach(([site, coordinates]) => {
            const marker = L.circleMarker(coordinates, {
                radius: 9,
                color: '#ffffff',
                weight: 2,
                fillColor: '#3399ff',
                fillOpacity: 0.9
            }).addTo(monitoringMap);

            monitoringMarkers[site] = marker;
        });

        monitoringMap.fitBounds(
            Object.values(monitoringLocations),
            { padding: [40, 40] }
        );

        setTimeout(() => monitoringMap.invalidateSize(), 200);
    }

    function updateMonitoringMap() {
        Object.entries(monitoringMarkers).forEach(([site, marker]) => {
            const record = campbellWeather[site];

            const name = campbellNames[site] || site;

            if (!record) {
                marker.setStyle({ fillColor: '#6b7280' });
                marker.bindPopup(
                    `<strong>${safe(name)}</strong><br>Measurements unavailable`
                );
                return;
            }

            const oneHour = site === 'roosevelt'
                ? record.O3_Hr
                : record.O3_ppb_1h;

            const eightHour = site === 'roosevelt'
                ? record.O3_Eight_Hr
                : record.O3_ppb_8h;

            const ozone = Number(oneHour);
            const validOzone = oneHour !== null &&
                oneHour !== undefined &&
                Number.isFinite(ozone);

            marker.setStyle({
                fillColor: validOzone ? colorForOzone(ozone) : '#6b7280'
            });

            marker.bindPopup(`
                <strong>${safe(name)}</strong><br>
                1-hour ozone: ${validOzone ? number(ozone) + ' ppb' : 'Unavailable'}<br>
                8-hour ozone: ${number(eightHour)} ppb<br>
                Observed: ${safe(formatCampbellTime(record.TimeStamp))}
            `);
        });
    }

    const campbellWeather = {};

    const campbellNames = {
        'seven-sisters': 'Seven Sisters',
        'horsepool': 'Horsepool',
        'castle-peak': 'Castle Peak',
        'roosevelt': 'Roosevelt'
    };

    function weatherValue(value, unit, digits = 1) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }

        const numeric = Number(value);
        if (!Number.isFinite(numeric)) return '—';

        return `${numeric.toFixed(digits)} ${unit}`;
    }

    function renderAtmosphericConditions() {
        const body = document.getElementById('atmosphericConditionsBody');
        if (!body) return;

        body.innerHTML = campbellSites.map(site => {
            const record = campbellWeather[site];

            if (!record) {
                return `<tr>
                    <td><strong>${campbellNames[site]}</strong></td>
                    <td colspan="5">Measurements unavailable</td>
                </tr>`;
            }

            const temperature = site === 'roosevelt'
                ? record.Temp_2m
                : site === 'castle-peak'
                    ? record.temp5m_C_1h
                    : record.temp10m_C_1h;

            const humidity = site === 'roosevelt'
                ? record.RH
                : record.RH_pcnt_1h;

            const wind = site === 'roosevelt'
                ? null
                : record.Wspd_ms_1h;

            const solar = site === 'roosevelt'
                ? null
                : record.solarrad_Wm2_1h;

            const snow = site === 'roosevelt'
                ? null
                : record.snowdepth_cm_4h;

            return `<tr>
                <td><strong>${campbellNames[site]}</strong></td>
                <td>${weatherValue(temperature, '°C')}</td>
                <td>${weatherValue(humidity, '%')}</td>
                <td>${weatherValue(wind, 'm/s')}</td>
                <td>${weatherValue(solar, 'W/m²')}</td>
                <td>${weatherValue(snow, 'cm', 2)}</td>
            </tr>`;
        }).join('');
    }

    function renderCampbellSummary() {
        const oneHour = [];
        const eightHour = [];

        campbellSites.forEach(site => {
            const record = campbellWeather[site];
            if (!record) return;

            const rawOneHour = site === 'roosevelt'
                ? record.O3_Hr
                : record.O3_ppb_1h;

            const rawEightHour = site === 'roosevelt'
                ? record.O3_Eight_Hr
                : record.O3_ppb_8h;

            if (rawOneHour !== null && rawOneHour !== undefined &&
                rawOneHour !== '' && Number.isFinite(Number(rawOneHour))) {
                oneHour.push({
                    site,
                    value: Number(rawOneHour)
                });
            }

            if (rawEightHour !== null && rawEightHour !== undefined &&
                rawEightHour !== '' && Number.isFinite(Number(rawEightHour))) {
                eightHour.push(Number(rawEightHour));
            }
        });

        const average = values => values.length
            ? values.reduce((sum, value) => sum + value, 0) / values.length
            : null;

        const basinAverage = average(oneHour.map(item => item.value));
        const eightHourAverage = average(eightHour);

        const peak = oneHour.reduce(
            (best, item) => !best || item.value > best.value ? item : best,
            null
        );

        setText('latestAvg', number(basinAverage));
        setText('latestAvgLabel',
            `${oneHour.length} station(s) with available 1-hour measurements`);

        setText('peakValue', number(peak ? peak.value : null));
        setText('peakLabel',
            peak ? campbellNames[peak.site] : 'No measurements available');

        setText('stationCount', oneHour.length);
        setText('categoryValue', number(eightHourAverage));

        const categoryEl = document.getElementById('categoryValue');
        if (categoryEl) categoryEl.className = 'dashboard-stat-value';
    }

    function renderCampbellStatus() {
        const body = document.getElementById('campbellStatusBody');
        if (!body) return;

        body.innerHTML = campbellSites.map(site => {
            const record = campbellWeather[site];
            const name = campbellNames[site];

            if (!record) {
                return `<tr>
                    <td><strong>${safe(name)}</strong></td>
                    <td>—</td>
                    <td>—</td>
                    <td>—</td>
                    <td><span class="badge badge-secondary">Unavailable</span></td>
                </tr>`;
            }

            const oneHour = site === 'roosevelt'
                ? record.O3_Hr
                : record.O3_ppb_1h;

            const eightHour = site === 'roosevelt'
                ? record.O3_Eight_Hr
                : record.O3_ppb_8h;

            const valid = oneHour !== null &&
                oneHour !== undefined &&
                oneHour !== '' &&
                Number.isFinite(Number(oneHour));

            return `<tr>
                <td><strong>${safe(name)}</strong></td>
                <td>${safe(formatCampbellTime(record.TimeStamp))}</td>
                <td>${valid ? number(Number(oneHour)) + ' ppb' : '—'}</td>
                <td>${eightHour !== null && eightHour !== undefined &&
                    eightHour !== '' && Number.isFinite(Number(eightHour))
                    ? number(Number(eightHour)) + ' ppb' : '—'}</td>
                <td><span class="badge ${valid ? 'badge-success' : 'badge-warning'}">
                    ${valid ? 'Measurement available' : 'Missing ozone'}
                </span></td>
            </tr>`;
        }).join('');
    }

    async function loadCampbellStations() {
        Object.keys(campbellWeather).forEach(key => delete campbellWeather[key]);

        await Promise.all(campbellSites.map(async site => {
            const ozoneEl = document.getElementById(`campbell-${site}-ozone`);
            const eightHourEl = document.getElementById(`campbell-${site}-8h`);
            const statusEl = document.getElementById(`campbell-${site}-status`);

            try {
                const response = await fetch(
                    `/data/realtime/logger/${site}/json`,
                    { headers: { 'Accept': 'application/json' } }
                );

                if (!response.ok) throw new Error('Station request failed');

                const json = await response.json();
                if (!json.ok || !json.record) throw new Error('No station measurements');

                const record = json.record;
                campbellWeather[site] = record;

                const oneHour = site === 'roosevelt'
                    ? record.O3_Hr
                    : record.O3_ppb_1h;

                const eightHour = site === 'roosevelt'
                    ? record.O3_Eight_Hr
                    : record.O3_ppb_8h;

                ozoneEl.innerHTML = oneHour !== null && oneHour !== undefined
                    ? `${number(oneHour)} <span class="dashboard-stat-unit">ppb</span>`
                    : '-- <span class="dashboard-stat-unit">ppb</span>';

                eightHourEl.textContent = number(eightHour);

                statusEl.textContent = record.TimeStamp
                    ? `Observed: ${formatCampbellTime(record.TimeStamp)}`
                    : 'Observation time unavailable';

            } catch (error) {
                console.error(`Campbell station ${site}:`, error);
                ozoneEl.innerHTML = '-- <span class="dashboard-stat-unit">ppb</span>';
                eightHourEl.textContent = '--';
                statusEl.textContent = 'Measurements unavailable';
            }
        }));

        renderAtmosphericConditions();
        updateMonitoringMap();
        renderCampbellSummary();
        renderCampbellStatus();
        renderCampbellRankings();

        const reporting = campbellSites.filter(site => {
            const record = campbellWeather[site];
            if (!record) return false;

            const raw = site === 'roosevelt'
                ? record.O3_Hr
                : record.O3_ppb_1h;

            return raw !== null &&
                raw !== undefined &&
                raw !== '' &&
                Number.isFinite(Number(raw));
        }).length;

        status.textContent =
            `${reporting} Campbell monitoring station(s) reporting | Updated ${new Date().toLocaleString()}`;
    }

    refreshBtn.addEventListener('click', () => {
        loadOzone();
        loadCampbellStations();
        loadCampbellHistory();
    });

    window.addEventListener('brc:theme-changed', () => {
        if (latestJson) {
            destroyCharts();
            renderAll(latestJson);
        }
        renderCampbellRankings();
        renderTrend();
    });

    initializeMonitoringMap();
    loadOzone();
    loadCampbellStations();
    loadCampbellHistory();
})();
</script>
@endpush