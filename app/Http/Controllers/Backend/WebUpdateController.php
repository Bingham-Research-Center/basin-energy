<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AboutPageContent;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\ServicesPageContent;
use App\Models\PortfolioPageContent;
use App\Models\PortfolioItem;
use App\Models\PortfolioCategory;
use App\Models\BlogPageContent;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\ContactPageContent;
use App\Models\ContactMessage;

class WebUpdateController extends Controller
{
    public function index()
    {
        return view('backend.webupdate.index');
    }

    public function home()
    {
        return view('backend.webupdate.home');
    }

    public function updateHome(Request $request)
    {
        return back()->withFlashSuccess('Home page updated successfully.');
    }

    public function about()
    {
        $hero = AboutPageContent::getSectionItem('hero', 'title');
        $intro = AboutPageContent::getSectionItem('intro', 'heading');
        $aboutSection = AboutPageContent::getSectionItem('about_section', 'main');
        $teamHeader = AboutPageContent::getSectionItem('team_header', 'main');

        $teamMembers = AboutPageContent::where('section', 'team_members')
            ->orderBy('sort_order')
            ->get();

        return view('backend.webupdate.about', compact(
            'hero',
            'intro',
            'aboutSection',
            'teamHeader',
            'teamMembers'
        ));
    }

    public function updateAbout(Request $request)
    {
        $request->validate([
            'hero_title' => ['nullable', 'string', 'max:255'],
            'intro_heading' => ['nullable', 'string'],
            'about_heading' => ['nullable', 'string'],
            'about_description' => ['nullable', 'string'],
            'about_button_text' => ['nullable', 'string', 'max:255'],
            'about_button_link' => ['nullable', 'string', 'max:255'],
            'about_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'team_title' => ['nullable', 'string', 'max:255'],
            'team_description' => ['nullable', 'string'],
        ]);

        AboutPageContent::updateOrCreate(
            ['section' => 'hero', 'item_key' => 'title'],
            ['title' => $request->hero_title]
        );

        AboutPageContent::updateOrCreate(
            ['section' => 'intro', 'item_key' => 'heading'],
            ['title' => $request->intro_heading]
        );

        $aboutData = [
            'title' => $request->about_heading,
            'description' => $request->about_description,
            'button_text' => $request->about_button_text,
            'button_link' => $request->about_button_link,
        ];

        if ($request->hasFile('about_image')) {
            $aboutData['image'] = $request->file('about_image')->store('website/about', 'public');
        }

        AboutPageContent::updateOrCreate(
            ['section' => 'about_section', 'item_key' => 'main'],
            $aboutData
        );

        AboutPageContent::updateOrCreate(
            ['section' => 'team_header', 'item_key' => 'main'],
            [
                'title' => $request->team_title,
                'description' => $request->team_description,
            ]
        );

        return back()->withFlashSuccess('About page updated successfully.');
    }

    public function storeTeamMember(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'facebook_url' => ['nullable', 'url'],
            'twitter_url' => ['nullable', 'url'],
            'instagram_url' => ['nullable', 'url'],
            'linkedin_url' => ['nullable', 'url'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = [
            'section' => 'team_members',
            'item_key' => 'team_member_' . Str::uuid(),
            'title' => $request->name,
            'subtitle' => $request->designation,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
            'value' => [
                'facebook' => $request->facebook_url,
                'twitter' => $request->twitter_url,
                'instagram' => $request->instagram_url,
                'linkedin' => $request->linkedin_url,
            ],
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('website/about/team', 'public');
        }

        AboutPageContent::create($data);

        return back()->withFlashSuccess('Team member added successfully.');
    }

    public function updateTeamMember(Request $request, AboutPageContent $teamMember)
    {
        abort_unless($teamMember->section === 'team_members', 404);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'facebook_url' => ['nullable', 'url'],
            'twitter_url' => ['nullable', 'url'],
            'instagram_url' => ['nullable', 'url'],
            'linkedin_url' => ['nullable', 'url'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = [
            'title' => $request->name,
            'subtitle' => $request->designation,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
            'value' => [
                'facebook' => $request->facebook_url,
                'twitter' => $request->twitter_url,
                'instagram' => $request->instagram_url,
                'linkedin' => $request->linkedin_url,
            ],
        ];

        if ($request->hasFile('image')) {
            if ($teamMember->image) {
                Storage::disk('public')->delete($teamMember->image);
            }

            $data['image'] = $request->file('image')->store('website/about/team', 'public');
        }

        $teamMember->update($data);

        return back()->withFlashSuccess('Team member updated successfully.');
    }

    public function deleteTeamMember(AboutPageContent $teamMember)
    {
        abort_unless($teamMember->section === 'team_members', 404);

        if ($teamMember->image) {
            Storage::disk('public')->delete($teamMember->image);
        }

        $teamMember->delete();

        return back()->withFlashSuccess('Team member deleted successfully.');
    }

    public function service()
    {
        $hero = ServicesPageContent::getSectionItem('hero', 'title');

        $serviceItems = ServicesPageContent::where('section', 'service_items')
            ->orderBy('sort_order')
            ->get();

        return view('backend.webupdate.service', compact(
            'hero',
            'serviceItems'
        ));
    }

    public function updateService(Request $request)
    {
        $request->validate([
            'hero_title' => ['nullable', 'string', 'max:255'],
        ]);

        ServicesPageContent::updateOrCreate(
            ['section' => 'hero', 'item_key' => 'title'],
            ['title' => $request->hero_title]
        );

        return back()->withFlashSuccess('Service page updated successfully.');
    }

    public function storeServiceItem(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ServicesPageContent::create([
            'section' => 'service_items',
            'item_key' => 'service_item_' . Str::uuid(),
            'title' => $request->title,
            'description' => $request->description,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->withFlashSuccess('Service item added successfully.');
    }

    public function updateServiceItem(Request $request, ServicesPageContent $serviceItem)
    {
        abort_unless($serviceItem->section === 'service_items', 404);

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $serviceItem->update([
            'title' => $request->title,
            'description' => $request->description,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->withFlashSuccess('Service item updated successfully.');
    }

    public function deleteServiceItem(ServicesPageContent $serviceItem)
    {
        abort_unless($serviceItem->section === 'service_items', 404);

        $serviceItem->delete();

        return back()->withFlashSuccess('Service item deleted successfully.');
    }

    public function portfolio()
    {
        $hero = PortfolioPageContent::getSectionItem('hero', 'title');
        $portfolioHeader = PortfolioPageContent::getSectionItem('portfolio_header', 'main');
        $cta = PortfolioPageContent::getSectionItem('cta', 'main');

        $portfolioCategories = PortfolioCategory::orderBy('sort_order')->get();
        $portfolioItems = PortfolioItem::with('categories')->orderBy('sort_order')->get();

        return view('backend.webupdate.portfolio', compact(
            'hero',
            'portfolioHeader',
            'cta',
            'portfolioCategories',
            'portfolioItems'
        ));
    }

    public function storePortfolioCategory(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:portfolio_categories,slug'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        PortfolioCategory::create([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->withFlashSuccess('Portfolio category added successfully.');
    }

    public function updatePortfolioCategory(Request $request, PortfolioCategory $portfolioCategory)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:portfolio_categories,slug,' . $portfolioCategory->id],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $portfolioCategory->update([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->withFlashSuccess('Portfolio category updated successfully.');
    }

    public function deletePortfolioCategory(PortfolioCategory $portfolioCategory)
    {
        $portfolioCategory->delete();

        return back()->withFlashSuccess('Portfolio category deleted successfully.');
    }

    public function updatePortfolio(Request $request)
    {
        $request->validate([
            'hero_title' => ['nullable', 'string', 'max:255'],
            'portfolio_title' => ['nullable', 'string', 'max:255'],
            'portfolio_description' => ['nullable', 'string'],
            'cta_subtitle' => ['nullable', 'string', 'max:255'],
            'cta_title' => ['nullable', 'string'],
            'cta_button_text' => ['nullable', 'string', 'max:255'],
            'cta_button_link' => ['nullable', 'string', 'max:255'],
            'cta_secondary_button_text' => ['nullable', 'string', 'max:255'],
            'cta_secondary_button_link' => ['nullable', 'string', 'max:255'],
        ]);

        PortfolioPageContent::updateOrCreate(
            ['section' => 'hero', 'item_key' => 'title'],
            ['title' => $request->hero_title]
        );

        PortfolioPageContent::updateOrCreate(
            ['section' => 'portfolio_header', 'item_key' => 'main'],
            [
                'title' => $request->portfolio_title,
                'description' => $request->portfolio_description,
            ]
        );

        PortfolioPageContent::updateOrCreate(
            ['section' => 'cta', 'item_key' => 'main'],
            [
                'subtitle' => $request->cta_subtitle,
                'title' => $request->cta_title,
                'button_text' => $request->cta_button_text,
                'button_link' => $request->cta_button_link,
                'value' => [
                    'secondary_button_text' => $request->cta_secondary_button_text,
                    'secondary_button_link' => $request->cta_secondary_button_link,
                ],
            ]
        );

        return back()->withFlashSuccess('Portfolio page updated successfully.');
    }

    public function storePortfolioItem(Request $request)
    {
        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:portfolio_categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('website/portfolio', 'public');
        }

        $item = PortfolioItem::create($data);

        $item->categories()->sync($request->input('category_ids', []));

        return back()->withFlashSuccess('Portfolio item added successfully.');
    }

    public function updatePortfolioItem(Request $request, PortfolioItem $portfolioItem)
    {
        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:portfolio_categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('image')) {
            if ($portfolioItem->image) {
                \Storage::disk('public')->delete($portfolioItem->image);
            }

            $data['image'] = $request->file('image')->store('website/portfolio', 'public');
        }

        $portfolioItem->update($data);

        $portfolioItem->categories()->sync($request->input('category_ids', []));

        return back()->withFlashSuccess('Portfolio item updated successfully.');
    }


    public function deletePortfolioItem(PortfolioItem $portfolioItem)
    {
        if ($portfolioItem->image) {
            Storage::disk('public')->delete($portfolioItem->image);
        }

        $portfolioItem->delete();

        return back()->withFlashSuccess('Portfolio item deleted successfully.');
    }

    public function blog()
    {
        $hero = BlogPageContent::getSectionItem('hero', 'title');
        $sidebarAuthor = BlogPageContent::getSectionItem('sidebar_author', 'main');

        $blogCategories = BlogCategory::orderBy('sort_order')->get();
        $blogPosts = BlogPost::with('categories')->orderByDesc('published_at')->get();

        return view('backend.webupdate.blog', compact(
            'hero',
            'sidebarAuthor',
            'blogCategories',
            'blogPosts'
        ));
    }
    public function updateBlog(Request $request)
    {
        $request->validate([
            'hero_title' => ['nullable', 'string', 'max:255'],
            'sidebar_author_name' => ['nullable', 'string', 'max:255'],
            'sidebar_author_description' => ['nullable', 'string'],
            'sidebar_author_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        BlogPageContent::updateOrCreate(
            ['section' => 'hero', 'item_key' => 'title'],
            ['title' => $request->hero_title]
        );

        $authorData = [
            'title' => $request->sidebar_author_name,
            'description' => $request->sidebar_author_description,
        ];

        $existingAuthor = BlogPageContent::getSectionItem('sidebar_author', 'main');

        if ($request->hasFile('sidebar_author_image')) {
            if ($existingAuthor && $existingAuthor->image) {
                Storage::disk('public')->delete($existingAuthor->image);
            }

            $authorData['image'] = $request->file('sidebar_author_image')->store('website/blog', 'public');
        }

        BlogPageContent::updateOrCreate(
            ['section' => 'sidebar_author', 'item_key' => 'main'],
            $authorData
        );

        return back()->withFlashSuccess('Blog page updated successfully.');
    }

    public function storeBlogCategory(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:blog_categories,slug'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        BlogCategory::create([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->withFlashSuccess('Blog category added successfully.');
    }

    public function updateBlogCategory(Request $request, BlogCategory $blogCategory)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:blog_categories,slug,' . $blogCategory->id],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $blogCategory->update([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->withFlashSuccess('Blog category updated successfully.');
    }

    public function deleteBlogCategory(BlogCategory $blogCategory)
    {
        $blogCategory->delete();

        return back()->withFlashSuccess('Blog category deleted successfully.');
    }

    public function storeBlogPost(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:blog_posts,slug'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:blog_categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = [
            'title' => $request->title,
            'slug' => $request->slug ?: Str::slug($request->title),
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'author_name' => $request->author_name,
            'published_at' => $request->published_at,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('website/blog/posts', 'public');
        }

        $post = BlogPost::create($data);
        $post->categories()->sync($request->input('category_ids', []));

        return back()->withFlashSuccess('Blog post added successfully.');
    }

    public function updateBlogPost(Request $request, BlogPost $blogPost)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:blog_posts,slug,' . $blogPost->id],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:blog_categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = [
            'title' => $request->title,
            'slug' => $request->slug ?: Str::slug($request->title),
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'author_name' => $request->author_name,
            'published_at' => $request->published_at,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('featured_image')) {
            if ($blogPost->featured_image) {
                Storage::disk('public')->delete($blogPost->featured_image);
            }

            $data['featured_image'] = $request->file('featured_image')->store('website/blog/posts', 'public');
        }

        $blogPost->update($data);
        $blogPost->categories()->sync($request->input('category_ids', []));

        return back()->withFlashSuccess('Blog post updated successfully.');
    }

    public function deleteBlogPost(BlogPost $blogPost)
    {
        if ($blogPost->featured_image) {
            Storage::disk('public')->delete($blogPost->featured_image);
        }

        $blogPost->delete();

        return back()->withFlashSuccess('Blog post deleted successfully.');
    }

    public function contact()
    {
        $hero = ContactPageContent::getSectionItem('hero', 'title');
        $contactInfo = ContactPageContent::getSectionItem('contact_info', 'main');
        $socialLinks = ContactPageContent::getSectionItem('social_links', 'main');
        $contactForm = ContactPageContent::getSectionItem('contact_form', 'main');
        $map = ContactPageContent::getSectionItem('map', 'main');

        $contactMessages = ContactMessage::latest()->get();
        
        \App\Models\ContactMessage::where('is_read', false)->update(['is_read' => true]);

        return view('backend.webupdate.contact', compact(
            'hero',
            'contactInfo',
            'socialLinks',
            'contactForm',
            'map',
            'contactMessages'
        ));
    }

    public function updateContact(Request $request)
    {
        $request->validate([
            'hero_title' => ['nullable', 'string', 'max:255'],

            'contact_lead' => ['nullable', 'string'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'contact_description' => ['nullable', 'string'],

            'facebook_url' => ['nullable', 'url'],
            'twitter_url' => ['nullable', 'url'],
            'linkedin_url' => ['nullable', 'url'],

            'form_title' => ['nullable', 'string', 'max:255'],
            'form_description' => ['nullable', 'string'],

            'map_embed_url' => ['nullable', 'string'],
        ]);

        ContactPageContent::updateOrCreate(
            ['section' => 'hero', 'item_key' => 'title'],
            ['title' => $request->hero_title]
        );

        ContactPageContent::updateOrCreate(
            ['section' => 'contact_info', 'item_key' => 'main'],
            [
                'subtitle' => $request->contact_lead,
                'title' => $request->contact_phone,
                'description' => $request->contact_description,
            ]
        );

        ContactPageContent::updateOrCreate(
            ['section' => 'social_links', 'item_key' => 'main'],
            [
                'value' => [
                    'facebook' => $request->facebook_url,
                    'twitter' => $request->twitter_url,
                    'linkedin' => $request->linkedin_url,
                ],
            ]
        );

        ContactPageContent::updateOrCreate(
            ['section' => 'contact_form', 'item_key' => 'main'],
            [
                'title' => $request->form_title,
                'description' => $request->form_description,
            ]
        );

        ContactPageContent::updateOrCreate(
            ['section' => 'map', 'item_key' => 'main'],
            [
                'value' => [
                    'embed_url' => $request->map_embed_url,
                ],
            ]
        );

        return back()->withFlashSuccess('Contact page updated successfully.');
    }

    public function deleteContactMessage(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return back()->withFlashSuccess('Contact message deleted successfully.');
    }
}