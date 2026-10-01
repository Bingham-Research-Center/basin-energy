@extends('frontend.layouts.app')

@section('title', __('Methane Emissions'))

@push('after-styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
@endpush

@section('content')
<div class="dashboard-page dashboard-page-pro dashboard-fill-page">
    <div class="card dashboard-chart-card dashboard-fill-card mb-4">
        <div class="card-header">
            <div>
                <h2 class="dashboard-chart-title mb-0">
                    @lang('Methane Emissions in Utah')
                </h2>

                <div class="dashboard-chart-subtitle">
                    @lang('Satellite-detected methane observations from the Carbon Mapper data archive')
                </div>
            </div>

            <div class="dashboard-status-pill dashboard-status-pill-success">
                <i class="c-icon cil-map mr-1"></i>
                @lang('Carbon Mapper')
            </div>
        </div>

        <div class="card-body dashboard-fill-card-body">
            <div class="row">
                <div class="col-12 col-xl-4 mb-4 mb-xl-0">
                    <div class="dashboard-control-panel h-100">
                        <div class="dashboard-control-title">
                            @lang('How to read this map')
                        </div>

                        <div class="dashboard-control-subtitle mb-3">
                            @lang('Each symbol reflects satellite-detected methane activity near a location over time.')
                        </div>

                        <div class="carbon-map-help">
                            <div class="carbon-map-help-item">
                                <div class="carbon-map-help-icon carbon-map-help-icon-cluster">
                                    <i class="c-icon cil-layers"></i>
                                </div>

                                <div>
                                    <h6>@lang('Clustered circles')</h6>
                                    <p>
                                        @lang('Large numbered circles represent groups of nearby methane detections. Higher numbers indicate repeated observations and persistent source locations.')
                                    </p>
                                </div>
                            </div>

                            <div class="carbon-map-help-item">
                                <div class="carbon-map-help-icon carbon-map-help-icon-lines">
                                    <i class="c-icon cil-share-alt"></i>
                                </div>

                                <div>
                                    <h6>@lang('Spiderfied points')</h6>
                                    <p>
                                        @lang('When many detections overlap, the map spreads them out so individual observations can be selected. This usually indicates dense repeated satellite coverage.')
                                    </p>
                                </div>
                            </div>

                            <div class="carbon-map-help-item">
                                <div class="carbon-map-help-icon carbon-map-help-icon-nearby">
                                    <i class="c-icon cil-location-pin"></i>
                                </div>

                                <div>
                                    <h6>@lang('Nearby emitters')</h6>
                                    <p>
                                        @lang('Smaller yellow or orange circles often represent lower-count nearby emission locations within the same basin or region.')
                                    </p>
                                </div>
                            </div>

                            <div class="carbon-map-help-note mt-3">
                                <strong>@lang('This confirms:')</strong>
                                <ul class="mb-0 mt-2 pl-3">
                                    <li>@lang('Detection-level satellite data')</li>
                                    <li>@lang('Repeat observations over time')</li>
                                    <li>@lang('Dense Utah methane source coverage')</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="dashboard-map-shell">
                        <div id="map" class="dashboard-leaflet-map"></div>
                    </div>

                    <div class="dashboard-chart-help mt-3">
                        <i class="c-icon cil-info mr-1"></i>
                        @lang('Tip: zoom into clustered circles to inspect individual methane detections and persistent source locations.')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="{{ asset('js/carbon-mapper-map.js') }}"></script>
@endpush