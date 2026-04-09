<?php

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\WebUpdateController;
use App\Http\Controllers\Backend\SensorController;
use Tabuna\Breadcrumbs\Trail;

Route::redirect('/', '/admin/dashboard', 301);

Route::get('dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->breadcrumbs(function (Trail $trail) {
        $trail->push(__('Home'), route('admin.dashboard'));
    });

Route::get('sensors/dashboard', [SensorController::class, 'index'])
    ->name('sensors.dashboard')
    ->breadcrumbs(function (Trail $trail) {
        $trail->parent('admin.dashboard')
            ->push(__('Sensor Dashboard'), route('admin.sensors.dashboard'));
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
    Route::post('/service/items', [WebUpdateController::class, 'storeServiceItem'])->name('service.items.store');
    Route::put('/service/items/{serviceItem}', [WebUpdateController::class, 'updateServiceItem'])->name('service.items.update');
    Route::delete('/service/items/{serviceItem}', [WebUpdateController::class, 'deleteServiceItem'])->name('service.items.delete');

    Route::get('/portfolio', [WebUpdateController::class, 'portfolio'])->name('portfolio');
    Route::post('/portfolio', [WebUpdateController::class, 'updatePortfolio'])->name('portfolio.update');
    Route::post('/portfolio/items', [WebUpdateController::class, 'storePortfolioItem'])->name('portfolio.items.store');
    Route::put('/portfolio/items/{portfolioItem}', [WebUpdateController::class, 'updatePortfolioItem'])->name('portfolio.items.update');
    Route::delete('/portfolio/items/{portfolioItem}', [WebUpdateController::class, 'deletePortfolioItem'])->name('portfolio.items.delete');
    Route::post('/portfolio/categories', [WebUpdateController::class, 'storePortfolioCategory'])->name('portfolio.categories.store');
    Route::put('/portfolio/categories/{portfolioCategory}', [WebUpdateController::class, 'updatePortfolioCategory'])->name('portfolio.categories.update');
    Route::delete('/portfolio/categories/{portfolioCategory}', [WebUpdateController::class, 'deletePortfolioCategory'])->name('portfolio.categories.delete');

    Route::get('/blog', [WebUpdateController::class, 'blog'])->name('blog');
    Route::post('/blog', [WebUpdateController::class, 'updateBlog'])->name('blog.update');
    Route::post('/blog/categories', [WebUpdateController::class, 'storeBlogCategory'])->name('blog.categories.store');
    Route::put('/blog/categories/{blogCategory}', [WebUpdateController::class, 'updateBlogCategory'])->name('blog.categories.update');
    Route::delete('/blog/categories/{blogCategory}', [WebUpdateController::class, 'deleteBlogCategory'])->name('blog.categories.delete');
    Route::post('/blog/posts', [WebUpdateController::class, 'storeBlogPost'])->name('blog.posts.store');
    Route::put('/blog/posts/{blogPost}', [WebUpdateController::class, 'updateBlogPost'])->name('blog.posts.update');
    Route::delete('/blog/posts/{blogPost}', [WebUpdateController::class, 'deleteBlogPost'])->name('blog.posts.delete');

    Route::get('/contact', [WebUpdateController::class, 'contact'])->name('contact');
    Route::post('/contact', [WebUpdateController::class, 'updateContact'])->name('contact.update');
    Route::delete('/contact/messages/{contactMessage}', [WebUpdateController::class, 'deleteContactMessage'])->name('contact.messages.delete');
});