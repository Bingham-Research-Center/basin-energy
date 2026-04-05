<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\TermsController;
use App\Http\Controllers\Frontend\DroneController;
use App\Http\Controllers\Frontend\PageController;
use Tabuna\Breadcrumbs\Trail;

/*
 * Frontend Controllers
 * All route names are prefixed with 'frontend.'.
 */
Route::get('/', [HomeController::class, 'index'])
    ->name('index')
    ->breadcrumbs(function (Trail $trail) {
        $trail->push(__('Home'), route('frontend.index'));
    });

Route::get('terms', [TermsController::class, 'index'])
    ->name('pages.terms')
    ->breadcrumbs(function (Trail $trail) {
        $trail->parent('frontend.index')
            ->push(__('Terms & Conditions'), route('frontend.pages.terms'));
    });

Route::get('drone', [DroneController::class, 'index'])
    ->name('pages.drone')
    ->breadcrumbs(function (Trail $trail) {
        $trail->parent('frontend.index')
            ->push(__('Drone Data Test Results'), route('frontend.pages.drone'));
    });

Route::get('about', [PageController::class, 'about'])
    ->name('pages.about');

Route::get('service', [PageController::class, 'service'])
    ->name('pages.service');

Route::get('pricing', [PageController::class, 'pricing'])
    ->name('pages.pricing');

Route::get('portfolio', [PageController::class, 'portfolio'])
    ->name('pages.portfolio');

Route::get('blog', [PageController::class, 'blog'])
    ->name('pages.blog');

Route::get('blog/{slug}', [PageController::class, 'blogSingle'])
    ->name('pages.blogSingle');

Route::get('contact', [PageController::class, 'contact'])
    ->name('pages.contact');
Route::post('contact', [PageController::class, 'submitContact'])
    ->name('pages.contact.submit');