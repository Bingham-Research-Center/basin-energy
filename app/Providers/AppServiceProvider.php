<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\BlogCategory;

/**
 * Class AppServiceProvider.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('frontend.website.header', function ($view) {
            $headerBlogCategories = BlogCategory::where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            $view->with('headerBlogCategories', $headerBlogCategories);
        });
    }
}
