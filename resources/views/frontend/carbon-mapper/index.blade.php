@extends('frontend.layouts.app')

@section('title', __('Methane Emissions'))

@push('after-styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">

    <style>
        /* Carbon Mapper page styling */

        /* Make the map wider and shorter */
        .carbon-mapper-page .dashboard-map-shell {
            width: 100%;
            height: 90vh;
            min-height: 400px;
            max-height: 1300px;
        }

        .carbon-mapper-page .dashboard-leaflet-map {
            width: 100%;
            height: 100%;
            min-height: 500px;
        }

        /* Keep the map within the screen on mobile */
        @media (max-width: 767.98px) {
            .carbon-mapper-page .dashboard-map-shell {
                width: 100%;
                height: 60vh;
                min-height: 460px;
            }

            .carbon-mapper-page .dashboard-leaflet-map {
                min-height: 460px;
            }
        }
        .carbon-mapper-page .carbon-map-filters {
            border: 1px solid #e5e9ef;
            border-radius: 10px;
            padding: 20px;
        }

        .carbon-mapper-page .carbon-map-section-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .carbon-mapper-page .carbon-map-section-description {
            font-size: 0.875rem;
            color: #768192;
        }

        .carbon-mapper-page .carbon-map-filters label {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 7px;
        }
        .carbon-mapper-page .carbon-map-filters .form-control {
            border-radius: 6px;
            min-height: 40px;
        }

        .carbon-mapper-page .carbon-map-stat {
            border: 1px solid #e5e9ef;
            border-radius: 10px;
            padding: 18px;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .carbon-mapper-page .carbon-map-stat:hover {
            box-shadow: 0 5px 16px rgba(30, 42, 60, 0.07);
            transform: translateY(-2px);
        }

        .carbon-mapper-page .carbon-map-stat-label {
            font-size: 0.8rem;
            color: #768192;
            margin-bottom: 8px;
        }

        .carbon-mapper-page .carbon-map-stat-value {
            font-size: 1.65rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .carbon-mapper-page .carbon-map-stat-total {
            border-top: 3px solid #321fdb;
        }

        .carbon-mapper-page .carbon-map-stat-candidates {
            border-top: 3px solid #e67e22;
        }

        .carbon-mapper-page .carbon-map-stat-types {
            border-top: 3px solid #2eb85c;
        }

        .carbon-mapper-page .carbon-map-stat-dates {
            border-top: 3px solid #39a9db;
        }

        .carbon-mapper-page .carbon-map-stat-dates .carbon-map-stat-value {
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .carbon-mapper-page .carbon-map-legend {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px 24px;
            padding: 14px 16px;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
        }

        .carbon-mapper-page .carbon-map-legend-item {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
        }

        .carbon-mapper-page .carbon-map-legend-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            flex-shrink: 0;
            border-radius: 50%;
            margin-right: 9px;
        }

        .carbon-mapper-page .carbon-map-legend-dot-observation {
            background: #6f42c1;
        }

        .carbon-mapper-page .carbon-map-legend-dot-candidate {
            width: 14px;
            height: 14px;
            background: #e67e22;
            border: 2px solid #e67e22;
        }

        @media (max-width: 575.98px) {
            .carbon-mapper-page .carbon-map-filters {
                padding: 15px;
            }

            .carbon-mapper-page .carbon-map-stat {
                padding: 13px;
            }

            .carbon-mapper-page .carbon-map-stat-value {
                font-size: 1.35rem;
            }
        }
   </style>
@endpush

@section('content')
<div class="dashboard-page dashboard-page-pro dashboard-fill-page carbon-mapper-page">
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
                     @lang('Explore Carbon Mapper gas emission observations and learn what each map symbol represents.')
 </div>

                        <div class="carbon-map-help">
                            <div class="carbon-map-help-item">
                                <div class="carbon-map-help-icon carbon-map-help-icon-cluster">
                                    <i class="c-icon cil-layers"></i>
                                </div>

                                <div>
                                    <h6>@lang('Clustered circles')</h6>
                                    <p>
                                   @lang('Large numbered circles represent groups of nearby gas emission detections. The number indicates how many observations are grouped together.')
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
                              {{-- Carbon Mapper map legend --}}
<div class="mt-3 mb-3">
    <h6 class="mb-3">Map Legend</h6>

    <div class="carbon-map-help-item">
        <div style="width:24px;min-width:24px;display:flex;justify-content:center;">
            <span style="width:12px;height:12px;border-radius:50%;background:#6f42c1;"></span>
        </div>
        <div>
            <h6>Gas emission observation</h6>
            <p>Individual gas emission detection recorded by Carbon Mapper.</p>
        </div>
    </div>

    <div class="carbon-map-help-item">
        <div style="width:24px;min-width:24px;display:flex;justify-content:center;">
            <span style="width:14px;height:14px;border-radius:50%;background:#e67e22;"></span>
        </div>
        <div>
            <h6>Potential super-emitter candidate</h6>
            <p>Observation flagged as a potential super-emitter.</p>
        </div>
    </div>

    <div class="carbon-map-help-item">
        <div style="width:24px;min-width:24px;display:flex;justify-content:center;">
            <span style="width:17px;height:13px;background:rgba(111,66,193,0.35);border:2px solid #6f42c1;border-radius:40% 60% 50% 35%;"></span>
        </div>
        <div>
            <h6>Methane plume outline</h6>
            <p>Geographic footprint of a methane plume provided by Carbon Mapper.</p>
        </div>
    </div>
</div>

                            <div class="carbon-map-help-note mt-3">
                                <strong>@lang('This confirms:')</strong>
                                <ul class="mb-0 mt-2 pl-3">
                                    <li>@lang('Detection-level satellite data')</li>
                                    <li>@lang('Repeat observations over time')</li>
                                    <li>@lang('Dense Utah gas emission source coverage')</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-12 col-xl-8">
                {{-- Carbon Mapper filters --}}
<div class="dashboard-control-panel carbon-map-filters mb-3">
    <div class="carbon-map-section-title">Filter Carbon Mapper observations</div>
<div class="carbon-map-section-description">
    Narrow the map by gas type, observation date, and detection type.
</div>

    <div class="row mt-3">
<div class="col-12 col-md-3 mb-3">
    <label for="carbonMapperGas">Gas type</label>
    <select id="carbonMapperGas" class="form-control">
        <option value="">All gases</option>
        <option value="CH4">Methane (CH4)</option>
        <option value="CO2">Carbon dioxide (CO2)</option>
    </select>
</div>
        <div class="col-12 col-md-4 mb-3">
            <label for="carbonMapperFrom">Start date</label>
            <input
                type="date"
                id="carbonMapperFrom"
                class="form-control"
            >
        </div>

        <div class="col-12 col-md-4 mb-3">
            <label for="carbonMapperTo">End date</label>
            <input
                type="date"
                id="carbonMapperTo"
                class="form-control"
            >
        </div>

        <div class="col-12 col-md-4 mb-3">
            <label for="carbonMapperDetectionType">Detection type</label>
            <select id="carbonMapperDetectionType" class="form-control">
                <option value="">All detection types</option>
            </select>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center">
        <button
            type="button"
            id="carbonMapperApplyFilters"
            class="btn btn-primary mr-2 mb-2"
        >
            Apply filters
        </button>

        <button
            type="button"
            id="carbonMapperResetFilters"
            class="btn btn-outline-secondary mb-2"
        >
            Reset filters
        </button>

        <button
    type="button"
    id="carbonMapperDownloadCsv"
    class="btn btn-outline-primary ml-2 mb-2"
    disabled
>
    Download CSV
</button>

      <span
    id="carbonMapperFilterStatus"
    class="small ml-md-3 mb-2"
    role="status"
    aria-live="polite"
    aria-atomic="true"
></span>
    </div>

    {{-- Carbon Mapper source data refresh information --}}
    <div class="small mt-2" style="color: #a8b3c7;">
        <strong>Source measurements last updated:</strong>
        @if($sourceMeasurementsLastUpdated)
            {{ $sourceMeasurementsLastUpdated->format('F j, Y') }} UTC
        @else
            Not available
        @endif
    </div>
</div>
{{-- Carbon Mapper summary statistics --}}
<div class="row mb-3" id="carbonMapperStatistics">
    <div class="col-6 col-lg-3 mb-3">
        <div class="dashboard-control-panel h-100">
            <div class="dashboard-control-subtitle">
                Total observations
            </div>
             <div
    class="dashboard-chart-title"
    id="carbonMapperTotal"
    role="status"
    aria-label="Total Carbon Mapper observations"
>
                —
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3 mb-3">
        <div class="dashboard-control-panel h-100">
            <div class="dashboard-control-subtitle">
                Potential candidates
            </div>
            <div class="dashboard-chart-title" id="carbonMapperCandidates">
                —
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3 mb-3">
        <div class="dashboard-control-panel h-100">
            <div class="dashboard-control-subtitle">
                Detection types
            </div>
            <div class="dashboard-chart-title" id="carbonMapperTypes">
                —
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3 mb-3">
        <div class="dashboard-control-panel h-100">
            <div class="dashboard-control-subtitle">
                Observation date range
            </div>
            <div class="small mt-2" id="carbonMapperDateRange">
                —
            </div>
        </div>
    </div>
</div>
<div class="dashboard-map-shell">
    <div
    id="map"
    class="dashboard-leaflet-map"
    role="region"
    aria-label="Interactive Carbon Mapper gas emissions map of Utah"
></div>

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
@endpush
