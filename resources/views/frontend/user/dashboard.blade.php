@extends('frontend.layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="dashboard-page dashboard-page-pro">
    <div class="row">
        <div class="col-sm-6 col-xl-3 mb-4">
            <a href="{{ route('frontend.user.data.emission-trends') }}" class="card dashboard-stat-card dashboard-stat-bg-primary text-reset text-decoration-none">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="dashboard-stat-value">@lang('Emissions')</div>
                            <div class="dashboard-stat-label">@lang('Trend records')</div>
                        </div>
                        <i class="c-icon cil-chart-line"></i>
                    </div>
                    <div class="dashboard-sparkline">
                        <div class="progress bg-transparent mt-4" style="height: 3px;">
                            <div class="progress-bar bg-white" style="width: 72%; opacity: .75;"></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3 mb-4">
            <a href="{{ route('frontend.user.carbon-mapper.utah') }}" class="card dashboard-stat-card dashboard-stat-bg-info text-reset text-decoration-none">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="dashboard-stat-value">@lang('Mapper')</div>
                            <div class="dashboard-stat-label">@lang('Utah observations')</div>
                        </div>
                        <i class="c-icon cil-map"></i>
                    </div>
                    <div class="dashboard-sparkline">
                        <div class="progress bg-transparent mt-4" style="height: 3px;">
                            <div class="progress-bar bg-white" style="width: 58%; opacity: .75;"></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3 mb-4">
            <a href="{{ route('frontend.user.data.produced-water') }}" class="card dashboard-stat-card dashboard-stat-bg-warning text-reset text-decoration-none">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="dashboard-stat-value">@lang('Water')</div>
                            <div class="dashboard-stat-label">@lang('Chemistry and flux')</div>
                        </div>
                        <i class="c-icon cil-drop"></i>
                    </div>
                    <div class="dashboard-sparkline">
                        <div class="progress bg-transparent mt-4" style="height: 3px;">
                            <div class="progress-bar bg-white" style="width: 44%; opacity: .75;"></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3 mb-4">
            <a href="{{ route('frontend.user.data.realtime.ozone') }}" class="card dashboard-stat-card dashboard-stat-bg-danger text-reset text-decoration-none">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="dashboard-stat-value">@lang('Ozone')</div>
                            <div class="dashboard-stat-label">@lang('Realtime monitoring')</div>
                        </div>
                        <i class="c-icon cil-clock"></i>
                    </div>
                    <div class="dashboard-sparkline">
                        <div class="progress bg-transparent mt-4" style="height: 3px;">
                            <div class="progress-bar bg-white" style="width: 66%; opacity: .75;"></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card dashboard-chart-card mb-4">
        <div class="card-header">
            <div>
                <h2 class="dashboard-chart-title">@lang('Research Data Overview')</h2>
                <div class="dashboard-chart-subtitle">@lang('Quick access to Bingham Research Center datasets')</div>
            </div>
            <div class="btn-group">
                <a href="{{ route('frontend.user.data.realtime.ozone') }}" class="btn btn-sm btn-primary">
                    <i class="c-icon cil-clock mr-1"></i> @lang('Realtime ozone')
                </a>
                <a href="{{ route('frontend.user.data.realtime.horsepool') }}" class="btn btn-sm btn-outline-primary">
                    <i class="c-icon cil-speedometer mr-1"></i> @lang('Horsepool')
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 col-xl-3 mb-3">
                    <a href="{{ route('frontend.user.data.emission-trends') }}" class="dashboard-dataset-card card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="dashboard-dataset-icon"><i class="c-icon cil-chart-line"></i></span>
                                <span class="badge badge-primary">@lang('Dataset')</span>
                            </div>
                            <h4 class="mb-1">@lang('Emission Trends')</h4>
                            <p class="dashboard-muted mb-0">@lang('Oil and gas emission trend records.')</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-xl-3 mb-3">
                    <a href="{{ route('frontend.user.carbon-mapper.utah') }}" class="dashboard-dataset-card card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="dashboard-dataset-icon"><i class="c-icon cil-map"></i></span>
                                <span class="badge badge-info">@lang('Map')</span>
                            </div>
                            <h4 class="mb-1">@lang('Carbon Mapper')</h4>
                            <p class="dashboard-muted mb-0">@lang('Utah super-emitter observations.')</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-xl-3 mb-3">
                    <a href="{{ route('frontend.user.data.produced-water') }}" class="dashboard-dataset-card card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="dashboard-dataset-icon"><i class="c-icon cil-drop"></i></span>
                                <span class="badge badge-warning">@lang('Water')</span>
                            </div>
                            <h4 class="mb-1">@lang('Produced Water')</h4>
                            <p class="dashboard-muted mb-0">@lang('Produced water chemistry and flux data.')</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-xl-3 mb-3">
                    <a href="{{ route('frontend.user.data.realtime.ozone') }}" class="dashboard-dataset-card card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="dashboard-dataset-icon"><i class="c-icon cil-clock"></i></span>
                                <span class="badge badge-danger">@lang('Live')</span>
                            </div>
                            <h4 class="mb-1">@lang('Realtime Ozone')</h4>
                            <p class="dashboard-muted mb-0">@lang('Uinta Basin station monitoring.')</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <div class="dashboard-progress-grid">
                <div>
                    <div class="dashboard-progress-label">@lang('Emissions')</div>
                    <div class="dashboard-progress-value">@lang('Trends')</div>
                    <div class="progress progress-xs"><div class="progress-bar bg-primary" style="width: 72%"></div></div>
                </div>
                <div>
                    <div class="dashboard-progress-label">@lang('Carbon')</div>
                    <div class="dashboard-progress-value">@lang('Mapper')</div>
                    <div class="progress progress-xs"><div class="progress-bar bg-info" style="width: 58%"></div></div>
                </div>
                <div>
                    <div class="dashboard-progress-label">@lang('Produced')</div>
                    <div class="dashboard-progress-value">@lang('Water')</div>
                    <div class="progress progress-xs"><div class="progress-bar bg-warning" style="width: 44%"></div></div>
                </div>
                <div>
                    <div class="dashboard-progress-label">@lang('Realtime')</div>
                    <div class="dashboard-progress-value">@lang('Ozone')</div>
                    <div class="progress progress-xs"><div class="progress-bar bg-danger" style="width: 66%"></div></div>
                </div>
                <div>
                    <div class="dashboard-progress-label">@lang('Logger')</div>
                    <div class="dashboard-progress-value">@lang('Horsepool')</div>
                    <div class="progress progress-xs"><div class="progress-bar bg-success" style="width: 52%"></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card dashboard-panel-card h-100">
                <div class="card-header">
                    <strong>@lang('Realtime data')</strong>
                    <span class="badge badge-success">@lang('Available')</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('frontend.user.data.realtime.horsepool') }}" class="btn btn-block btn-outline-primary text-left">
                                <i class="c-icon cil-speedometer mr-2"></i> @lang('Horsepool Campbell Logger')
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('frontend.user.data.realtime.ozone') }}" class="btn btn-block btn-outline-primary text-left">
                                <i class="c-icon cil-clock mr-2"></i> @lang('Realtime Uinta Ozone')
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card dashboard-panel-card h-100">
                <div class="card-header"><strong>@lang('Account')</strong></div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <img class="c-avatar-img mr-3" style="width: 48px; height: 48px; border-radius: 50%;" src="{{ $logged_in_user->avatar }}" alt="{{ $logged_in_user->email }}">
                        <div>
                            <div class="font-weight-bold">{{ $logged_in_user->name }}</div>
                            <div class="dashboard-muted small">{{ $logged_in_user->email }}</div>
                        </div>
                    </div>
                    <a href="{{ route('frontend.user.account') }}" class="btn btn-primary btn-block">
                        <i class="c-icon cil-user mr-1"></i> @lang('Manage account')
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection