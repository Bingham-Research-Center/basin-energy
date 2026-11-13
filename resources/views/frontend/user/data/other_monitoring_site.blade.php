@extends('frontend.layouts.app')

@section('title', $site['name'] ?? 'Other Monitoring Site')

@section('content')
<div class="dashboard-page dashboard-page-pro dashboard-fill-page">

    <div class="card dashboard-chart-card dashboard-fill-card mb-4">
        <div class="card-header">
            <div>
                <h1 class="dashboard-chart-title mb-0">
                    {{ $site['name'] }}
                </h1>
                <div class="dashboard-chart-subtitle">
                    Environmental Monitoring Station
                </div>
            </div>
            <span class="badge badge-warning">
                Data Connection Pending
            </span>
        </div>

        <div class="card-body dashboard-fill-card-body">

            <div class="alert alert-info mb-4">
                This monitoring station dashboard is ready for integration.
                Live measurements will appear once the data source is connected.
            </div>

            <h4 class="mb-3">Current Measurements</h4>

            <div class="row">
                @foreach ([
                    ['Ozone', 'ppb'],
                    ['Air Temperature', '°C'],
                    ['Relative Humidity', '%'],
                    ['Wind Speed', 'm/s'],
                    ['Wind Direction', '°'],
                    ['Barometric Pressure', 'hPa']
                ] as $metric)
                    <div class="col-xl-4 col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="text-muted mb-2">
                                    {{ $metric[0] }}
                                </div>
                                <h2 class="mb-1">N/A</h2>
                                <small class="text-muted">
                                    {{ $metric[1] }} · Awaiting data
                                </small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row mt-3">

                <div class="col-lg-8 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong>Historical Ozone Measurements</strong>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center"
                             style="min-height: 260px;">
                            <div class="text-center text-muted">
                                <h5>Awaiting Data Connection</h5>
                                <p class="mb-0">
                                    Historical observations will appear here.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong>Ozone Statistics</strong>
                        </div>
                        <div class="card-body">
                            <p>Maximum: <strong>N/A</strong></p>
                            <p>Minimum: <strong>N/A</strong></p>
                            <p>Average: <strong>N/A</strong></p>
                            <p class="mb-0">Recent Trend: <strong>N/A</strong></p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row">

                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong>Atmospheric Conditions</strong>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-0">
                                Atmospheric measurements will appear
                                when the station data becomes available.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong>Station Location</strong>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center"
                             style="min-height: 180px;">
                            <span class="text-muted">
                                Station coordinates not yet configured.
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="card">
                <div class="card-header">
                    <strong>Station Information</strong>
                </div>
                <div class="card-body">
                    <p><strong>Station:</strong> {{ $site['name'] }}</p>
                    <p><strong>Data Source:</strong> {{ $site['source'] ?? 'Pending confirmation' }}</p>
                    <p><strong>Elevation:</strong>
                        {{ isset($site['elevation_ft']) ? number_format($site['elevation_ft']) . ' ft' : 'N/A' }}
                    </p>
                    <p><strong>Latitude:</strong> {{ $site['latitude'] ?? 'N/A' }}</p>
                    <p><strong>Longitude:</strong> {{ $site['longitude'] ?? 'N/A' }}</p>
                    <p class="mb-0">
                        <strong>Status:</strong> Awaiting data integration
                    </p>
                </div>
            </div>

            <div class="card mt-4 mb-0">
                    <div class="card-header">
                        <div>
                            <strong>Station Location</strong>
                            <div class="dashboard-chart-subtitle">
                                {{ $site['name'] }} — Geographic Location
                            </div>
                        </div>
                    </div>
            
                    <div class="card-body">
                        <div id="otherSiteMap"
                             data-station-name="{{ $site['name'] }}"
                             data-latitude="{{ $site['latitude'] ?? '' }}"
                             data-longitude="{{ $site['longitude'] ?? '' }}"
                             style="height: 280px; width: 100%; border-radius: 6px;">
                        </div>
            
                        <p id="otherSiteMapStatus" class="text-muted mt-3 mb-0">
                            Exact station coordinates are pending confirmation.
                            The map currently shows the general Uintah Basin region.
                        </p>
                    </div>
                </div>

        </div>
    </div>

</div>


@endsection

@push('after-styles')
<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@push('after-scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const element = document.getElementById('otherSiteMap');

    if (!element || typeof L === 'undefined') {
        return;
    }

    const latitude = Number.parseFloat(element.dataset.latitude);
    const longitude = Number.parseFloat(element.dataset.longitude);

    const hasCoordinates =
        Number.isFinite(latitude) &&
        Number.isFinite(longitude) &&
        latitude >= -90 &&
        latitude <= 90 &&
        longitude >= -180 &&
        longitude <= 180;

    const defaultCenter = [40.1, -109.5];

    const map = L.map(element).setView(
        hasCoordinates ? [latitude, longitude] : defaultCenter,
        hasCoordinates ? 11 : 8
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    if (hasCoordinates) {
        const marker = L.marker([latitude, longitude]).addTo(map);

        const popup = document.createElement('strong');
        popup.textContent = element.dataset.stationName;

        marker.bindPopup(popup);

        document.getElementById('otherSiteMapStatus').textContent =
            'Marker indicates the configured monitoring station location.';
    }

    setTimeout(function () {
        map.invalidateSize();
    }, 200);
});
</script>
@endpush

