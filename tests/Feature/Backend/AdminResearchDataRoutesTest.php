<?php

namespace Tests\Feature\Backend;

use App\Domains\Auth\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminResearchDataRoutesTest extends TestCase
{
    public function test_admin_has_both_research_dropdowns_and_all_implemented_pages(): void
    {
        $this->loginAsAdmin();

        $pages = [
            'admin.data.emission-trends',
            'admin.data.produced-water',
            'admin.data.subsurface-natural-gas-leaks',
            'admin.data.oil-well-pad-emissions',
            'admin.data.carbon-mapper.utah',
            'admin.data.realtime.ozone',
        ];

        foreach ($pages as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee('c-sidebar-nav-dropdown')
                ->assertSee('Real-Time Data')
                ->assertSee('admin/data/', false)
                ->assertSee('brc-notification-trigger');
        }

        $this->get(route('admin.data.realtime.logger', ['site' => 'horsepool']))
            ->assertOk()->assertSee('Horsepool');
        $this->get(route('admin.data.realtime.other', ['site' => 'dinosaur-nm']))
            ->assertOk()->assertSee('Dinosaur NM');
    }

    public function test_admin_pages_use_the_admin_json_and_chart_endpoints(): void
    {
        $this->loginAsAdmin();

        $this->get(route('admin.data.emission-trends'))
            ->assertOk()
            ->assertSee(route('admin.data.emission-trends.json'))
            ->assertSee(route('admin.data.ai-summary.emission-trends'));

        $this->get(route('admin.data.produced-water'))
            ->assertOk()
            ->assertSee(route('admin.data.produced-water.flux.json'))
            ->assertSee(route('admin.data.produced-water.flux.columns'));

        $this->get(route('admin.data.subsurface-natural-gas-leaks'))
            ->assertOk()
            ->assertSee(str_replace('/', '\\/', route('admin.data.subsurface-natural-gas-leaks.overview.json')), false);

        $this->get(route('admin.data.oil-well-pad-emissions'))
            ->assertOk()
            ->assertSee(route('admin.data.oil-well-pad-emissions.overview.json'));

        $this->get(route('admin.data.carbon-mapper.utah'))
            ->assertOk()
            ->assertSee(route('admin.data.carbon-mapper.utah.json'))
            ->assertSee(route('admin.data.carbon-mapper.utah.boundary'))
            ->assertSee(route('admin.data.carbon-mapper.utah.sources'));

        $this->get(route('admin.data.realtime.ozone'))
            ->assertOk()
            ->assertSee(route('admin.data.realtime.ozone.json'))
            ->assertSee(route('admin.data.realtime.logger.history.json', ['site' => '__STATION__']));

        $this->get(route('admin.data.realtime.logger', ['site' => 'horsepool']))
            ->assertOk()
            ->assertSee(route('admin.data.realtime.logger.json', ['site' => 'horsepool']));

        // This endpoint is local/DB-backed, so no external station is called.
        $this->getJson(route('admin.data.emission-trends.json'))->assertOk();
    }

    public function test_research_charts_load_the_single_shared_bundle_after_page_dependencies(): void
    {
        $this->loginAsAdmin();

        $researchPages = [
            'admin.data.emission-trends',
            'admin.data.produced-water',
            'admin.data.subsurface-natural-gas-leaks',
            'admin.data.oil-well-pad-emissions',
            'admin.data.carbon-mapper.utah',
        ];

        foreach ($researchPages as $routeName) {
            $html = $this->get(route($routeName))->assertOk()->getContent();
            $this->assertSame(
                1,
                substr_count($html, '/js/research-data.js'),
                'Expected exactly one versioned research bundle on '.$routeName
            );
        }

        // Inline URLs and external Leaflet dependencies are declared
        // before the shared chart module executes.
        $leaks = $this->get(route('admin.data.subsurface-natural-gas-leaks'))
            ->assertOk()->getContent();
        $this->assertLessThan(
            strpos($leaks, '/js/research-data.js'),
            strpos($leaks, 'window.subsurfaceLeakUrls')
        );

        $mapper = $this->get(route('admin.data.carbon-mapper.utah'))
            ->assertOk()->getContent();

        $this->assertStringContainsString('data-json-url="'.route('admin.data.carbon-mapper.utah.json').'"', $mapper);
        $this->assertStringContainsString('data-boundary-url="'.route('admin.data.carbon-mapper.utah.boundary').'"', $mapper);
        $this->assertStringContainsString('data-sources-url="'.route('admin.data.carbon-mapper.utah.sources').'"', $mapper);
        $this->assertStringNotContainsString('unpkg.com/leaflet', $mapper);
        $this->assertLessThan(
            strpos($mapper, '/js/research-data.js'),
            strpos($mapper, 'data-json-url=')
        );

        // The existing independent real-time dashboards should not
        // download the extra chart bundle.
        $this->get(route('admin.data.realtime.ozone'))
            ->assertOk()->assertDontSee('/js/research-data.js', false);
        $this->get(route('admin.dashboard'))
            ->assertOk()->assertDontSee('/js/research-data.js', false);
    }

    public function test_regular_user_charts_use_the_same_bundle_once(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $pages = [
            'frontend.user.data.emission-trends',
            'frontend.user.data.produced-water',
            'frontend.user.data.subsurface-natural-gas-leaks',
            'frontend.user.data.oil-well-pad-emissions',
            'frontend.user.carbon-mapper.utah',
        ];

        foreach ($pages as $routeName) {
            $html = $this->get(route($routeName))->assertOk()->getContent();
            $this->assertSame(
                1,
                substr_count($html, '/js/research-data.js'),
                'Expected one research chart bundle on '.$routeName
            );
        }

        $this->get(route('frontend.user.data.realtime.ozone'))
            ->assertOk()->assertDontSee('/js/research-data.js', false);
    }

    public function test_admin_and_regular_user_data_endpoints_remain_isolated(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $adminRouteNames = [
            'admin.data.emission-trends',
            'admin.data.emission-trends.json',
            'admin.data.produced-water.flux.json',
            'admin.data.subsurface-natural-gas-leaks.overview.json',
            'admin.data.oil-well-pad-emissions.overview.json',
            'admin.data.carbon-mapper.utah',
            'admin.data.carbon-mapper.utah.json',
            'admin.data.realtime.ozone',
            'admin.data.realtime.ozone.json',
            'admin.data.realtime.logger.json',
            'admin.data.ai-summary.emission-trends',
        ];
        foreach ($adminRouteNames as $name) {
            $middleware = Route::getRoutes()->getByName($name)->gatherMiddleware();
            $this->assertContains('admin', $middleware, $name);
            $this->assertNotContains('is_user', $middleware, $name);
        }

        // Original user-only paths are deliberately unchanged.
        $this->get('/data/produced-water')->assertRedirect(route('admin.dashboard'));
        $this->getJson('/data/emission-trends/json')->assertForbidden();

        $this->actingAs(User::factory()->user()->create());

        $this->get('/admin/data/produced-water')
            ->assertRedirect(route('frontend.index'));
        $this->getJson('/admin/data/emission-trends/json')
            ->assertRedirect(route('frontend.index'));
        $this->getJson(route('frontend.user.data.emission-trends.json'))
            ->assertOk();

        $this->get(route('frontend.user.data.emission-trends'))
            ->assertOk()
            ->assertSee(route('frontend.user.data.emission-trends.json'));
    }
}
