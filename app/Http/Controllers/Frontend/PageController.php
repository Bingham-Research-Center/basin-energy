<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AboutPageContent;
use App\Models\ServicesPageContent;
use App\Models\PortfolioPageContent;
use App\Models\PortfolioCategory;
use App\Models\PortfolioItem;
use App\Models\BlogPageContent;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\ContactPageContent;
use App\Models\ContactMessage;

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

        $portfolioCategories = PortfolioCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $portfolioItems = PortfolioItem::with('categories')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.pages.portfolio', compact(
            'hero',
            'portfolioHeader',
            'cta',
            'portfolioCategories',
            'portfolioItems'
        ));
    }
    
    public function blog(Request $request)
    {
        $hero = BlogPageContent::getSectionItem('hero', 'title');

        $postsQuery = BlogPost::with('categories')
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at');

        if ($request->filled('category')) {
            $categorySlug = $request->category;

            $postsQuery->whereHas('categories', function ($query) use ($categorySlug) {
                $query->where('slug', $categorySlug);
            });
        }

        $posts = $postsQuery->paginate(6)->withQueryString();

        return view('frontend.pages.blog-grid', compact(
            'hero',
            'posts'
        ));
    }

    public function blogSingle($slug)
    {
        $post = BlogPost::with('categories')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $latestPosts = BlogPost::where('is_active', true)
            ->where('id', '!=', $post->id)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        $categories = BlogCategory::withCount(['posts' => function ($query) {
                $query->where('is_active', true);
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $sidebarAuthor = BlogPageContent::getSectionItem('sidebar_author', 'main');

        $previousPost = BlogPost::where('is_active', true)
            ->where('published_at', '<', $post->published_at)
            ->orderByDesc('published_at')
            ->first();

        $nextPost = BlogPost::where('is_active', true)
            ->where('published_at', '>', $post->published_at)
            ->orderBy('published_at')
            ->first();

        return view('frontend.pages.blog-single', compact(
            'post',
            'latestPosts',
            'categories',
            'sidebarAuthor',
            'previousPost',
            'nextPost'
        ));
    }

    public function contact()
    {
        $hero = ContactPageContent::getSectionItem('hero', 'title');
        $contactInfo = ContactPageContent::getSectionItem('contact_info', 'main');
        $socialLinks = ContactPageContent::getSectionItem('social_links', 'main');
        $contactForm = ContactPageContent::getSectionItem('contact_form', 'main');
        $map = ContactPageContent::getSectionItem('map', 'main');

        return view('frontend.pages.contact', compact(
            'hero',
            'contactInfo',
            'socialLinks',
            'contactForm',
            'map'
        ));
    }

    public function submitContact(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string'],
        ];

        if (config('boilerplate.access.captcha.contact')) {
            $rules['g-recaptcha-response'] = ['required'];
        }

        $request->validate($rules);

        $contactMessage = ContactMessage::create([
            'name' => $request->name,
            'email' => $request->email,
            'message' => $request->message,
            'is_read' => false,
        ]);
        broadcast(new \App\Events\ContactMessageSubmitted($contactMessage));

        return back()->withFlashSuccess('Your message was sent successfully.');
    }
}