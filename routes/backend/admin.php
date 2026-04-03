<?php

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\WebUpdateController;
use Tabuna\Breadcrumbs\Trail;

Route::redirect('/', '/admin/dashboard', 301);

Route::get('dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->breadcrumbs(function (Trail $trail) {
        $trail->push(__('Home'), route('admin.dashboard'));
    });

Route::prefix('website-update')->as('website-update.')->group(function () {
    Route::get('/', [WebUpdateController::class, 'index'])->name('index');

    Route::get('/home', [WebUpdateController::class, 'home'])->name('home');
    Route::post('/home', [WebUpdateController::class, 'updateHome'])->name('home.update');

    Route::get('/about', [WebUpdateController::class, 'about'])->name('about');
    Route::post('/about', [WebUpdateController::class, 'updateAbout'])->name('about.update');

    Route::post('/about/team-members', [WebUpdateController::class, 'storeTeamMember'])->name('about.team-members.store');
    Route::put('/about/team-members/{teamMember}', [WebUpdateController::class, 'updateTeamMember'])->name('about.team-members.update');
    Route::delete('/about/team-members/{teamMember}', [WebUpdateController::class, 'deleteTeamMember'])->name('about.team-members.delete');

    Route::get('/service', [WebUpdateController::class, 'service'])->name('service');
    Route::post('/service', [WebUpdateController::class, 'updateService'])->name('service.update');

    Route::get('/portfolio', [WebUpdateController::class, 'portfolio'])->name('portfolio');
    Route::post('/portfolio', [WebUpdateController::class, 'updatePortfolio'])->name('portfolio.update');

    Route::get('/blog', [WebUpdateController::class, 'blog'])->name('blog');
    Route::post('/blog', [WebUpdateController::class, 'updateBlog'])->name('blog.update');

    Route::get('/contact', [WebUpdateController::class, 'contact'])->name('contact');
    Route::post('/contact', [WebUpdateController::class, 'updateContact'])->name('contact.update');
});