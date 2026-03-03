@extends('frontend.layouts.app')

@push('after-styles')
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
/>
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"
/>

@endpush


@section('title', 'Methane Emissions')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="mb-0">Methane Emissions in Utah</h3>
    </div>

    <div class="card mb-3">
    <div class="card-header">
        <strong>How to read this map</strong>
    </div>

    <div class="card-body small">
        <p>
            This map shows <strong>satellite-detected methane (CH₄) emissions</strong>
            from the Carbon Mapper data archive. Each symbol reflects how often
            methane was detected at or near a location over time.
        </p>

        <hr>

        <h6 class="fw-bold">🟣 Clustered circles with numbers</h6>
        <p>
            Large circles with numbers (for example <strong>99</strong>) represent
            <strong>clusters of methane detections</strong>.
        </p>
        <ul>
            <li>The number shows how many individual detections occurred nearby</li>
            <li>Higher numbers indicate <strong>persistent emission locations</strong></li>
            <li>These are referred to as <em>“sources”</em> in Carbon Mapper terminology</li>
        </ul>

        <hr>

        <h6 class="fw-bold">🕸️ Lines radiating from a cluster</h6>
        <p>
            When many detections occur at nearly the same coordinates, the map
            briefly spreads them out so they can be clicked individually.
        </p>
        <ul>
            <li>This behavior is called <strong>“spiderfying”</strong></li>
            <li>It indicates repeated satellite observations over time</li>
            <li>This is a positive sign i.e, the data is <strong>dense and reliable</strong></li>
        </ul>

        <hr>

        <h6 class="fw-bold">🟡 Nearby yellow and orange circles</h6>
        <p>
            Smaller colored circles represent <strong>nearby emission locations</strong>
            with fewer detections.
        </p>
        <p>
            Together, these patterns often show:
        </p>
        <ul>
            <li>One strong, persistent emitter</li>
            <li>Several moderate emitters nearby</li>
            <li>All within the same basin or region (such as the Uintah Basin)</li>
        </ul>

        <hr>

        <h6 class="fw-bold">What this confirms</h6>
        <ul>
            <li>You are viewing <strong>detection-level satellite data</strong></li>
            <li>Emissions are shown based on <strong>repeat observations</strong></li>
            <li>This is not “one point per state” data</li>
            <li>The Utah map reflects Carbon Mapper’s backend data density</li>
        </ul>
    </div>
</div>


    <div class="card-body p-0">
        <div id="map" style="height: 600px;"></div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script src="{{ asset('js/carbon-mapper-map.js') }}"></script>
@endpush
