<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AboutPageContent;

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
        return view('frontend.pages.service');
    }

    public function pricing()
    {
        return view('frontend.pages.pricing');
    }

    public function portfolio()
    {
        return view('frontend.pages.portfolio');
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