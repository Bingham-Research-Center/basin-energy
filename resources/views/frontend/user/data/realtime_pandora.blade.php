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
                </div>
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
                    atmospheric chemistry, and pollution processes.
                    This dashboard will focus exclusively on verified
                    Pandora observations collected within the Uintah Basin.
                </p>
            </div>

            <div class="row dashboard-metric-row mb-4">

                <div class="col-12 col-md-6 col-xl-3 mb-3">
                    <div class="dashboard-stat-card card h-100">
                        <div class="card-body">
                            <div class="dashboard-stat-label">
                                Latest NO₂ Measurement
                            </div>
                            <div class="dashboard-stat-value">
                                --
                            </div>
                            <div class="dashboard-stat-note">
                                Awaiting verified Pandora data
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
                            <div class="dashboard-stat-value">
                                --
                            </div>
                            <div class="dashboard-stat-note">
                                No observations loaded
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
                                --
                            </div>
                            <div class="dashboard-stat-note">
                                Uintah Basin stations to be verified
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
                            <div class="dashboard-stat-value">
                                Pending
                            </div>
                            <div class="dashboard-stat-note">
                                NASA data connection not configured
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
            Station verification pending
        </span>
    </div>

    <div class="p-3">
        <div
            id="pandoraMonitoringMap"
            style="height: 420px; width: 100%; border-radius: 8px;"
        ></div>

        <div class="dashboard-stat-note mt-3">
            Map centered on Utah's Uintah Basin.
            Verified NASA Pandora instrument locations will
            appear here when station information is available.
        </div>
    </div>

</div>

            <div class="alert alert-info mb-0">
                <strong>Data availability:</strong>
                This dashboard is under development. Measurements will
                appear after we verify and connect NASA Pandora datasets
                from the Uintah Basin.
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
            Awaiting verified data
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
            <select id="pandoraStationFilter" class="form-control" disabled>
                <option value="">No verified stations available</option>
            </select>
        </div>

        <div class="col-12 col-md-3 mb-3">
            <label for="pandoraStartDate">Start Date</label>
            <input
                type="date"
                id="pandoraStartDate"
                class="form-control"
            >
        </div>

        <div class="col-12 col-md-3 mb-3">
            <label for="pandoraEndDate">End Date</label>
            <input
                type="date"
                id="pandoraEndDate"
                class="form-control"
            >
        </div>

    </div>

    <!-- Chart -->
    <div class="p-3 pt-0">

        <div style="height: 350px; position: relative;">
            <canvas id="pandoraTimeSeriesChart"></canvas>
        </div>

        <div class="dashboard-stat-note mt-3" id="pandoraChartStatus">
            No Pandora observations have been loaded.
            The chart will display verified Uintah Basin measurements
            when a data source is connected.
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

    new Chart(canvas, {
        type: 'line',

        data: {
            labels: [],
            datasets: [{
                label: 'Pandora NO₂ Observations',
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
                    display: false
                },
                tooltip: {
                    enabled: true
                }
            },

            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Observation Time'
                    }
                },

                y: {
                    title: {
                        display: true,
                        text: 'Measurement (units pending verification)'
                    },
                    beginAtZero: true
                }
            }
        }
    });

})();
</script>
@endpush
