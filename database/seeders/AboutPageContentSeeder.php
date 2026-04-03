<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AboutPageContent;

class AboutPageContentSeeder extends Seeder
{
    public function run(): void
    {
        AboutPageContent::updateOrCreate(
            ['section' => 'hero', 'item_key' => 'title'],
            ['title' => 'About Us', 'sort_order' => 1]
        );

        AboutPageContent::updateOrCreate(
            ['section' => 'intro', 'item_key' => 'heading'],
            ['title' => 'We provide best solution to client with their business problem', 'sort_order' => 1]
        );

        AboutPageContent::updateOrCreate(
            ['section' => 'about_section', 'item_key' => 'main'],
            [
                'title' => 'Forget about design limits! Build and customize your portfolio',
                'description' => 'We provide consulting services in the area of IFRS and management reporting, helping companies to reach their highest level. We optimize business processes, making them easier.',
                'button_text' => 'Get started',
                'button_link' => '#',
                'sort_order' => 1,
            ]
        );

        AboutPageContent::updateOrCreate(
            ['section' => 'team_header', 'item_key' => 'main'],
            [
                'title' => 'Team',
                'description' => 'We provide a wide range of creative services.',
                'sort_order' => 1,
            ]
        );
    }
}