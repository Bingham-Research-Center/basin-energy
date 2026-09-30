<?php

namespace App\Http\Controllers\Frontend;

/**
 * Class HomeController.
 */
class HomeController
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $hero = \App\Models\HomePageContent::getSectionItem('hero', 'main');
        $weatherButton = \App\Models\HomePageContent::getSectionItem('hero', 'weather');
        $platform = \App\Models\HomePageContent::getSectionItem('platform', 'main');
        $platformButton = \App\Models\HomePageContent::getSectionItem('platform', 'button');

        return view('frontend.pages.index', compact(
            'hero',
            'weatherButton',
            'platform',
            'platformButton'
        ));
    }
}