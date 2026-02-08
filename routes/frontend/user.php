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
Route::group(['as' => 'user.', 'middleware' => ['auth', 'password.expires', config('boilerplate.access.middleware.verified')]], function () {
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
    ], function () {

        Route::get('/', function () {
            return redirect()->route('frontend.user.data.emission-trends');
        })->name('data');


        Route::get('emission-trends', [DataController::class, 'emissionTrends'])
            ->name('emission-trends');

        Route::get('emission-trends/json', [DataController::class, 'emissionTrendsJson'])
            ->name('emission-trends.json');

        Route::get('produced-water', [DataController::class, 'producedWater'])
            ->name('produced-water');

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
    });

    Route::group([
        'prefix' => 'carbon-mapper',
        'as' => 'carbon-mapper.',
    ], function () {

        Route::get('/utah', [CarbonMapperController::class, 'utah'])
            ->name('utah');

        Route::get('/utah/json', [CarbonMapperController::class, 'utahJson'])
            ->name('utah.json');

        Route::get('/utah/detections', [CarbonMapperController::class, 'utahDetections'])
            ->name('utah.detections');

        Route::get('/utah/sources', [CarbonMapperController::class, 'utahSources'])
            ->name('utah.sources');
    });

    
    Route::patch('profile/update', [ProfileController::class, 'update'])->name('profile.update');
});
