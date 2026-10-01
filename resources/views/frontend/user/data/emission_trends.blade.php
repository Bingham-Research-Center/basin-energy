@extends('frontend.layouts.app')

@section('title', __('Emission Trends'))

@section('content')
<div class="dashboard-page dashboard-page-pro dashboard-fill-page" id="emissionTrendsChartRoot"
     data-json-url="{{ route('frontend.user.data.emission-trends.json') }}">

    <div class="card dashboard-chart-card dashboard-fill-card mb-4">
        <div class="card-header">
            <div>
                <h2 class="dashboard-chart-title mb-0">@lang('Emission Trends')</h2>
                <div class="dashboard-chart-subtitle">@lang('Interactive oil and gas emission trend visualization')</div>
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
            <div class="row mb-4">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="dashboard-control-panel h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="dashboard-control-title">@lang('Plot columns')</div>
                                <div class="dashboard-muted small">@lang('Choose one or more fields to compare')</div>
                            </div>
                            <span class="badge badge-primary">@lang('Dataset')</span>
                        </div>
                        <div id="colPicker" class="dashboard-column-picker"></div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="dashboard-control-panel h-100">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="dashboard-control-title mb-2" for="smooth">
                                    @lang('Smoothing') <span class="badge badge-secondary" id="smoothVal">0.35</span>
                                </label>
                                <input id="smooth" type="range" class="custom-range"
                                       min="0" max="1" step="0.05" value="0.35">
                                <div class="dashboard-muted small">@lang('0 = straight lines, 1 = very smooth')</div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <div class="dashboard-control-title mb-2">@lang('Scale options')</div>

                                <div class="custom-control custom-switch mb-2">
                                    <input class="custom-control-input" type="checkbox" id="toggleNormalize">
                                    <label class="custom-control-label" for="toggleNormalize">
                                        @lang('Normalize') <span class="dashboard-muted">(@lang('index to 100'))</span>
                                    </label>
                                </div>

                                <div class="custom-control custom-switch">
                                    <input class="custom-control-input" type="checkbox" id="toggleLog">
                                    <label class="custom-control-label" for="toggleLog">
                                        @lang('Log scale')
                                    </label>
                                </div>

                                <div class="dashboard-muted small mt-2">@lang('Normalize compares trends; log scale hides non-positive values.')</div>
                            </div>

                            <div class="col-12">
                                <label class="dashboard-control-title mb-2">@lang('Year range')</label>
                                <div class="row align-items-center">
                                    <div class="col-md-5 mb-2 mb-md-0">
                                        <input id="yearMin" type="range" class="custom-range">
                                    </div>
                                    <div class="col-md-5 mb-2 mb-md-0">
                                        <input id="yearMax" type="range" class="custom-range">
                                    </div>
                                    <div class="col-md-2 text-md-right">
                                        <span class="badge badge-secondary">
                                            <span id="yearMinLabel"></span> - <span id="yearMaxLabel"></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="dashboard-muted small">@lang('Drag either handle to focus the visualization window.')</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning py-2 px-3 d-none" id="logWarning">
                <i class="c-icon cil-warning mr-1"></i> @lang('Log scale hides zero/negative values.')
            </div>

            <div class="dashboard-chart-shell dashboard-chart-shell-fill">
                <canvas id="emissionsChart"></canvas>
            </div>

            <div class="dashboard-ai-block mt-3">
                <div class="dashboard-ai-block-title">
                    <i class="c-icon cil-lightbulb"></i>
                    @lang('AI data summary')
                </div>

                <p class="dashboard-ai-block-text" id="emissionAiSummary">
                    @lang('Select one or more emission trend columns to generate a short interpretation of the selected trends.')
                </p>
            </div>

            <div class="dashboard-chart-help mt-3">
                <i class="c-icon cil-info mr-1"></i>
                @lang('Tip: use the mouse wheel or pinch to zoom; drag to pan the chart.')
            </div>
        </div>

    </div>
</div>
@endsection