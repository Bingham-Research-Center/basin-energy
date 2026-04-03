<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AboutPageContent;
use App\Models\ServicesPageContent;
use App\Models\PortfolioPageContent;
use App\Models\PortfolioItem;

class PageController extends Controller
{
    public function about()
    {
        $hero = AboutPageContent::getSectionItem('hero', 'title');
        $intro = AboutPageContent::getSectionItem('intro', 'heading');
        $aboutSection = AboutPageContent::getSectionItem('about_section', 'main');
        $teamHeader = AboutPageContent::getSectionItem('team_header', 'main');

        $teamMembers = AboutPageContent::where('section', 'team_members')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.pages.about', compact(
            'hero',
            'intro',
            'aboutSection',
            'teamHeader',
            'teamMembers'
        ));
    }

    public function service()
    {
        $hero = ServicesPageContent::getSectionItem('hero', 'title');

        $serviceItems = ServicesPageContent::where('section', 'service_items')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.pages.service', compact(
            'hero',
            'serviceItems'
        ));
    }

    public function portfolio()
    {
        $hero = PortfolioPageContent::getSectionItem('hero', 'title');
        $portfolioHeader = PortfolioPageContent::getSectionItem('portfolio_header', 'main');
        $cta = PortfolioPageContent::getSectionItem('cta', 'main');

        $portfolioItems = PortfolioItem::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.pages.portfolio', compact(
            'hero',
            'portfolioHeader',
            'cta',
            'portfolioItems'
        ));
    }
    
    public function blog()
    {
        return view('frontend.pages.blog-grid');
    }

    public function blogSingle()
    {
        return view('frontend.pages.blog-single');
    }

    public function contact()
    {
        return view('frontend.pages.contact');
    }
}