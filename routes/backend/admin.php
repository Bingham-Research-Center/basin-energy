<?php

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\WebUpdateController;
use App\Http\Controllers\Backend\SensorController;
use Tabuna\Breadcrumbs\Trail;
use App\Domains\Auth\Support\WebsiteContentPermissions;

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
    Route::get('/', [WebUpdateController::class, 'index'])
        ->middleware(WebsiteContentPermissions::middlewareForAny())
        ->name('index')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.dashboard')
                ->push(__('Website Updates'), route('admin.website-update.index'));
        });

    Route::middleware(WebsiteContentPermissions::middlewareFor('home'))->group(function () {
    Route::get('/home', [WebUpdateController::class, 'home'])->name('home')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.website-update.index')
                ->push(__('Home'), route('admin.website-update.home'));
        });
    Route::post('/home', [WebUpdateController::class, 'updateHome'])->name('home.update');
    });

    Route::middleware(WebsiteContentPermissions::middlewareFor('about'))->group(function () {
    Route::get('/about', [WebUpdateController::class, 'about'])->name('about')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.website-update.index')
                ->push(__('About'), route('admin.website-update.about'));
        });
    Route::post('/about', [WebUpdateController::class, 'updateAbout'])->name('about.update');
    Route::post('/about/team-members', [WebUpdateController::class, 'storeTeamMember'])->name('about.team-members.store');
    Route::put('/about/team-members/{teamMember}', [WebUpdateController::class, 'updateTeamMember'])->name('about.team-members.update');
    Route::delete('/about/team-members/{teamMember}', [WebUpdateController::class, 'deleteTeamMember'])->name('about.team-members.delete');
    });

    Route::middleware(WebsiteContentPermissions::middlewareFor('service'))->group(function () {
    Route::get('/service', [WebUpdateController::class, 'service'])->name('service')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.website-update.index')
                ->push(__('Service'), route('admin.website-update.service'));
        });
    Route::post('/service', [WebUpdateController::class, 'updateService'])->name('service.update');
    Route::post('/service/items', [WebUpdateController::class, 'storeServiceItem'])->name('service.items.store');
    Route::put('/service/items/{serviceItem}', [WebUpdateController::class, 'updateServiceItem'])->name('service.items.update');
    Route::delete('/service/items/{serviceItem}', [WebUpdateController::class, 'deleteServiceItem'])->name('service.items.delete');
    });

    Route::middleware(WebsiteContentPermissions::middlewareFor('portfolio'))->group(function () {
    Route::get('/portfolio', [WebUpdateController::class, 'portfolio'])->name('portfolio')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.website-update.index')
                ->push(__('Portfolio'), route('admin.website-update.portfolio'));
        });
    Route::post('/portfolio', [WebUpdateController::class, 'updatePortfolio'])->name('portfolio.update');
    Route::post('/portfolio/items', [WebUpdateController::class, 'storePortfolioItem'])->name('portfolio.items.store');
    Route::put('/portfolio/items/{portfolioItem}', [WebUpdateController::class, 'updatePortfolioItem'])->name('portfolio.items.update');
    Route::delete('/portfolio/items/{portfolioItem}', [WebUpdateController::class, 'deletePortfolioItem'])->name('portfolio.items.delete');
    Route::post('/portfolio/categories', [WebUpdateController::class, 'storePortfolioCategory'])->name('portfolio.categories.store');
    Route::put('/portfolio/categories/{portfolioCategory}', [WebUpdateController::class, 'updatePortfolioCategory'])->name('portfolio.categories.update');
    Route::delete('/portfolio/categories/{portfolioCategory}', [WebUpdateController::class, 'deletePortfolioCategory'])->name('portfolio.categories.delete');
    });

    Route::middleware(WebsiteContentPermissions::middlewareFor('blog'))->group(function () {
    Route::get('/blog', [WebUpdateController::class, 'blog'])->name('blog')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.website-update.index')
                ->push(__('Blog'), route('admin.website-update.blog'));
        });
    Route::post('/blog', [WebUpdateController::class, 'updateBlog'])->name('blog.update');
    Route::post('/blog/categories', [WebUpdateController::class, 'storeBlogCategory'])->name('blog.categories.store');
    Route::put('/blog/categories/{blogCategory}', [WebUpdateController::class, 'updateBlogCategory'])->name('blog.categories.update');
    Route::delete('/blog/categories/{blogCategory}', [WebUpdateController::class, 'deleteBlogCategory'])->name('blog.categories.delete');
    Route::post('/blog/images', [WebUpdateController::class, 'uploadBlogImage'])->name('blog.images.upload');
    Route::post('/blog/posts', [WebUpdateController::class, 'storeBlogPost'])->name('blog.posts.store');
    Route::put('/blog/posts/{blogPost}', [WebUpdateController::class, 'updateBlogPost'])->name('blog.posts.update');
    Route::delete('/blog/posts/{blogPost}', [WebUpdateController::class, 'deleteBlogPost'])->name('blog.posts.delete');
    });

    Route::middleware(WebsiteContentPermissions::middlewareFor('contact'))->group(function () {
    Route::get('/contact', [WebUpdateController::class, 'contact'])->name('contact')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.website-update.index')
                ->push(__('Contacts'), route('admin.website-update.contact'));
        });
    Route::post('/contact', [WebUpdateController::class, 'updateContact'])->name('contact.update');
    Route::delete('/contact/messages/{contactMessage}', [WebUpdateController::class, 'deleteContactMessage'])->name('contact.messages.delete');
    });

});
