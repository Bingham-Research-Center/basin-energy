@extends('frontend.layouts.app')

@section('title', $site['name'] ?? __('Campbell Logger'))

@push('after-styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

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
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endonce
<script>
(function () {
    const root = document.getElementById('horsepoolRoot');
    if (!root) return;

    const url = root.dataset.jsonUrl;
    const historyUrl = url.replace(/\/json$/, '/history/json');
    const status = document.getElementById('horsepoolStatus');
    const refreshBtn = document.getElementById('btnRefreshHorsepool');
    const content = document.getElementById('horsepoolContent');
    const charts = [];
    let latestRecord = null;
    let latestMeta = null;

    const siteKey = @json($siteKey);
    const stationLatitude = @json($site['latitude'] ?? null);
    const stationLongitude = @json($site['longitude'] ?? null);
    const stationName = @json($site['name'] ?? 'Campbell Logger');
    const stationElevation = @json($site['elevation_ft'] ?? null);

    let stationMap = null;

    const dashboardMetrics = [
        {
            id: 'ozone1',
            title: '1-Hour Ozone',
            unit: 'ppb',
            fields: siteKey === 'roosevelt' ? ['O3_Hr'] : ['O3_ppb_1h']
        },
        {
            id: 'ozone8',
            title: '8-Hour Ozone',
            unit: 'ppb',
            fields: siteKey === 'roosevelt' ? ['O3_Eight_Hr'] : ['O3_ppb_8h']
        },
        {
            id: 'temp',
            title: 'Air Temperature',
            unit: '°C',
            fields: siteKey === 'roosevelt'
                ? ['Temp_2m']
                : siteKey === 'castle-peak'
                    ? ['temp5m_C_1h']
                    : ['temp10m_C_1h']
        },
        {
            id: 'humidity',
            title: 'Relative Humidity',
            unit: '%',
            fields: siteKey === 'roosevelt' ? ['RH'] : ['RH_pcnt_1h']
        },
        {
            id: 'wind',
            title: 'Wind Speed',
            unit: 'm/s',
            fields: siteKey === 'roosevelt' ? [] : ['Wspd_ms_1h']
        },
        {
            id: 'snow',
            title: 'Snow Depth',
            unit: 'cm',
            fields: siteKey === 'roosevelt' ? [] : ['snowdepth_cm_4h']
        }
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
        for (const field of metric.fields) {
            if (Object.prototype.hasOwnProperty.call(record, field)) {
                return {
                    key: field,
                    raw: record[field],
                    value: numeric(record[field])
                };
            }
        }

        return null;
    }

    let historyChart = null;
    let latestHistory = [];

    function renderOzoneStatistics(points) {
        const container = document.getElementById('campbellOzoneStatistics');
        if (!container) return;

        const readings = (points || [])
            .map(point => ({
                time: new Date(String(point.x).replace(' ', 'T')).getTime(),
                value: Number(point.y)
            }))
            .filter(point =>
                Number.isFinite(point.time) &&
                Number.isFinite(point.value)
            )
            .sort((a, b) => a.time - b.time);

        if (readings.length < 2) {
            container.innerHTML =
                '<div class="col-12 dashboard-chart-help">' +
                'Insufficient history for ozone statistics and trends.' +
                '</div>';
            return;
        }

        const values = readings.map(point => point.value);
        const highest = Math.max(...values);
        const lowest = Math.min(...values);
        const average = values.reduce((sum, value) => sum + value, 0) / values.length;

        const previous = readings[readings.length - 2];
        const current = readings[readings.length - 1];
        const difference = current.value - previous.value;

        const trend = difference > 0
            ? 'Increasing'
            : difference < 0
                ? 'Decreasing'
                : 'Unchanged';

        const cards = [
            ['Highest Ozone', highest.toFixed(1) + ' ppb'],
            ['Lowest Ozone', lowest.toFixed(1) + ' ppb'],
            ['Average Ozone', average.toFixed(1) + ' ppb'],
            ['Ozone Trend', trend + ' (' +
                (difference > 0 ? '+' : '') +
                difference.toFixed(1) + ' ppb)']
        ];

        container.innerHTML = cards.map(card => `
            <div class="col-12 col-md-6 col-xl-3 mb-3">
                <div class="dashboard-panel-card h-100 p-3">
                    <div class="dashboard-panel-subtitle">${safe(card[0])}</div>
                    <div style="font-size:1.35rem;font-weight:700;margin-top:8px;">
                        ${safe(card[1])}
                    </div>
                </div>
            </div>
        `).join('') + `
            <div class="col-12 dashboard-chart-help">
                Calculated from ${readings.length} available 1-hour ozone observations.
                Trend compares the two most recent observations.
            </div>
        `;
    }

    function renderHistoryChart(points) {
        renderOzoneStatistics(points);
        const canvas = document.getElementById('campbellHistoryChart');
        const message = document.getElementById('campbellHistoryMessage');

        if (!canvas || !message) return;

        if (historyChart) {
            historyChart.destroy();
            historyChart = null;
        }

        const data = (points || []).map(point => {
            const date = new Date(String(point.x).replace(' ', 'T'));
            const value = Number(point.y);

            if (Number.isNaN(date.getTime()) || !Number.isFinite(value)) {
                return null;
            }

            return { x: date.getTime(), y: value };
        }).filter(Boolean);

        if (!data.length) {
            canvas.style.display = 'none';
            message.textContent = 'Historical ozone measurements are unavailable.';
            return;
        }

        canvas.style.display = 'block';
        message.textContent = data.length === 1
            ? 'Only one historical observation is currently available.'
            : '';

        const dark = isDarkTheme();

        historyChart = new Chart(canvas, {
            type: 'line',
            data: {
                datasets: [{
                    label: '1-Hour Ozone (ppb)',
                    data,
                    borderColor: '#3399ff',
                    backgroundColor: '#3399ff',
                    borderWidth: 2,
                    pointRadius: data.length === 1 ? 5 : 2,
                    tension: 0.25
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                parsing: false,
                plugins: {
                    legend: {
                        labels: {
                            color: dark ? '#e5e7eb' : '#374151'
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'linear',
                        ticks: {
                            color: dark ? '#aab4c3' : '#6b7280',
                            callback: value => new Date(value).toLocaleTimeString(
                                'en-US',
                                { hour: 'numeric', minute: '2-digit' }
                            )
                        },
                        grid: {
                            color: dark ? '#374151' : '#e5e7eb'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Ozone (ppb)',
                            color: dark ? '#e5e7eb' : '#374151'
                        },
                        ticks: {
                            color: dark ? '#aab4c3' : '#6b7280'
                        },
                        grid: {
                            color: dark ? '#374151' : '#e5e7eb'
                        }
                    }
                }
            }
        });
    }

    async function loadCampbellHistory() {
        const message = document.getElementById('campbellHistoryMessage');
        if (message) message.textContent = 'Loading ozone history...';

        try {
            const response = await fetch(historyUrl, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) throw new Error('History request failed');

            const json = await response.json();
            latestHistory = json.data || [];

            if (!json.ok) throw new Error('History unavailable');

            renderHistoryChart(json.data || []);
        } catch (error) {
            console.error(error);
            renderHistoryChart([]);
        }
    }

    function weatherDisplay(value, unit = '') {
        if (value === null || value === undefined || value === '') {
            return 'N/A';
        }

        const number = numeric(value);

        if (number === null || !Number.isFinite(number)) {
            return 'N/A';
        }

        return number.toLocaleString(undefined, {
            maximumFractionDigits: 2
        }) + (unit ? ' ' + unit : '');
    }

    function renderAtmosphericConditions(record) {
        const container = document.getElementById('campbellAtmosphericConditions');
        if (!container) return;

        const roosevelt = siteKey === 'roosevelt';

        const conditions = [
            {
                label: 'Air Temperature',
                value: roosevelt ? record.Temp_2m :
                    siteKey === 'castle-peak' ? record.temp5m_C_1h : record.temp10m_C_1h,
                unit: '°C'
            },
            {
                label: 'Relative Humidity',
                value: roosevelt ? record.RH : record.RH_pcnt_1h,
                unit: '%'
            },
            {
                label: 'Wind Speed',
                value: roosevelt ? null : record.Wspd_ms_1h,
                unit: 'm/s'
            },
            {
                label: 'Wind Direction',
                value: roosevelt ? null : record.Wdir_deg_1h,
                unit: '°'
            },
            {
                label: 'Atmospheric Pressure',
                value: roosevelt ? null : record.press_mbar_1h,
                unit: 'mbar'
            },
            {
                label: 'Snow Depth',
                value: roosevelt ? null : record.snowdepth_cm_4h,
                unit: 'cm'
            }
        ];

        container.innerHTML = conditions.map(condition => `
            <tr>
                <td>${safe(condition.label)}</td>
                <td class="text-right">
                    <strong>${safe(weatherDisplay(condition.value, condition.unit))}</strong>
                </td>
            </tr>
        `).join('');
    }

    function renderStationMap(record) {
        const container = document.getElementById('campbellStationMap');
        if (!container) return;

        if (stationMap) {
            stationMap.remove();
            stationMap = null;
        }

        const latitude = Number(stationLatitude);
        const longitude = Number(stationLongitude);

        if (
            stationLatitude === null ||
            stationLongitude === null ||
            !Number.isFinite(latitude) ||
            !Number.isFinite(longitude)
        ) {
            container.innerHTML =
                '<div class="p-3">Station coordinates are not currently available.</div>';
            return;
        }

        if (typeof L === 'undefined') {
            container.textContent = 'Station map could not be loaded.';
            return;
        }

        stationMap = L.map(container).setView([latitude, longitude], 11);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(stationMap);

        L.marker([latitude, longitude])
            .addTo(stationMap)
             .bindPopup(
                `<strong>${safe(stationName)}</strong><br>` +
                `Ozone (1-hour): ${safe(
                    numeric(siteKey === 'roosevelt'
                        ? record.O3_Hr
                        : record.O3_ppb_1h) ?? 'N/A'
                )} ppb`
            );

        setTimeout(() => {
            if (stationMap) stationMap.invalidateSize();
        }, 100);
    }

    function render(record, meta) {
        latestRecord = record;
        latestMeta = meta;
        destroyCharts();

        const cards = dashboardMetrics.map(metric => {
            const found = findMetric(record, metric);
            const value = found?.value;

            const displayValue = value === null || value === undefined
                ? 'N/A'
                : value.toLocaleString(undefined, {
                    maximumFractionDigits: metric.id === 'snow' ? 2 : 1
                });

            return `
                <div class="col-12 col-md-6 col-xl-4 mb-3">
                    <div class="dashboard-panel-card h-100">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">${safe(metric.title)}</div>

                            </div>
                            <span class="dashboard-status-pill dashboard-status-pill-neutral">
                                ${safe(metric.unit)}
                            </span>
                        </div>

                        <div class="p-3">
                            <div class="dashboard-stat-value" style="font-size:2rem;font-weight:700;">
                                ${safe(displayValue)}
                                ${displayValue === 'N/A' ? '' : `<span class="dashboard-stat-unit">${safe(metric.unit)}</span>`}
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

            <div class="dashboard-panel-card mt-2 mb-3">
                <div class="dashboard-panel-header">
                    <div>
                        <div class="dashboard-panel-title">Ozone Concentration — Last 24 Hours</div>
                        <div class="dashboard-panel-subtitle">Historical 1-hour ozone observations</div>
                    </div>
                    <span class="dashboard-status-pill dashboard-status-pill-info">ppb</span>
                </div>
                <div class="p-3">
                    <div style="height:300px;position:relative;">
                        <canvas id="campbellHistoryChart"></canvas>
                    </div>
                    <div id="campbellHistoryMessage" class="dashboard-chart-help mt-2"></div>
                </div>
            </div>

            <div class="dashboard-panel-card mb-3">
                <div class="dashboard-panel-header">
                    <div>
                        <div class="dashboard-panel-title">Ozone Statistics</div>
                        <div class="dashboard-panel-subtitle">
                            Summary of available 1-hour ozone observations
                        </div>
                    </div>
                </div>
                <div id="campbellOzoneStatistics" class="row p-3">
                    <div class="col-12 dashboard-chart-help">
                        Loading ozone statistics...
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12 col-lg-6 mb-3">
                    <div class="dashboard-panel-card h-100">
                        <div class="dashboard-panel-header">
                            <div class="dashboard-panel-title">Atmospheric Conditions</div>
                        </div>

                        <div class="table-responsive dashboard-table-wrap">
                            <table class="table table-sm dashboard-data-table mb-0">
                                <tbody id="campbellAtmosphericConditions"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6 mb-3">
                    <div class="dashboard-panel-card h-100">
                        <div class="dashboard-panel-header">
                            <div class="dashboard-panel-title">Station Location</div>
                        </div>

                        <div id="campbellStationMap" style="height:280px;"></div>
                    </div>
                </div>
            </div>


            <div class="dashboard-panel-card mb-3">
                <div class="dashboard-panel-header">
                    <div>
                        <div class="dashboard-panel-title">Station Information</div>
                        <div class="dashboard-panel-subtitle">
                            Monitoring site details
                        </div>
                    </div>
                </div>
                <div class="table-responsive dashboard-table-wrap">
                    <table class="table table-sm dashboard-data-table mb-0">
                        <tbody id="campbellStationInformation"></tbody>
                    </table>
                </div>
            </div>

<div class="dashboard-panel-card mt-2">
                <div class="dashboard-panel-header">
                    <div>
                        <div class="dashboard-panel-title">Latest logger packet</div>
                        <div class="dashboard-panel-subtitle">${safe(meta?.station || 'Horsepool')} · ${safe(meta?.source || 'Campbell logger')}</div>
                    </div>
                    <span class="dashboard-status-pill dashboard-status-pill-info">Realtime</span>
                </div>

                <details class="campbell-details"><summary class="p-3" style="cursor:pointer;font-weight:600;">Show detailed measurements</summary><div class="table-responsive dashboard-table-wrap">
                    <table class="table table-sm dashboard-data-table mb-0">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Latest value</th>
                            </tr>
                        </thead>
                        <tbody>${rows || '<tr><td colspan="2">No record values returned.</td></tr>'}</tbody>
                    </table>
                </div></details>
            </div>

            <div class="dashboard-chart-help mt-3">
                <i class="c-icon cil-info mr-1"></i>
                Latest packet values are parsed directly from the Campbell logger endpoint.
            </div>`;

        renderAtmosphericConditions(record);
        renderStationMap(record);
        renderStationInformation(record);
    }

    function renderStationInformation(record) {
        const container = document.getElementById('campbellStationInformation');
        if (!container) return;

        const elevation = stationElevation === null
            ? 'Not available'
            : Number(stationElevation).toLocaleString('en-US') + ' ft';

        const latitude = stationLatitude === null
            ? 'Not available'
            : Math.abs(Number(stationLatitude)).toFixed(4) +
              (Number(stationLatitude) >= 0 ? ' N' : ' S');

        const longitude = stationLongitude === null
            ? 'Not available'
            : Math.abs(Number(stationLongitude)).toFixed(4) +
              (Number(stationLongitude) >= 0 ? ' E' : ' W');

        const details = [
            ['Station Name', stationName],
            ['Monitoring Network', 'Campbell Logger'],
            ['Elevation', elevation],
            ['Latitude', latitude],
            ['Longitude', longitude],
            ['Latest Observation', formatObservationTime(record.TimeStamp)]
        ];

        container.innerHTML = details.map(([label, value]) =>
            '<tr><td>' + safe(label) + '</td><td>' +
            safe(value) + '</td></tr>'
        ).join('');
    }

    function formatObservationTime(timestamp) {
        if (!timestamp) return 'Unavailable';

        const parts = String(timestamp).trim().split(/[- :T]/);
        if (parts.length < 5) return String(timestamp);

        const year = Number(parts[0]);
        const monthNumber = Number(parts[1]);
        const day = Number(parts[2]);
        const hour = Number(parts[3]);
        const minute = Number(parts[4]);

        if (!year || monthNumber < 1 || monthNumber > 12 ||
            day < 1 || day > 31 || hour < 0 || hour > 23 ||
            minute < 0 || minute > 59) {
            return String(timestamp);
        }

        const months = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];

        const period = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        const displayMinute = String(minute).padStart(2, '0');

        return `${months[monthNumber - 1]} ${day}, ${displayHour}:${displayMinute} ${period}`;
    }

    async function loadHorsepool() {
        status.textContent = 'Loading...';
        refreshBtn.disabled = true;

        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' }});
            if (!response.ok) throw new Error('Request failed');

            const json = await response.json();
            render(json.record || {}, json.meta || {});
            status.textContent = `Observed: ${formatObservationTime(json.record?.TimeStamp)}`;
            await loadCampbellHistory();
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
            renderHistoryChart(latestHistory);
        }
    });

    loadHorsepool();
})();
</script>
@endpush