@extends('frontend.layouts.app')

@section('title', __('Produced Water'))

@section('content')
<div
    class="dashboard-page dashboard-page-pro dashboard-fill-page"
    id="producedWaterFluxRoot"
    data-json-url="{{ route('frontend.user.data.produced-water.flux.json') }}"
    data-columns-url="{{ route('frontend.user.data.produced-water.flux.columns') }}"
>
    <div class="card dashboard-chart-card dashboard-fill-card mb-4">
        <div class="card-header">
            <div>
                <h2 class="dashboard-chart-title mb-0">@lang('Produced Water Flux')</h2>
                <div class="dashboard-chart-subtitle">
                    @lang('Interactive produced water chemistry and flux visualization')
                </div>
            </div>

            <div class="btn-group">
                <button id="btnResetZoom" type="button" class="btn btn-sm btn-outline-primary">
                    <i class="c-icon cil-reload mr-1"></i> @lang('Reset zoom')
                </button>

                <button id="btnPng" type="button" class="btn btn-sm btn-outline-primary">
                    <i class="c-icon cil-image mr-1"></i> @lang('PNG')
                </button>

                <button id="btnCsv" type="button" class="btn btn-sm btn-outline-primary">
                    <i class="c-icon cil-cloud-download mr-1"></i> @lang('CSV')
                </button>
            </div>
        </div>

        <div class="card-body dashboard-fill-card-body">
            <div class="row">
                <div class="col-12 col-lg-4 mb-4 mb-lg-0">
                    <div class="dashboard-control-panel h-100">
                        <div class="dashboard-control-title">
                            @lang('Plot column')
                        </div>

                        <div class="dashboard-control-subtitle">
                            @lang('Choose a field to visualize')
                        </div>

                        <div id="colPicker" class="mt-3"></div>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="dashboard-control-panel h-100">
                        <div class="row">
                            <div class="col-12 col-xl-6 mb-4 mb-xl-0">
                                <label for="smooth" class="dashboard-control-title mb-2">
                                    @lang('Smoothing')
                                    <span class="badge badge-secondary ml-1" id="smoothVal">0.35</span>
                                </label>

                                <input
                                    type="range"
                                    class="custom-range"
                                    id="smooth"
                                    min="0"
                                    max="1"
                                    step="0.01"
                                    value="0.35"
                                >

                                <div class="dashboard-control-help mt-1">
                                    @lang('0 = straight lines, 1 = very smooth')
                                </div>
                            </div>

                            <div class="col-12 col-xl-6">
                                <div class="dashboard-control-title mb-2">
                                    @lang('Scale options')
                                </div>

                                <div class="custom-control custom-switch mb-2">
                                    <input
                                        class="custom-control-input"
                                        type="checkbox"
                                        id="toggleLog"
                                    >
                                    <label class="custom-control-label" for="toggleLog">
                                        @lang('Log scale')
                                    </label>
                                </div>

                                <div id="logWarning" class="dashboard-control-help d-none mb-3">
                                    @lang('Non-positive values will be hidden.')
                                </div>

                                <div class="custom-control custom-switch">
                                    <input
                                        class="custom-control-input"
                                        type="checkbox"
                                        id="toggleWindCorrected"
                                    >
                                    <label class="custom-control-label" for="toggleWindCorrected">
                                        @lang('Use wind-corrected fluxes')
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Chart --}}
            <div class="dashboard-chart-shell dashboard-chart-shell-fill mt-4">
                <canvas id="emissionsChart"></canvas>
            </div>

            <div class="dashboard-chart-help mt-3">
                <i class="c-icon cil-info mr-1"></i>
                @lang('Tip: use the mouse wheel or pinch to zoom; drag to pan the chart.')
            </div>
        </div>
    </div>
</div>
@endsection