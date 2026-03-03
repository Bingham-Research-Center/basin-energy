<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function about()
    {
        return view('frontend.pages.about');
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