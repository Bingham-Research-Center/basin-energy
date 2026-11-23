<?php

use App\Http\Controllers\Frontend\CarbonMapperController;
use App\Http\Controllers\Frontend\User\DataController;
use Illuminate\Support\Str;
use Tabuna\Breadcrumbs\Trail;

/*
 * Admin dashboard access to the existing research data and real-time
 * monitoring controllers. The enclosing /admin route group applies the
 * 'admin' middleware to every page AND JSON/AI endpoint here.
 *
 * Keep the original frontend.user.* routes strictly 'is_user' protected.
 */
Route::prefix('data')->as('data.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.data.emission-trends');
    })->name('index')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.dashboard')
                ->push(__('Data'), route('admin.data.index'));
        });

    Route::get('emission-trends', [DataController::class, 'emissionTrends'])
        ->name('emission-trends')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.data.index')
                ->push(__('Emission Trends'), route('admin.data.emission-trends'));
        });
    Route::get('emission-trends/json', [DataController::class, 'emissionTrendsJson'])
        ->name('emission-trends.json');

    Route::get('produced-water', [DataController::class, 'producedWater'])
        ->name('produced-water')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.data.index')
                ->push(__('Produced Water'), route('admin.data.produced-water'));
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
            $trail->parent('admin.data.index')
                ->push(__('Natural Gas Leaks'), route('admin.data.subsurface-natural-gas-leaks'));
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
            $trail->parent('admin.data.index')
                ->push(__('Oil Well Pad Emissions'), route('admin.data.oil-well-pad-emissions'));
        });
    Route::get('oil-well-pad-emissions/overview/json', [DataController::class, 'oilWellPadEmissionsOverviewJson'])
        ->name('oil-well-pad-emissions.overview.json');
    Route::get('oil-well-pad-emissions/samples/json', [DataController::class, 'oilWellPadEmissionsSamplesJson'])
        ->name('oil-well-pad-emissions.samples.json');
    Route::get('oil-well-pad-emissions/composition/json', [DataController::class, 'oilWellPadEmissionsCompositionJson'])
        ->name('oil-well-pad-emissions.composition.json');
    Route::get('oil-well-pad-emissions/options/json', [DataController::class, 'oilWellPadEmissionsOptionsJson'])
        ->name('oil-well-pad-emissions.options.json');

    Route::post('ai-summary/emission-trends', [DataController::class, 'emissionTrendsAiSummary'])
        ->name('ai-summary.emission-trends');

    Route::prefix('carbon-mapper')->as('carbon-mapper.')->group(function () {
        Route::get('utah', [CarbonMapperController::class, 'utah'])
            ->name('utah')
            ->breadcrumbs(function (Trail $trail) {
                $trail->parent('admin.data.index')
                    ->push(__('Carbon Mapper'))
                    ->push(__('Utah Super-Emitters'), route('admin.data.carbon-mapper.utah'));
            });
        Route::get('utah/json', [CarbonMapperController::class, 'utahJson'])
            ->name('utah.json');
        Route::get('utah/plume/{observationId}', [CarbonMapperController::class, 'utahPlume'])
            ->name('utah.plume');
        Route::get('utah/boundary', [CarbonMapperController::class, 'utahBoundary'])
            ->name('utah.boundary');
        Route::get('utah/detections', [CarbonMapperController::class, 'utahDetections'])
            ->name('utah.detections');
        Route::get('utah/sources', [CarbonMapperController::class, 'utahSources'])
            ->name('utah.sources');
    });

    Route::prefix('realtime')->as('realtime.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.data.realtime.ozone');
        })->name('index')
            ->breadcrumbs(function (Trail $trail) {
                $trail->parent('admin.dashboard')
                    ->push(__('Real-Time Data'), route('admin.data.realtime.index'));
            });

        Route::get('ozone', [DataController::class, 'realtimeOzone'])
            ->name('ozone')
            ->breadcrumbs(function (Trail $trail) {
                $trail->parent('admin.data.realtime.index')
                    ->push(__('Uinta Ozone'), route('admin.data.realtime.ozone'));
            });
        Route::get('ozone/json', [DataController::class, 'realtimeOzoneJson'])
            ->name('ozone.json');

        Route::get('logger/{site}', [DataController::class, 'campbellLogger'])
            ->name('logger')
            ->breadcrumbs(function (Trail $trail) {
                $site = (string) request()->route('site');
                $name = config('services.campbell_sites.'.$site.'.name') ?? Str::headline($site);
                $trail->parent('admin.data.realtime.index')
                    ->push((string) $name, route('admin.data.realtime.logger', ['site' => $site]));
            });
        Route::get('logger/{site}/json', [DataController::class, 'campbellLoggerJson'])
            ->name('logger.json');
        Route::get('logger/{site}/history/json', [DataController::class, 'campbellLoggerHistoryJson'])
            ->name('logger.history.json');

        Route::get('other/{site}', [DataController::class, 'otherMonitoringSite'])
            ->name('other')
            ->breadcrumbs(function (Trail $trail) {
                $site = (string) request()->route('site');
                $name = config('services.other_monitoring_sites.'.$site.'.name') ?? Str::headline($site);
                $trail->parent('admin.data.realtime.index')
                    ->push((string) $name, route('admin.data.realtime.other', ['site' => $site]));
            });
    });
});
