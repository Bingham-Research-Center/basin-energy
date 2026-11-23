@extends('frontend.layouts.app')

@section('title', __('NASA Pandora Project'))

@push('after-styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
<div class="dashboard-page dashboard-page-pro" id="pandoraDashboard">

    <div class="card dashboard-chart-card mb-4">
        <div class="card-header">
            <div>
                <h2 class="dashboard-chart-title mb-0">
                    NASA Pandora Project
                </h2>
                <div class="dashboard-chart-subtitle">
                    Uintah Basin Atmospheric Monitoring   
             </div
>
            </div>
        </div>

        <div class="card-body">

            <div class="dashboard-panel-card mb-4">
                <div class="dashboard-panel-header">
                    <div>
                        <div class="dashboard-panel-title">
                            About the NASA Pandora Project
                        </div>
                        <div class="dashboard-panel-subtitle">
                            Ground-based atmospheric monitoring in Utah's Uintah Basin
                        </div>
                    </div>
                </div>

                <p class="mb-0">
                    The Pandora instrument system uses ground-based
                    spectroscopy to measure atmospheric trace gases.
                    These observations support research into air quality,
                    atmospheric chemistry, and pollution processes. This dashboard displays atmospheric NO₂ measurements
from the DinosaurUT Pandora monitoring station near
the Uintah Basin. Measurements are retrieved from the
Pandonia Global Network and may not be quality-assured.
                </p>
            </div>

            <div class="row dashboard-metric-row mb-4">

                <div class="col-12 col-md-6 col-xl-3 mb-3">
                    <div class="dashboard-stat-card card h-100">
                        <div class="card-body">
                            <div class="dashboard-stat-label">
                                Latest NO₂ Measurement
                            </div>
<div class="dashboard-stat-value" id="pandoraLatestNo2">
    --
</div>
<div class="dashboard-stat-note" id="pandoraLatestNo2Quality">
    Latest in loaded date range · Not quality-assured
</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3 mb-3">
                    <div class="dashboard-stat-card card h-100">
                        <div class="card-body">
                            <div class="dashboard-stat-label">
                                Latest Observation
                            </div>
<div class="dashboard-stat-value" id="pandoraLatestObservation">
    --
</div>
<div class="dashboard-stat-note">
    Latest observation in loaded date range
</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3 mb-3">
                    <div class="dashboard-stat-card card h-100">
                        <div class="card-body">
<div class="dashboard-stat-label">
    Monitoring Stations
</div>
<div class="dashboard-stat-value">
    1
</div>
<div class="dashboard-stat-note">
    DinosaurUT (Pandora95)
</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3 mb-3">
                    <div class="dashboard-stat-card card h-100">
                        <div class="card-body">
<div class="dashboard-stat-label">
    Data Status
</div>
<div class="dashboard-stat-value" id="pandoraDataStatus">
    Pending
</div>
<div class="dashboard-stat-note" id="pandoraDataStatusNote">
    Waiting for Pandora data
</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- NASA Pandora Monitoring Map -->
<div class="dashboard-panel-card mb-4">

    <div class="dashboard-panel-header">
        <div>
            <div class="dashboard-panel-title">
                Uintah Basin Monitoring Map
            </div>
            <div class="dashboard-panel-subtitle">
                NASA Pandora atmospheric monitoring locations
            </div>
        </div>

<span class="dashboard-status-pill dashboard-status-pill-info">
    1 Confirmed Station
</span>
    </div>

    <div class="p-3">
        <div
            id="pandoraMonitoringMap"
            style="height: 420px; width: 100%; border-radius: 8px;"
        ></div>

<div class="dashboard-stat-note mt-3">
    Map showing the DinosaurUT (Pandora95) monitoring station
    near Dinosaur National Monument, Utah.
    Station coordinates provided by the Pandonia Global Network.
</div>
    </div>

</div>

<div class="alert alert-info mb-0">
    <strong>NASA Pandora Data:</strong>
    Atmospheric NO₂ measurements are retrieved from the
    Pandonia Global Network (PGN) for the DinosaurUT
    monitoring station (Pandora95).

    <strong>Data Quality Notice:</strong>
    Some observations may not be quality-assured.
    Measurements should be interpreted according to
    their reported quality flags.
</div>
<!-- NASA Pandora Atmospheric Measurements -->
<div class="dashboard-panel-card mb-4">

    <div class="dashboard-panel-header">
        <div>
            <div class="dashboard-panel-title">
                Atmospheric Measurements
            </div>
            <div class="dashboard-panel-subtitle">
                NASA Pandora atmospheric trace gas observations over time
            </div>
        </div>

        <span class="dashboard-status-pill dashboard-status-pill-info">
PGN Data · Quality Flags Available
        </span>
    </div>

    <!-- Chart Filters -->
    <div class="row p-3">

        <div class="col-12 col-md-3 mb-3">
            <label for="pandoraGasFilter">Atmospheric Gas</label>
            <select id="pandoraGasFilter" class="form-control">
                <option value="NO2">NO₂</option>
            </select>
            <small class="text-muted">
                Additional gases depend on dataset availability.
            </small>
        </div>

        <div class="col-12 col-md-3 mb-3">
            <label for="pandoraStationFilter">Monitoring Station</label>
<select id="pandoraStationFilter" class="form-control">
    <option value="DinosaurUT" selected>
        DinosaurUT (Pandora95)
    </option>
</select>
        </div>

        <div class="col-12 col-md-3 mb-3">
            <label for="pandoraStartDate">Start Date</label>
            <input
                type="date"
                id="pandoraStartDate"
                class="form-control"
value="{{ now('UTC')->subDays(7)->format('Y-m-d') }}"
            >

        </div>

        <div class="col-12 col-md-3 mb-3">
            <label for="pandoraEndDate">End Date</label>
<input
    type="date"
    id="pandoraEndDate"
    class="form-control"
value="{{ now('UTC')->format('Y-m-d') }}"
>
        </div>
<div class="col-12 mb-3">
    <button
        type="button"
        id="pandoraApplyFilters"
        class="btn btn-primary"
    >
        Apply Filters
    </button>
</div>
    </div>

    <!-- Chart -->
    <div class="p-3 pt-0">

        <div style="height: 350px; position: relative;">
            <canvas id="pandoraTimeSeriesChart"></canvas>
        </div>

<div class="dashboard-stat-note mt-3" id="pandoraChartStatus">
    Loading NO₂ observations from the Pandonia Global Network...
</div>

    </div>

</div>

        </div>
    </div>

</div>
@endsection
@push('after-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
(function () {
    'use strict';

    const mapElement = document.getElementById('pandoraMonitoringMap');

    if (!mapElement || typeof L === 'undefined') {
        return;
    }

    // Approximate center of Utah's Uintah Basin.
    // This is a regional map center, not a Pandora station location.
    const basinCenter = [40.15, -109.45];

    const map = L.map(mapElement, {
        center: basinCenter,
        zoom: 9,
        scrollWheelZoom: false
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Verified Pandonia Global Network monitoring station.
    const dinosaurStation = {
        name: 'DinosaurUT',
        instrument: 'Pandora95',
        latitude: 40.4373,
        longitude: -109.3046
    };

    L.marker([
        dinosaurStation.latitude,
        dinosaurStation.longitude
    ])
        .addTo(map)
        .bindPopup(`
            <div>
                <strong>DinosaurUT</strong><br>
                Dinosaur National Monument, Utah<br><br>
                <strong>Instrument:</strong> Pandora95<br>
                <strong>Network:</strong> Pandonia Global Network<br>
                <strong>Measurements:</strong> NO₂<br>
                <strong>Latitude:</strong> 40.4373<br>
                <strong>Longitude:</strong> -109.3046
            </div>
        `);

    // Ensure the map renders correctly inside the dashboard card.
    setTimeout(function () {
        map.invalidateSize();
    }, 250);

})();
</script>
<script>
(function () {
    'use strict';

    const canvas = document.getElementById('pandoraTimeSeriesChart');

    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    const chart = new Chart(canvas, {
        type: 'line',

        data: {
            labels: [],
            datasets: [{
                label: 'NO₂ (Not Quality-Assured)',
                data: [],
                borderColor: '#38bdf8',
                backgroundColor: 'rgba(56, 189, 248, 0.12)',
                borderWidth: 2,
                pointRadius: 3,
                tension: 0.2,
                fill: false
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: true,
                    labels: {
                        color: '#cbd5e1'
                    }
                },
                tooltip: {
                    enabled: true
                }
            },

            scales: {
                x: {
                    ticks: {
                        color: '#cbd5e1',
                        maxTicksLimit: 8
                    },
                    title: {
                        display: true,
                        text: 'Observation Time (UTC)',
                        color: '#cbd5e1'
                    }
                },

                y: {
                    ticks: {
                        color: '#cbd5e1'
                    },
                    title: {
                        display: true,
                        text: 'Tropospheric NO₂ Column (mol/m²)',
                        color: '#cbd5e1'
                    }
                }
            }
        }
    });

async function loadPandoraData(isInitialLoad = false) {
    const startDate = document.getElementById('pandoraStartDate').value;
    const endDate = document.getElementById('pandoraEndDate').value;
    const applyButton = document.getElementById('pandoraApplyFilters');

    if (!startDate || !endDate) {
        alert('Please select both a start date and an end date.');
        return;
    }

    const start = new Date(startDate + 'T00:00:00Z');
    const end = new Date(endDate + 'T00:00:00Z');
    const days = (end - start) / 86400000;

    if (!Number.isFinite(days) || days <= 0 || days > 7) {
        alert('Please select a date range greater than 0 and no more than 7 days.');
        return;
    }

    applyButton.disabled = true;

    try {
        const url = '/data/realtime/pandora/json?' +
            new URLSearchParams({
                start: startDate,
                end: endDate
            });

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });
            if (!response.ok) {
                throw new Error('Pandora API returned HTTP ' + response.status);
            }

            const result = await response.json();
let observations = result.observations || [];

// Search earlier dates only during the initial dashboard load.
if (isInitialLoad === true && observations.length === 0) {
    const formatUtcDate = date => date.toISOString().slice(0, 10);

    for (let attempt = 1; attempt <= 4; attempt++) {
        const fallbackEnd = new Date(
            end.getTime() - (attempt * 7 * 86400000)
        );

        const fallbackStart = new Date(
            fallbackEnd.getTime() - (7 * 86400000)
        );

        const fallbackStartDate = formatUtcDate(fallbackStart);
        const fallbackEndDate = formatUtcDate(fallbackEnd);

        console.info(
            'Pandora: Checking earlier dates ' +
            fallbackStartDate + ' to ' + fallbackEndDate
        );

        const fallbackUrl = '/data/realtime/pandora/json?' +
            new URLSearchParams({
                start: fallbackStartDate,
                end: fallbackEndDate
            });

        const fallbackResponse = await fetch(fallbackUrl, {
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!fallbackResponse.ok) {
            throw new Error(
                'Pandora fallback API returned HTTP ' +
                fallbackResponse.status
            );
        }

        const fallbackResult = await fallbackResponse.json();
        const fallbackObservations =
            fallbackResult.observations || [];

        if (fallbackObservations.length > 0) {
            observations = fallbackObservations;

            document.getElementById('pandoraStartDate').value =
                fallbackStartDate;

            document.getElementById('pandoraEndDate').value =
                fallbackEndDate;

            console.info(
                'Pandora: Found ' + observations.length +
                ' observations in an earlier date range.'
            );

            break;
        }
    }
}

// Update the NASA Pandora data connection status.
const dataStatusElement = document.getElementById('pandoraDataStatus');
const dataStatusNoteElement = document.getElementById('pandoraDataStatusNote');

const assuredCount = observations.filter(
    row => row.quality_assured === true
).length;

// Update the chart information message.
const chartStatusElement = document.getElementById('pandoraChartStatus');

if (chartStatusElement) {
    if (observations.length > 0) {
        chartStatusElement.textContent =
            observations.length + ' NO₂ observations loaded from ' +
            'DinosaurUT (Pandora95). ' +
            assuredCount + ' quality-assured; ' +
            (observations.length - assuredCount) +
            ' not quality-assured. Times shown in UTC.';
    } else {
        chartStatusElement.textContent =
            'No NO₂ observations are available for the selected dates.';
    }
}

if (dataStatusElement && dataStatusNoteElement) {
    if (observations.length > 0) {
        dataStatusElement.textContent = 'Connected';

        dataStatusNoteElement.textContent =
            observations.length + ' observations loaded · ' +
            assuredCount + ' quality-assured';
    } else {
        dataStatusElement.textContent = 'No Data';
        dataStatusNoteElement.textContent =
            'No observations found for the selected dates';
    }
}
// Update the latest NO2 measurement card.
const latestNo2Element = document.getElementById('pandoraLatestNo2');

const latestNo2QualityElement =
    document.getElementById('pandoraLatestNo2Quality');

if (latestNo2Element) {
    if (observations.length > 0) {
        const latest = observations[observations.length - 1];

        latestNo2Element.textContent =
            latest.no2.toExponential(3) + ' mol/m²';

        if (latestNo2QualityElement) {
            latestNo2QualityElement.textContent =
                'Latest in loaded date range · ' +
                (latest.quality_assured === true
                    ? 'Quality-assured'
                    : 'Not quality-assured');
        }
    } else {
        latestNo2Element.textContent = '--';

        if (latestNo2QualityElement) {
            latestNo2QualityElement.textContent =
                'No measurement available';
        }
    }
}

// Update the latest observation timestamp.
const latestObservationElement =
    document.getElementById('pandoraLatestObservation');

if (latestObservationElement) {
    if (observations.length > 0) {
        const latest = observations[observations.length - 1];
        const timestamp = latest.timestamp;

        // Convert PGN timestamp YYYYMMDDTHHMMSS into a readable UTC date.
        const year = timestamp.substring(0, 4);
        const month = timestamp.substring(4, 6);
        const day = timestamp.substring(6, 8);
        const hour = timestamp.substring(9, 11);
        const minute = timestamp.substring(11, 13);

        latestObservationElement.textContent =
            `${year}-${month}-${day} ${hour}:${minute} UTC`;
    } else {
        latestObservationElement.textContent = '--';
    }
}
            chart.data.labels = observations.map(row => {
                const timestamp = row.timestamp;

                return timestamp.substring(0, 4) + '-' +
                    timestamp.substring(4, 6) + '-' +
                    timestamp.substring(6, 8) + ' ' +
                    timestamp.substring(9, 11) + ':' +
                    timestamp.substring(11, 13);
            });

            chart.data.datasets[0].data = observations.map(row => row.no2);

            chart.update();

            console.info(
                'NASA Pandora: Loaded ' + observations.length +
                ' NO₂ observations.'
            );

} catch (error) {
    console.error('NASA Pandora data loading failed:', error);

    // Clear measurements from the previous successful request.
    chart.data.labels = [];
    chart.data.datasets.forEach(dataset => {
        dataset.data = [];
    });
    chart.update();

    // Clear the latest measurement and observation cards.
    const latestNo2Element = document.getElementById('pandoraLatestNo2');
    const latestObservationElement =
        document.getElementById('pandoraLatestObservation');

    if (latestNo2Element) {
        latestNo2Element.textContent = '--';
    }

    if (latestObservationElement) {
        latestObservationElement.textContent = '--';
    }

// Clear the quality label when the API request fails.
const qualityElement =
    document.getElementById('pandoraLatestNo2Quality');

if (qualityElement) {
    qualityElement.textContent = 'Measurement unavailable';
}

    // Explain why the graph is empty.
    const chartStatusElement = document.getElementById('pandoraChartStatus');

    if (chartStatusElement) {
        chartStatusElement.textContent =
            'Unable to load NO₂ observations for the selected dates.';
    }

    const statusElement = document.getElementById('pandoraDataStatus');
    const noteElement = document.getElementById('pandoraDataStatusNote');

    if (statusElement) {
        statusElement.textContent = 'Error';
    }

    if (noteElement) {
        noteElement.textContent = 'Unable to load Pandora observations.';
    }
} finally {
    applyButton.disabled = false;
}
    }

document.getElementById('pandoraApplyFilters')
    .addEventListener('click', () => loadPandoraData(false));

loadPandoraData(true);

})();
</script>
@endpush
