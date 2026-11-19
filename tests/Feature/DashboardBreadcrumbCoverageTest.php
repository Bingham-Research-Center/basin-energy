<?php

namespace Tests\Feature;

use App\Domains\Auth\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DashboardBreadcrumbCoverageTest extends TestCase
{
    private function assertTrail(TestResponse $response, array $labels): void
    {
        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame(
            1,
            preg_match(
                '/<ol class="breadcrumb border-0 m-0" aria-label="Breadcrumb">(.*?)<\/ol>/s',
                $html,
                $matches
            ),
            'Expected a breadcrumb in the shared dashboard header.'
        );

        $text = preg_replace('/\s+/u', ' ', html_entity_decode(
            strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'
        ));

        $position = 0;
        foreach ($labels as $label) {
            $found = mb_strpos($text, $label, $position, 'UTF-8');
            $this->assertNotFalse($found, 'Missing or out-of-order breadcrumb: '.$label.' in '.$text);
            $position = $found + mb_strlen($label, 'UTF-8');
        }

        $this->assertStringContainsString('aria-current="page"', $matches[0]);
    }

    public function test_admin_website_broadcast_account_and_existing_access_breadcrumbs(): void
    {
        $this->loginAsAdmin();

        $cases = [
            ['admin.dashboard', ['Home']],
            ['admin.auth.user.index', ['Home', 'User Management']],
            ['admin.auth.role.index', ['Home', 'Role Management']],
            ['admin.website-update.index', ['Home', 'Website Updates']],
            ['admin.website-update.home', ['Home', 'Website Updates', 'Home']],
            ['admin.website-update.about', ['Home', 'Website Updates', 'About']],
            ['admin.website-update.service', ['Home', 'Website Updates', 'Service']],
            ['admin.website-update.portfolio', ['Home', 'Website Updates', 'Portfolio']],
            ['admin.website-update.blog', ['Home', 'Website Updates', 'Blog']],
            ['admin.website-update.contact', ['Home', 'Website Updates', 'Contacts']],
            ['admin.broadcast.newsletters.index', ['Home', 'Broadcast', 'Newsletters']],
            ['admin.broadcast.announcements.index', ['Home', 'Broadcast', 'Announcements']],
            ['admin.account.index', ['Home', 'Account Info']],
            ['admin.sensors.dashboard', ['Home', 'Sensor Dashboard']],
        ];

        foreach ($cases as [$routeName, $labels]) {
            $this->assertTrail(
                $this->get(route($routeName)),
                $labels
            );
        }
    }

    public function test_broadcast_create_pages_have_parent_navigation(): void
    {
        $this->loginAsAdmin();

        $this->assertTrail(
            $this->get(route('admin.broadcast.newsletters.create')),
            ['Home', 'Broadcast', 'Newsletters', 'Create Newsletter']
        );

        $this->assertTrail(
            $this->get(route('admin.broadcast.announcements.create')),
            ['Home', 'Broadcast', 'Announcements', 'Create Announcement']
        );
    }

    public function test_user_data_and_account_pages_show_breadcrumbs(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $cases = [
            ['frontend.user.dashboard', ['Home', 'Dashboard']],
            ['frontend.user.account', ['Home', 'My Account']],
            ['frontend.user.data.emission-trends', ['Home', 'Dashboard', 'Data', 'Emission Trends']],
            ['frontend.user.data.produced-water', ['Home', 'Dashboard', 'Data', 'Produced Water']],
            ['frontend.user.data.subsurface-natural-gas-leaks',
                ['Home', 'Dashboard', 'Data', 'Subsurface Natural Gas Leaks']],
            ['frontend.user.data.oil-well-pad-emissions',
                ['Home', 'Dashboard', 'Data', 'Oil Well Pad Emissions']],
            ['frontend.user.data.realtime.ozone',
                ['Home', 'Dashboard', 'Data', 'Real-Time Data', 'Uinta Ozone']],
            ['frontend.user.carbon-mapper.utah',
                ['Home', 'Dashboard', 'Carbon Mapper', 'Utah Super-Emitters']],
            ['frontend.broadcast.announcements', ['Home', 'Dashboard', 'My Announcements']],
        ];

        foreach ($cases as [$routeName, $labels]) {
            $this->assertTrail($this->get(route($routeName)), $labels);
        }

        $this->assertTrail(
            $this->get(route('frontend.user.data.realtime.logger', ['site' => 'horsepool'])),
            ['Home', 'Dashboard', 'Data', 'Real-Time Data', 'Horsepool']
        );

        $this->assertTrail(
            $this->get(route('frontend.user.data.realtime.other', ['site' => 'dinosaur-nm'])),
            ['Home', 'Dashboard', 'Data', 'Real-Time Data', 'Dinosaur NM']
        );
    }
}
