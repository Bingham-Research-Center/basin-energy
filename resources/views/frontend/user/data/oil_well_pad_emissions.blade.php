@extends('frontend.layouts.app')

@section('title', __('Oil Well Pad Emissions'))

@section('content')
<div
    id="oilWellPadDashboard"
    class="container-fluid"
    data-overview-url="{{ route('frontend.user.data.oil-well-pad-emissions.overview.json') }}"
    data-samples-url="{{ route('frontend.user.data.oil-well-pad-emissions.samples.json') }}"
    data-composition-url="{{ route('frontend.user.data.oil-well-pad-emissions.composition.json') }}"
    data-options-url="{{ route('frontend.user.data.oil-well-pad-emissions.options.json') }}">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Oil Well Pad Emissions</h1>
            <p class="text-muted mb-0">Speciated organic-compound emissions measured from oil-well components in Duchesne County, Utah.</p>
        </div>
    </div>

    <div id="oilWellPadError" class="alert alert-danger d-none" role="alert"></div>

    <div class="card mb-4">
        <div class="card-header"><strong>About this dataset</strong></div>
        <div class="card-body">
            <p>
                Researchers used optical gas imaging and a custom high-flow sampler to quantify methane,
                non-methane organic compounds, and carbon dioxide from components at 24 oil wells.
                Measurements include oil and water tanks, separator equipment, wellheads, and other valves,
                fittings, and process equipment.
            </p>
            <p class="mb-0">
                Liquid storage tanks generally produced the largest measured emissions. Oil-tank emissions
                contained more reactive heavy hydrocarbons and aromatics than regulatory flash-gas estimates,
                suggesting that conventional regulatory composition data can underestimate ozone-forming potential.
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 col-xl-3">
            <div class="card text-white bg-primary mb-4">
                <div class="card-body">
                    <div class="text-value-lg" id="oilMeasurementCount">—</div>
                    <div>Quantified sources</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card text-white bg-info mb-4">
                <div class="card-body">
                    <div class="text-value-lg" id="oilSourceTypeCount">—</div>
                    <div>Source categories</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card text-white bg-warning mb-4">
                <div class="card-body">
                    <div class="text-value-lg" id="oilMaxOrganic">—</div>
                    <div>Maximum total organics</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card text-white bg-danger mb-4">
                <div class="card-body">
                    <div class="text-value-lg" id="oilMaxMethane">—</div>
                    <div>Maximum methane</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Average emissions by source type</strong></div>
        <div class="card-body">
            <div style="height: 430px;">
                <canvas id="oilSourceSummaryChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Explore individual measurements</strong></div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="oilSampleType">Source type</label>
                    <select id="oilSampleType" class="form-control">
                        <option value="">All source types</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="oilMetric">Emission metric</label>
                    <select id="oilMetric" class="form-control"></select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button id="oilUpdateChart" type="button" class="btn btn-primary btn-block">Update charts</button>
                </div>
            </div>
            <div style="height: 420px;">
                <canvas id="oilSampleChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Average emissions composition</strong></div>
        <div class="card-body">
            <p class="text-muted">Average measured mass emission rate for major organic-compound groups.</p>
            <div style="height: 420px;">
                <canvas id="oilCompositionChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Study and data source</strong></div>
        <div class="card-body">
            <p><strong>Study:</strong> Speciated Organic Compound Emissions from Oil Wells in Utah.</p>
            <p>
                The high-flow system measured methane and carbon dioxide continuously and collected samples
                for C2-C10 hydrocarbons, alcohols, and carbonyls. The spreadsheet contains anonymized component-level
                emission rates in grams per hour; “N.D.” indicates that a compound was not detected.
            </p>
            <a href="https://doi.org/10.5281/zenodo.17154738" target="_blank" rel="noopener">Open the archived dataset</a>
        </div>
    </div>
</div>
@endsection
