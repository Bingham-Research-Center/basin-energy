@extends('frontend.layouts.app')

@section('title', __('Data'))

@section('content')

<div class="container-fluid px-4">

    <div class="card mb-4" id="emissionTrendsChartRoot"
         data-json-url="{{ route('frontend.user.data.emission-trends.json') }}">

        <div class="card-header bg-white border-bottom">
            <h5 class="h5 mb-0">{{ __('Emission Trends') }}</h5>
        </div>

        <div class="card-body">

            <div class="row g-3 mb-3">
                <div class="col-12 col-lg-5">
                    <strong>{{ __('Plot columns') }}</strong>
                    <div id="colPicker" class="mt-2"></div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="d-flex flex-column gap-3">

                        <div>
                            <label class="form-label mb-1">
                                <strong>{{ __('Smoothing') }}</strong>
                                (<span id="smoothVal">0.35</span>)
                            </label>
                            <input id="smooth" type="range" class="form-range"
                                   min="0" max="1" step="0.05" value="0.35">
                            <small class="text-muted">
                                0 = straight lines, 1 = very smooth
                            </small>
                        </div>

                        <div style="margin-top: 10px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="toggleNormalize">
                                <label class="form-check-label" for="toggleNormalize">
                                    <strong>{{ __('Normalize') }}</strong> (index to 100)
                                </label>
                            </div>
                            <small class="text-muted d-block">
                                Compares trends, not magnitude
                            </small>
                        </div>

                        <div style="margin-top: 10px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="toggleLog">
                                <label class="form-check-label" for="toggleLog">
                                    <strong>{{ __('Log scale') }}</strong>
                                </label>
                            </div>
                            <small class="text-muted d-block">
                                Non-positive values will be hidden
                            </small>
                        </div>

                        <div style="margin-top: 10px;">
                            <label class="form-label mb-1">
                                <strong>{{ __('Year range') }}</strong>
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <input id="yearMin" type="range" class="form-range">
                                <input id="yearMax" type="range" class="form-range">
                            </div>
                            <small class="text-muted">
                                <span id="yearMinLabel"></span> –
                                <span id="yearMaxLabel"></span>
                            </small>
                        </div>

                        <div  style="margin-top: 20px;">
                        <strong>{{ __('Utilities') }}</strong><br>
                            <button id="btnResetZoom"
                                    class="btn btn-outline-secondary btn-sm">
                                Reset zoom
                            </button>&nbsp;&nbsp;
                            <button id="btnPng"
                                    class="btn btn-primary btn-sm">
                                Download PNG
                            </button>&nbsp;&nbsp;
                            <button id="btnCsv"
                                    class="btn btn-outline-primary btn-sm">
                                Download CSV
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <div class="alert alert-info py-2 px-3 d-none" id="logWarning">
                Log scale hides zero/negative values.
            </div>

            <div style="height: 420px;">
                <canvas id="emissionsChart"></canvas>
            </div>

            <small class="text-muted d-block mt-2">
                Tip: wheel / pinch to zoom; drag to pan.
            </small>

        </div>
    </div>
</div>

@endsection
