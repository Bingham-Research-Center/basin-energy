<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AboutPageContent;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        // validate fields
        // upload image if present
        // save to DB/settings
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
        return view('backend.webupdate.service');
    }

    public function updateService(Request $request)
    {
        return back()->withFlashSuccess('Service page updated successfully.');
    }

    public function portfolio()
    {
        return view('backend.webupdate.portfolio');
    }

    public function updatePortfolio(Request $request)
    {
        return back()->withFlashSuccess('Portfolio page updated successfully.');
    }

    public function blog()
    {
        return view('backend.webupdate.blog');
    }

    public function updateBlog(Request $request)
    {
        return back()->withFlashSuccess('Blog page updated successfully.');
    }

    public function contact()
    {
        return view('backend.webupdate.contact');
    }

    public function updateContact(Request $request)
    {
        return back()->withFlashSuccess('Contact page updated successfully.');
    }
}