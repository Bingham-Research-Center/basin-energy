@extends('frontend.layouts.app')

@section('title', __('Subsurface Natural Gas Leaks'))

@push('after-styles')
<style>
    .metric-card { min-height: 120px; }
    .chart-wrap { position: relative; min-height: 380px; }
    .source-note { font-size: .92rem; line-height: 1.6; }
</style>
@endpush

@section('content')
<div id="subsurfaceLeakDashboard" class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h3 mb-1">Subsurface Natural Gas Leaks</h1>
            <p class="text-muted mb-0">Methane, hydrocarbon, and carbon-dioxide fluxes from well-pad and nearby soils.</p>
        </div>
    </div>

    <div
        id="subsurfaceDashboardError"
        class="alert alert-danger d-none"
        role="alert">
    </div>

    <div class="alert alert-info source-note">
        Measurements show that some well pads had important localized soil emissions, but the estimated regional contribution was much less than 1% of total oil-and-gas methane and non-methane hydrocarbon emissions in the Uinta Basin.
    </div>

    <div class="row" id="overviewCards">
        @foreach(['Survey records', '15-minute records', 'Chamber records', 'Maximum survey CH₄'] as $label)
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="card metric-card"><div class="card-body">
                    <div class="text-muted text-uppercase small">{{ $label }}</div>
                    <div class="h4 mt-3 mb-0 overview-value">—</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Well-pad soil survey</strong></div>
        <div class="card-body">
            <div class="form-row mb-3">
                <div class="col-md-4"><label>Metric</label><select id="surveyMetric" class="form-control"></select></div>
                <div class="col-md-4"><label>Well type</label><select id="surveyWellType" class="form-control"><option value="">All</option></select></div>
                <div class="col-md-4"><label>Well status</label><select id="surveyWellStatus" class="form-control"><option value="">All</option></select></div>
            </div>
            <div class="chart-wrap"><canvas id="surveyChart"></canvas></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Temporal chamber measurements</strong></div>
        <div class="card-body">
            <div class="form-row mb-3">
                <div class="col-md-3"><label>Site</label><select id="temporalSite" class="form-control"></select></div>
                <div class="col-md-2"><label>Gas</label><select id="temporalGas" class="form-control"><option value="ch4">CH₄</option><option value="co2">CO₂</option></select></div>
                <div class="col-md-2"><label>Chamber</label><select id="temporalChamber" class="form-control">@for($i=1;$i<=6;$i++)<option value="{{ $i }}">{{ $i }}</option>@endfor</select></div>
                <div class="col-md-2"><label>Aggregation</label><select id="temporalAggregation" class="form-control"><option value="hourly">Hourly</option><option value="daily">Daily</option><option value="raw">Raw</option></select></div>
                <div class="col-md-3 d-flex align-items-end"><button id="reloadTemporal" class="btn btn-primary btn-block">Update chart</button></div>
            </div>
            <div class="chart-wrap"><canvas id="temporalChart"></canvas></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Publications and datasets</strong></div>
        <div class="card-body source-note">
            <p><strong>Publication:</strong> Strong Temporal Variability in Methane Fluxes from Natural Gas Well Pad Soils.</p>
            <p><a href="https://doi.org/10.1016/j.apr.2020.05.011" target="_blank" rel="noopener">View publication</a></p>
            <p><a href="https://digitalcommons.usu.edu/all_datasets/25/" target="_blank" rel="noopener">Well-pad soil flux dataset</a> &nbsp;|&nbsp;
               <a href="https://digitalcommons.usu.edu/all_datasets/111/" target="_blank" rel="noopener">Temporal methane flux dataset</a></p>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <script>
        window.subsurfaceLeakUrls = {
            overview: @json(route(
                'frontend.user.data.subsurface-natural-gas-leaks.overview.json'
            )),
            survey: @json(route(
                'frontend.user.data.subsurface-natural-gas-leaks.survey.json'
            )),
            temporal: @json(route(
                'frontend.user.data.subsurface-natural-gas-leaks.temporal.json'
            )),
            options: @json(route(
                'frontend.user.data.subsurface-natural-gas-leaks.options.json'
            ))
        };
    </script>
@endpush