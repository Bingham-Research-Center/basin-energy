<?php

namespace Tests\Feature;

use App\Domains\Auth\Models\User;
use Tests\TestCase;

class DashboardVisualizationWidthTest extends TestCase
{
    public function test_admin_research_and_realtime_pages_use_full_width_layout(): void
    {
        $this->loginAsAdmin();

        $pages = [
            route('admin.data.emission-trends'),
            route('admin.data.produced-water'),
            route('admin.data.carbon-mapper.utah'),
            route('admin.data.realtime.ozone'),
            route('admin.data.realtime.logger', ['site' => 'horsepool']),
            route('admin.data.realtime.other', ['site' => 'dinosaur-nm']),
        ];

        foreach ($pages as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('dashboard-page-pro dashboard-fill-page', false);
        }
    }

    public function test_user_research_and_realtime_pages_use_the_same_layout(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $pages = [
            route('frontend.user.data.emission-trends'),
            route('frontend.user.data.produced-water'),
            route('frontend.user.carbon-mapper.utah'),
            route('frontend.user.data.realtime.ozone'),
            route('frontend.user.data.realtime.logger', ['site' => 'horsepool']),
            route('frontend.user.data.realtime.other', ['site' => 'dinosaur-nm']),
        ];

        foreach ($pages as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('dashboard-page-pro dashboard-fill-page', false);
        }
    }

    public function test_other_data_dashboards_are_already_fluid_and_user_home_stays_bounded(): void
    {
        $this->loginAsAdmin();

        $this->get(route('admin.data.subsurface-natural-gas-leaks'))
            ->assertOk()
            ->assertSee('id="subsurfaceLeakDashboard" class="container-fluid"', false);

        $this->get(route('admin.data.oil-well-pad-emissions'))
            ->assertOk()
            ->assertSee('id="oilWellPadDashboard"', false)
            ->assertSee('class="container-fluid"', false);

        $this->actingAs(User::factory()->user()->create());

        $home = $this->get(route('frontend.user.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="dashboard-page dashboard-page-pro"', $home);
        $this->assertStringNotContainsString('dashboard-page-pro dashboard-fill-page', $home);
    }
}
