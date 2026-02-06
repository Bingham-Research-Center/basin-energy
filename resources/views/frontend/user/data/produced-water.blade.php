@extends('frontend.layouts.app')

@section('title', __('Data'))

@section('content')
<div class="container-fluid px-4"
     id="producedWaterFluxRoot"
     data-json-url="{{ route('frontend.user.data.produced-water.flux.json') }}"
     data-columns-url="{{ route('frontend.user.data.produced-water.flux.columns') }}">

    <div class="card mb-4">

    <div class="card-header bg-white border-bottom">
        <h5 class="h5 mb-0">{{ __('Produced Water Flux') }}</h5>
    </div>

    <div class="card-body">

        <div class="row g-3 mb-3">

            {{-- LEFT: Y-axis picker --}}
            <div class="col-12 col-lg-5">
                <strong>{{ __('Plot column') }}</strong>
                <div id="colPicker" class="mt-2"></div>
            </div>

            {{-- RIGHT: controls --}}
            <div class="col-12 col-lg-7">
                <div class="d-flex flex-column gap-3">

                    <div>
                        <label class="form-label mb-1">
                            <strong>{{ __('Smoothing') }}</strong>
                            (<span id="smoothVal">0.35</span>)
                        </label>
                        <input
                            type="range"
                            class="form-range"
                            id="smooth"
                            min="0"
                            max="1"
                            step="0.01"
                            value="0.35">
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="toggleLog">
                        <label class="form-check-label">
                            {{ __('Log scale') }}
                        </label>
                        <div id="logWarning" class="text-muted small d-none">
                            {{ __('Non-positive values will be hidden') }}
                        </div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="toggleWindCorrected">
                        <label class="form-check-label">
                            {{ __('Use wind-corrected fluxes') }}
                        </label>
                    </div>

                    <div class="d-flex gap-2 mt-2">
                        <button id="btnResetZoom" class="btn btn-sm btn-outline-secondary">
                            {{ __('Reset zoom') }}
                        </button>
                        <button id="btnPng" class="btn btn-sm btn-primary">
                            {{ __('Download PNG') }}
                        </button>
                        <button id="btnCsv" class="btn btn-sm btn-outline-primary">
                            {{ __('Download CSV') }}
                        </button>
                    </div>

                </div>
            </div>
        </div>

        {{-- CHART --}}
        <div style="height: 420px;">
            <canvas id="emissionsChart"></canvas>
        </div>

    </div>
</div>





</div>
@endsection
