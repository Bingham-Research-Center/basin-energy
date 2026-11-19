<?php

use App\Http\Controllers\Frontend\User\AccountController;
use App\Http\Controllers\Frontend\User\DashboardController;
use App\Http\Controllers\Frontend\User\DataController;
use App\Http\Controllers\Frontend\User\ProfileController;
use App\Http\Controllers\Frontend\CarbonMapperController;
use Tabuna\Breadcrumbs\Trail;

/*
 * These frontend controllers require the user to be logged in
 * All route names are prefixed with 'frontend.'
 * These routes can not be hit if the user has not confirmed their email
 */
Route::group(['as' => 'user.', 'middleware' => ['auth', '2fa.confirmed', 'password.expires', config('boilerplate.access.middleware.verified')]], function () {
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware('is_user')
        ->name('dashboard')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('frontend.index')->push(__('Dashboard'), route('frontend.user.dashboard'));
        });

    Route::get('account', [AccountController::class, 'index'])
        ->name('account')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('frontend.index')->push(__('My Account'), route('frontend.user.account'));
        });

    Route::group([
    'prefix' => 'data',
    'as' => 'data.',
    'middleware' => 'is_user',
    ], function () {

        Route::get('/', function () {
            return redirect()->route('frontend.user.data.emission-trends');
        })->name('data')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('frontend.user.dashboard')
                ->push(__('Data'), route('frontend.user.data.data'));
        });


        Route::get('emission-trends', [DataController::class, 'emissionTrends'])
            ->name('emission-trends')
            ->breadcrumbs(function (Trail $trail) {
                $trail->parent('frontend.user.data.data')
                    ->push(__('Emission Trends'), route('frontend.user.data.emission-trends'));
            });

        Route::get('emission-trends/json', [DataController::class, 'emissionTrendsJson'])
            ->name('emission-trends.json');

        Route::get('produced-water', [DataController::class, 'producedWater'])
            ->name('produced-water')
            ->breadcrumbs(function (Trail $trail) {
                $trail->parent('frontend.user.data.data')
                    ->push(__('Produced Water'), route('frontend.user.data.produced-water'));
            });

        Route::prefix('produced-water')->group(function () {
            Route::get('flux/columns', [DataController::class, 'producedWaterFluxColumns'])
                ->name('produced-water.flux.columns');
            Route::get('flux/json', [DataController::class, 'producedWaterFluxJson'])
                ->name('produced-water.flux.json');
            Route::get('chemistry/json', [DataController::class, 'producedWaterChemistryJson'])
                ->name('produced-water.chemistry.json');
            Route::get('relationships/json', [DataController::class, 'producedWaterRelationshipsJson'])
                ->name('produced-water.relationships.json');
        });

        Route::get('subsurface-natural-gas-leaks', [DataController::class, 'subsurfaceNaturalGasLeaks'])
            ->name('subsurface-natural-gas-leaks')
            ->breadcrumbs(function (Trail $trail) {
                $trail->parent('frontend.user.data.data')
                    ->push(__('Subsurface Natural Gas Leaks'), route('frontend.user.data.subsurface-natural-gas-leaks'));
            });

        Route::get('subsurface-natural-gas-leaks/overview/json', [DataController::class, 'subsurfaceNaturalGasLeaksOverviewJson'])
            ->name('subsurface-natural-gas-leaks.overview.json');

        Route::get('subsurface-natural-gas-leaks/survey/json', [DataController::class, 'subsurfaceNaturalGasLeaksSurveyJson'])
            ->name('subsurface-natural-gas-leaks.survey.json');

        Route::get('subsurface-natural-gas-leaks/temporal/json', [DataController::class, 'subsurfaceNaturalGasLeaksTemporalJson'])
            ->name('subsurface-natural-gas-leaks.temporal.json');

        Route::get('subsurface-natural-gas-leaks/options/json', [DataController::class, 'subsurfaceNaturalGasLeaksOptionsJson'])
            ->name('subsurface-natural-gas-leaks.options.json');

        Route::get('oil-well-pad-emissions', [DataController::class, 'oilWellPadEmissions'])
            ->name('oil-well-pad-emissions')
            ->breadcrumbs(function (Trail $trail) {
                $trail->parent('frontend.user.data.data')
                    ->push(__('Oil Well Pad Emissions'), route('frontend.user.data.oil-well-pad-emissions'));
            });

        Route::get('oil-well-pad-emissions/overview/json', [DataController::class, 'oilWellPadEmissionsOverviewJson'])
            ->name('oil-well-pad-emissions.overview.json');

        Route::get('oil-well-pad-emissions/samples/json', [DataController::class, 'oilWellPadEmissionsSamplesJson'])
            ->name('oil-well-pad-emissions.samples.json');

        Route::get('oil-well-pad-emissions/composition/json', [DataController::class, 'oilWellPadEmissionsCompositionJson'])
            ->name('oil-well-pad-emissions.composition.json');

        Route::get('oil-well-pad-emissions/options/json', [DataController::class, 'oilWellPadEmissionsOptionsJson'])
            ->name('oil-well-pad-emissions.options.json');

        /* Realtime Data */
        Route::get('realtime/ozone', [DataController::class, 'realtimeOzone'])
            ->name('realtime.ozone')
            ->breadcrumbs(function (Trail $trail) {
                $trail->parent('frontend.user.data.data')
                    ->push(__('Real-Time Data'))
                    ->push(__('Uinta Ozone'), route('frontend.user.data.realtime.ozone'));
            });

        Route::get('realtime/ozone/json', [DataController::class, 'realtimeOzoneJson'])
            ->name('realtime.ozone.json');

        /* Other Monitoring Sites */
        Route::get('realtime/other/{site}', [DataController::class, 'otherMonitoringSite'])
            ->name('realtime.other')
            ->breadcrumbs(function (Trail $trail) {
                $site = (string) request()->route('site');
                $name = config('services.other_monitoring_sites.'.$site.'.name')
                    ?? \Illuminate\Support\Str::headline($site);
                $trail->parent('frontend.user.data.data')
                    ->push(__('Real-Time Data'))
                    ->push((string) $name, route('frontend.user.data.realtime.other', ['site' => $site]));
            });

        Route::get('realtime/logger/{site}', [DataController::class, 'campbellLogger'])
            ->name('realtime.logger')
            ->breadcrumbs(function (Trail $trail) {
                $site = (string) request()->route('site');
                $name = config('services.campbell_sites.'.$site.'.name')
                    ?? \Illuminate\Support\Str::headline($site);
                $trail->parent('frontend.user.data.data')
                    ->push(__('Real-Time Data'))
                    ->push((string) $name, route('frontend.user.data.realtime.logger', ['site' => $site]));
            });

        Route::get('realtime/logger/{site}/json', [DataController::class, 'campbellLoggerJson'])
            ->name('realtime.logger.json');

        Route::get('realtime/logger/{site}/history/json', [DataController::class, 'campbellLoggerHistoryJson'])
            ->name('realtime.logger.history.json');

        /* Backward-compatible old Horsepool URL */
        Route::get('realtime/horsepool', function () {
            return redirect()->route('frontend.user.data.realtime.logger', ['site' => 'horsepool']);
        })->name('realtime.horsepool');

        Route::get('realtime/horsepool/json', function () {
            return redirect()->route('frontend.user.data.realtime.logger.json', ['site' => 'horsepool']);
        })->name('realtime.horsepool.json');

        Route::post('ai-summary/emission-trends', [DataController::class, 'emissionTrendsAiSummary'])
            ->name('ai-summary.emission-trends');

    });

    Route::group([
        'prefix' => 'carbon-mapper',
        'as' => 'carbon-mapper.',
        'middleware' => 'is_user',
    ], function () {

        Route::get('/utah', [CarbonMapperController::class, 'utah'])
            ->name('utah')
            ->breadcrumbs(function (Trail $trail) {
                $trail->parent('frontend.user.dashboard')
                    ->push(__('Carbon Mapper'))
                    ->push(__('Utah Super-Emitters'), route('frontend.user.carbon-mapper.utah'));
            });

        Route::get('/utah/json', [CarbonMapperController::class, 'utahJson'])
            ->name('utah.json');

        Route::get('/utah/plume/{observationId}', [CarbonMapperController::class, 'utahPlume'])
    ->name('utah.plume');

            Route::get('/utah/boundary', [CarbonMapperController::class, 'utahBoundary'])
    ->name('utah.boundary');

        Route::get('/utah/detections', [CarbonMapperController::class, 'utahDetections'])
            ->name('utah.detections');
        Route::get('/utah/sources', [CarbonMapperController::class, 'utahSources'])
            ->name('utah.sources');


    });


    // Personal User updates belong to User-type accounts. Administrators
    // must use their dedicated /admin/account/profile endpoint instead.
    Route::patch('profile/update', [ProfileController::class, 'update'])
        ->middleware('is_user')
        ->name('profile.update');
});
