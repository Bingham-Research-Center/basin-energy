@extends('backend.layouts.app')

@section('title', __('Dashboard'))

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}?v=2">
@endpush

@section('content')
<div class="dashboard-page dashboard-fill-page brc-admin-overview">
    <div class="dashboard-toolbar mb-3">
        <div>
            <div class="dashboard-eyebrow">@lang('Bingham Research Center')</div>
            <h1 class="dashboard-title mb-1">@lang('Administration Overview')</h1>
            <p class="dashboard-muted mb-0">
                @lang('Local weather, Uinta Basin ozone observations and recent user activity.')
            </p>
        </div>
    </div>

    <section class="brc-weather-section mb-4" id="adminWeatherSummary"
        data-weather-url="{{ route('admin.weather.current') }}"
        aria-labelledby="adminWeatherTitle">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
            <div>
                <h2 id="adminWeatherTitle" class="dashboard-chart-title mb-1">@lang('Uinta Basin weather')</h2>
                <p class="dashboard-chart-subtitle mb-1">
                    @lang('Vernal Regional Airport (KVEL) observations — National Weather Service')
                </p>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="adminWeatherRefresh">
                <i class="c-icon cil-reload mr-1" aria-hidden="true"></i>@lang('Refresh weather')
            </button>
        </div>
        <div class="row brc-weather-card-grid">
            <div class="col-6 col-xl-3 mb-3">
                <div class="brc-overview-metric h-100">
                    <div class="brc-overview-metric-label">@lang('Temperature')</div>
                    <div class="brc-overview-metric-number" id="adminWeatherTemperature">—</div>
                    <div class="brc-overview-metric-note">@lang('Air temperature')</div>
                </div>
            </div>
            <div class="col-6 col-xl-3 mb-3">
                <div class="brc-overview-metric h-100">
                    <div class="brc-overview-metric-label">@lang('Wind')</div>
                    <div class="brc-overview-metric-number" id="adminWeatherWind">—</div>
                    <div class="brc-overview-metric-note" id="adminWeatherWindDirection">@lang('Station wind speed')</div>
                </div>
            </div>
            <div class="col-6 col-xl-3 mb-3">
                <div class="brc-overview-metric h-100">
                    <div class="brc-overview-metric-label">@lang('Humidity')</div>
                    <div class="brc-overview-metric-number" id="adminWeatherHumidity">—</div>
                    <div class="brc-overview-metric-note">@lang('Relative humidity')</div>
                </div>
            </div>
            <div class="col-6 col-xl-3 mb-3">
                <div class="brc-overview-metric brc-weather-conditions h-100">
                    <div class="brc-overview-metric-label">@lang('Conditions')</div>
                    <div class="brc-overview-metric-number" id="adminWeatherConditions">—</div>
                    <div class="brc-overview-metric-note">@lang('Reported at KVEL')</div>
                </div>
            </div>
        </div>
        <p class="small dashboard-muted mb-0" id="adminWeatherStatus" role="status" aria-live="polite">
            @lang('Loading National Weather Service station observations...')
        </p>
        <p class="small dashboard-muted mt-1 mb-0">
            @lang('Station measurements represent conditions near Vernal, not a basin-wide average.')
            <a href="https://www.weather.gov/documentation/services-web-api" target="_blank" rel="noopener noreferrer">
                @lang('Source: NWS')
            </a>
        </p>
    </section>

    <section class="card dashboard-chart-card mb-4" id="adminOzoneTrend"
        data-history-url-template="{{ route('admin.data.realtime.logger.history.json', ['site' => '__STATION__']) }}"
        aria-labelledby="adminOzoneTrendTitle">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="dashboard-chart-title mb-1" id="adminOzoneTrendTitle">@lang('Ozone trend')</h2>
                <p class="dashboard-chart-subtitle mb-0">
                    @lang('1-hour observations from four Uinta Basin monitoring stations')
                </p>
            </div>
            <div class="brc-overview-actions">
                <span class="badge badge-light border mr-2">@lang('Last 24 records')</span>
                <button type="button" class="btn btn-sm btn-outline-secondary mr-2"
                    id="adminOzoneRefresh">@lang('Refresh')</button>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.data.realtime.ozone') }}">
                    @lang('Full monitoring dashboard')
                </a>
            </div>
        </div>
        <div class="card-body">
            <p class="small dashboard-muted mb-2" id="adminOzoneStatus" role="status" aria-live="polite">
                @lang('Loading recent ozone observations...')
            </p>
            <div class="brc-admin-ozone-chart">
                <canvas id="adminOzoneChart" role="img"
                    aria-label="{{ __('Hourly ozone trends for Seven Sisters, Horsepool, Castle Peak and Roosevelt') }}"></canvas>
            </div>
        </div>
    </section>

    <div class="row">
        <div class="col-12 col-xl-8 mb-4">
            <section class="card dashboard-panel-card h-100" aria-labelledby="recentUsersHeading">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="dashboard-chart-title mb-0" id="recentUsersHeading">@lang('Recently signed-in users')</h2>
                        <small class="dashboard-chart-subtitle">@lang('Latest sign-ins from regular user accounts only')</small>
                    </div>
                    @if ($canViewUsers)
                        <a href="{{ route('admin.auth.user.index') }}" class="btn btn-sm btn-outline-primary">
                            @lang('All users')
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    @if ($canViewUsers)
                        <div class="table-responsive brc-admin-data-table">
                            <table class="table table-sm brc-overview-user-table">
                                <thead>
                                    <tr>
                                        <th scope="col">@lang('Name')</th>
                                        <th scope="col">@lang('Active')</th>
                                        <th scope="col">@lang('Roles')</th>
                                        <th scope="col" title="{{ __('Last recorded sign-in, Mountain time') }}">
                                            @lang('Last seen')
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentUsers as $user)
                                        <tr>
                                            <td>
                                                <strong>{{ $user->name }}</strong>
                                                <small class="d-block dashboard-muted">{{ ucfirst($user->type) }}</small>
                                            </td>
                                            <td>
                                                @include('backend.includes.table-pill', [
                                                    'text' => $user->active ? __('Yes') : __('No'),
                                                    'tone' => $user->active ? 'success' : 'danger',
                                                ])
                                            </td>
                                            <td>
                                                @forelse ($user->roles as $role)
                                                    <span class="badge badge-light border mr-1 mb-1">{{ $role->name }}</span>
                                                @empty
                                                    <span class="dashboard-muted">@lang('None')</span>
                                                @endforelse
                                            </td>
                                            <td class="text-nowrap">
                                                @if ($user->last_login_at)
                                                    <time datetime="{{ $user->last_login_at->toIso8601String() }}"
                                                        title="{{ __('Last recorded sign-in, America/Denver') }}">
                                                        {{ $user->last_login_at->copy()->timezone('America/Denver')->format('jS M H:i') }}
                                                    </time>
                                                @else
                                                    <span class="dashboard-muted">@lang('Never')</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center dashboard-muted py-4">
                                                @lang('No user sign-ins have been recorded yet.')
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <small class="dashboard-muted d-block mt-2">
                            @lang('Last seen reflects the latest recorded sign-in, not continuous online presence. Times are Mountain time.')
                        </small>
                    @else
                        <p class="dashboard-muted mb-0">
                            @lang('User information requires the User Management viewing permission.')
                        </p>
                    @endif
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-4 mb-4">
            <section class="card dashboard-panel-card h-100" aria-labelledby="adminShortcutsTitle">
                <div class="card-header">
                    <h2 class="dashboard-chart-title mb-0" id="adminShortcutsTitle">@lang('Research shortcuts')</h2>
                </div>
                <div class="card-body">
                    <div class="brc-overview-shortcuts">
                        <a href="{{ route('admin.data.emission-trends') }}">
                            <i class="c-icon cil-chart-line" aria-hidden="true"></i> @lang('Emission Trends')
                        </a>
                        <a href="{{ route('admin.data.carbon-mapper.utah') }}">
                            <i class="c-icon cil-map" aria-hidden="true"></i> @lang('Carbon Mapper')
                        </a>
                        <a href="{{ route('admin.data.produced-water') }}">
                            <i class="c-icon cil-drop" aria-hidden="true"></i> @lang('Produced Water')
                        </a>
                        <a href="{{ route('admin.data.realtime.ozone') }}">
                            <i class="c-icon cil-speedometer" aria-hidden="true"></i> @lang('Uinta Ozone monitoring')
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
