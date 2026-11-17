@extends('frontend.layouts.app')

@section('title', __('Subsurface Natural Gas Leaks'))

@push('after-styles')
<style>
    #subsurfaceLeakDashboard {
        --leak-accent: #39a9db;
        --leak-border: rgba(150, 170, 190, .22);
    }

    #subsurfaceLeakDashboard .leak-hero {
        padding: 1.5rem;
        border: 1px solid var(--leak-border);
        border-radius: .6rem;
        background: rgba(45, 110, 150, .08);
    }

    #subsurfaceLeakDashboard .metric-card {
        min-height: 155px;
        border: 1px solid var(--leak-border);
    }

    #subsurfaceLeakDashboard .metric-label {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    #subsurfaceLeakDashboard .overview-value {
        font-size: 1.65rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    #subsurfaceLeakDashboard .metric-description {
        font-size: .85rem;
        line-height: 1.5;
    }

    #subsurfaceLeakDashboard .chart-wrap {
        position: relative;
        height: 390px;
        min-height: 300px;
    }

    #subsurfaceLeakDashboard .source-note {
        font-size: .93rem;
        line-height: 1.7;
    }

    #subsurfaceLeakDashboard .chart-description {
        font-size: .9rem;
        line-height: 1.6;
    }

    #subsurfaceLeakDashboard .section-heading {
        font-size: 1.15rem;
        font-weight: 700;
    }

    #subsurfaceLeakDashboard .leak-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    #subsurfaceLeakDashboard .leak-actions .btn {
        min-width: 115px;
    }

    #subsurfaceLeakDashboard .form-group label {
        font-weight: 600;
        font-size: .88rem;
    }

    #subsurfaceLeakDashboard .leak-info-card {
        height: 100%;
        border: 1px solid var(--leak-border);
    }

    #subsurfaceLeakDashboard .leak-info-card p:last-child {
        margin-bottom: 0;
    }

    #subsurfaceLeakDashboard .chart-status {
        min-height: 1.5rem;
        font-size: .88rem;
    }

    @media (max-width: 767.98px) {
        #subsurfaceLeakDashboard .chart-wrap {
            height: 320px;
        }

        #subsurfaceLeakDashboard .leak-hero {
            padding: 1rem;
        }

        #subsurfaceLeakDashboard .overview-value {
            font-size: 1.4rem;
        }

        #subsurfaceLeakDashboard .leak-actions .btn {
            flex: 1 1 auto;
        }
    }
</style>
@endpush

@section('content')
<div id="subsurfaceLeakDashboard" class="container-fluid">

    {{-- Dashboard header --}}
    <div class="leak-hero mb-4">
        <h1 class="h3 mb-2">Subsurface Natural Gas Leaks</h1>

        <p class="text-muted mb-3">
            Explore methane, hydrocarbon, and carbon-dioxide flux
            measurements from natural gas well-pad soils in the
            Uinta Basin, Utah.
        </p>

        <div class="source-note">
            <strong>Research finding:</strong>
            Measurements identified important localized soil emissions
            at some well pads. However, the estimated regional
            contribution was much less than 1% of total oil-and-gas
            methane and non-methane hydrocarbon emissions in the
            Uinta Basin.
        </div>
    </div>

    {{-- Dashboard errors --}}
    <div
        id="subsurfaceDashboardError"
        class="alert alert-danger d-none"
        role="alert">
    </div>

    {{-- Overview metrics --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-heading mb-0">Measurement Overview</h2>
    </div>

    @php
        $overviewCards = [
            [
                'label' => 'Survey Records',
                'description' => 'Well-pad soil survey measurements.',
            ],
            [
                'label' => '15-Minute Records',
                'description' => 'Interval-based flux measurements.',
            ],
            [
                'label' => 'Chamber Records',
                'description' => 'Temporal chamber measurement records.',
            ],
            [
                'label' => 'Maximum Survey CH₄',
                'description' => 'Highest recorded survey methane flux.',
            ],
        ];
    @endphp

    <div class="row mb-3" id="overviewCards">
        @foreach($overviewCards as $card)
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="card metric-card h-100">
                    <div class="card-body">
                        <div class="metric-label text-muted">
                            {{ $card['label'] }}
                        </div>

                        <div class="overview-value mt-3 mb-2"
                             aria-live="polite">
                            —
                        </div>

                        <div class="metric-description text-muted">
                            {{ $card['description'] }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Well-pad survey --}}
    <section class="card mb-4" aria-labelledby="surveyHeading">
        <div class="card-header">
            <h2 id="surveyHeading" class="section-heading mb-0">
                Well-Pad Soil Survey
            </h2>
        </div>

        <div class="card-body">
            <p class="chart-description text-muted mb-3">
                Explore how measured soil gas flux varies with
                distance from the wellhead. Each point represents
                a survey measurement.
            </p>

            <div class="form-row">
                <div class="col-md-4 form-group">
                    <label for="surveyMetric">Gas or compound</label>
                    <select
                        id="surveyMetric"
                        class="form-control">
                    </select>
                </div>

                <div class="col-md-4 form-group">
                    <label for="surveyWellType">Well type</label>
                    <select
                        id="surveyWellType"
                        class="form-control">
                        <option value="">All well types</option>
                    </select>
                </div>

                <div class="col-md-4 form-group">
                    <label for="surveyWellStatus">Well status</label>
                    <select
                        id="surveyWellStatus"
                        class="form-control">
                        <option value="">All statuses</option>
                    </select>
                </div>
            </div>

            <div class="form-row align-items-end mb-3">
                <div class="col-md-4 form-group mb-md-0">
                    <label for="surveyScale">Vertical scale</label>
                    <select id="surveyScale" class="form-control">
                        <option value="linear">Linear</option>
                        <option value="logarithmic">
                            Logarithmic (positive values)
                        </option>
                    </select>
                </div>

                <div class="col-md-8">
                    <div class="leak-actions justify-content-md-end">
                        <button
                            id="resetSurvey"
                            type="button"
                            class="btn btn-outline-secondary">
                            Reset filters
                        </button>

                        <button
                            id="downloadSurveyCsv"
                            type="button"
                            class="btn btn-outline-primary">
                            Download CSV
                        </button>
                    </div>
                </div>
            </div>

            <div
                id="surveyChartStatus"
                class="chart-status text-muted mb-2"
                role="status"
                aria-live="polite">
            </div>

            <div class="chart-wrap">
                <canvas
                    id="surveyChart"
                    role="img"
                    aria-label="Scatterplot of soil gas flux versus distance from the wellhead">
                </canvas>
            </div>

            <p class="chart-description text-muted mt-3 mb-0">
                <strong>How to read this chart:</strong>
                The horizontal axis shows distance from the wellhead.
                The vertical axis shows measured gas flux.
                Higher positive flux values indicate greater
                emissions at the measurement location.
            </p>
        </div>
    </section>

    {{-- Temporal measurements --}}
    <section class="card mb-4" aria-labelledby="temporalHeading">
        <div class="card-header">
            <h2 id="temporalHeading" class="section-heading mb-0">
                Temporal Chamber Measurements
            </h2>
        </div>

        <div class="card-body">
            <p class="chart-description text-muted mb-3">
                Investigate changes in methane and carbon-dioxide
                measurements over time at individual monitoring sites.
            </p>

            <div class="form-row">
                <div class="col-md-3 form-group">
                    <label for="temporalSite">Monitoring site</label>
                    <select
                        id="temporalSite"
                        class="form-control">
                    </select>
                </div>

                <div class="col-md-3 form-group">
                    <label for="temporalGas">Gas</label>
                    <select
                        id="temporalGas"
                        class="form-control">
                        <option value="ch4">Methane (CH₄)</option>
                        <option value="co2">
                            Carbon dioxide (CO₂)
                        </option>
                    </select>
                </div>

                <div class="col-md-3 form-group">
                    <label for="temporalChamber">Chamber</label>
                    <select
                        id="temporalChamber"
                        class="form-control">
                        @for($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}">
                                Chamber {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-3 form-group">
                    <label for="temporalAggregation">Aggregation</label>
                    <select
                        id="temporalAggregation"
                        class="form-control">
                        <option value="hourly">Hourly average</option>
                        <option value="daily">Daily average</option>
                        <option value="raw">Raw measurements</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="col-md-3 form-group">
                    <label for="temporalStart">Start date</label>
                    <input
                        id="temporalStart"
                        type="date"
                        class="form-control">
                </div>

                <div class="col-md-3 form-group">
                    <label for="temporalEnd">End date</label>
                    <input
                        id="temporalEnd"
                        type="date"
                        class="form-control">
                </div>

                <div class="col-md-6 d-flex align-items-end mb-3">
                    <div class="leak-actions w-100 justify-content-md-end">
                        <button
                            id="resetTemporal"
                            type="button"
                            class="btn btn-outline-secondary">
                            Reset filters
                        </button>

                        <button
                            id="downloadTemporalCsv"
                            type="button"
                            class="btn btn-outline-primary">
                            Download CSV
                        </button>

                        <button
                            id="reloadTemporal"
                            type="button"
                            class="btn btn-primary">
                            Update chart
                        </button>
                    </div>
                </div>
            </div>

            <div
                id="temporalChartStatus"
                class="chart-status text-muted mb-2"
                role="status"
                aria-live="polite">
            </div>

            <div class="chart-wrap">
                <canvas
                    id="temporalChart"
                    role="img"
                    aria-label="Time-series chart of soil gas flux measurements">
                </canvas>
            </div>

            <p class="chart-description text-muted mt-3 mb-0">
                <strong>How to read this chart:</strong>
                Measurements are plotted over time for the selected
                monitoring site and chamber. Hourly and daily
                options display aggregated averages.
            </p>
        </div>
    </section>

    {{-- Scientific context --}}
    <div class="row mb-4">
        <div class="col-lg-6 mb-3 mb-lg-0">
            <section class="card leak-info-card">
                <div class="card-header">
                    <h2 class="section-heading mb-0">
                        Understanding Soil Gas Flux
                    </h2>
                </div>

                <div class="card-body source-note">
                    <p>
                        Soil gas flux describes the movement of gases
                        between the soil surface and the atmosphere.
                    </p>

                    <p>
                        Positive flux values generally indicate gas
                        moving from the soil into the atmosphere.
                        Negative values can indicate movement in the
                        opposite direction.
                    </p>

                    <p>
                        Measurements may vary with location, soil
                        conditions, temperature, and time.
                    </p>
                </div>
            </section>
        </div>

        <div class="col-lg-6">
            <section class="card leak-info-card">
                <div class="card-header">
                    <h2 class="section-heading mb-0">
                        About the Research
                    </h2>
                </div>

                <div class="card-body source-note">
                    <p>
                        This dashboard presents well-pad soil survey
                        and temporal chamber measurements associated
                        with research on natural gas emissions in
                        Utah's Uinta Basin.
                    </p>

                    <p>
                        The measurements help illustrate localized
                        emissions and variability across monitoring
                        locations and measurement periods.
                    </p>

                    <p>
                        Consult the original publication and datasets
                        for complete methodology and interpretation.
                    </p>
                </div>
            </section>
        </div>
    </div>

    {{-- Publications --}}
    <section class="card mb-4" aria-labelledby="publicationsHeading">
        <div class="card-header">
            <h2 id="publicationsHeading" class="section-heading mb-0">
                Publications and Datasets
            </h2>
        </div>

        <div class="card-body source-note">
            <p>
                <strong>Publication:</strong>
                Strong Temporal Variability in Methane Fluxes
                from Natural Gas Well Pad Soils.
            </p>

            <p>
                <a
                    href="https://doi.org/10.1016/j.apr.2020.05.011"
                    target="_blank"
                    rel="noopener noreferrer">
                    View scientific publication
                </a>
            </p>

            <p>
                <a
                    href="https://digitalcommons.usu.edu/all_datasets/25/"
                    target="_blank"
                    rel="noopener noreferrer">
                    Well-pad soil flux dataset
                </a>
            </p>

            <p class="mb-0">
                <a
                    href="https://digitalcommons.usu.edu/all_datasets/111/"
                    target="_blank"
                    rel="noopener noreferrer">
                    Temporal methane flux dataset
                </a>
            </p>
        </div>
    </section>

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
