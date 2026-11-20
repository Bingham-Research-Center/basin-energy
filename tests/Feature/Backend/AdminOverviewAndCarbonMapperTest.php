<?php

namespace Tests\Feature\Backend;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use App\Models\BroadcastAnnouncement;
use App\Models\CarbonMapperObservation;
use App\Services\UintahBasinCoverage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminOverviewAndCarbonMapperTest extends TestCase
{
    public function test_admin_overview_renders_user_summary_last_signin_and_ozone_chart(): void
    {
        $user = User::factory()->user()->create([
            'name' => 'Recent Research User',
            'active' => true,
            'last_login_at' => Carbon::parse('2026-08-27 22:24:00', 'UTC'),
            'email_verified_at' => null,
        ]);

        $role = Role::factory()->create([
            'name' => 'Research Stakeholder',
            'type' => User::TYPE_USER,
        ]);
        $user->assignRole($role);

        BroadcastAnnouncement::create([
            'title' => 'Admin overview announcement',
            'body' => 'Test announcement',
            'audience' => 'all_users',
            'role_ids' => [],
            'status' => BroadcastAnnouncement::PUBLISHED,
            'published_at' => now(),
        ]);

        User::factory()->admin()->create([
            'name' => 'Internal Admin Latest Login',
            'last_login_at' => now(),
        ]);

        $this->loginAsAdmin();

        // Rendering the dashboard must not request external ozone data:
        // the browser fetches the existing authenticated endpoints.
        Http::preventStrayRequests();

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Recently signed-in users')
            ->assertSee('Recent Research User')
            ->assertSee('Research Stakeholder')
            ->assertSee('27th Aug 16:24')
            ->assertSee('Last seen')
            ->assertSee('Uinta Basin weather')
            ->assertSee('Vernal Regional Airport')
            ->assertSee('adminWeatherObservedAt')
            ->assertSee('Source NWS')
            ->assertSee('adminWeatherSummary')
            ->assertDontSee('Local weather, Uinta Basin ozone observations and recent user activity.')
            ->assertDontSee('Station measurements represent conditions near Vernal, not a basin-wide average.')
            ->assertDontSee('Latest station report')
            ->assertSee(route('admin.weather.current'))
            ->assertDontSee('Registered users')
            ->assertDontSee('Published announcements')
            ->assertDontSee('Internal Admin Latest Login')
            ->assertSee('adminOzoneChart')
            ->assertSee('Last 24 records')
            ->assertSee(route('admin.data.realtime.logger.history.json', [
                'site' => '__STATION__',
            ]))
            ->assertSee(route('admin.data.carbon-mapper.utah'));
    }

    public function test_admin_without_user_list_permission_does_not_see_names_or_counts(): void
    {
        User::factory()->user()->create(['name' => 'Private Researcher']);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->assertFalse($admin->can('admin.access.user.list'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Private Researcher')
            ->assertSee('Uinta Basin weather')
            ->assertSee('requires the User Management viewing permission')
            ->assertSee('adminOzoneChart');
    }

    public function test_carbon_mapper_admin_json_endpoints_return_imported_observations(): void
    {
        $this->assertTrue(
            app(UintahBasinCoverage::class)->contains(40.0868333, -109.2851667)
        );

        CarbonMapperObservation::create([
            'observation_id' => 'admin-map-regression-001',
            'gas' => 'CH4',
            'latitude' => 40.0868333,
            'longitude' => -109.2851667,
            'observed_at' => '2026-11-01 12:00:00',
            'is_super_emitter' => true,
            'raw_payload' => ['properties' => ['cm:cmf_type' => 'plume']],
        ]);

        $this->loginAsAdmin();

        $this->getJson(route('admin.data.carbon-mapper.utah.boundary'))
            ->assertOk()->assertJsonPath('type', 'FeatureCollection');

        $this->getJson(route('admin.data.carbon-mapper.utah.json'))
            ->assertOk()->assertJsonFragment([
                'observation_id' => 'admin-map-regression-001',
            ]);

        $this->getJson(route('admin.data.carbon-mapper.utah.sources'))
            ->assertOk()->assertJsonStructure([]);

        $this->get(route('admin.data.carbon-mapper.utah'))
            ->assertOk()
            ->assertSee('id="map"', false)
            ->assertSee('data-json-url="'.route('admin.data.carbon-mapper.utah.json').'"', false)
            ->assertSee('research-data.js')
            ->assertDontSee('unpkg.com/leaflet');
    }

    public function test_regular_user_cannot_access_admin_dashboard_or_carbon_mapper_api(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('frontend.index'));

        $this->getJson(route('admin.data.carbon-mapper.utah.json'))
            ->assertRedirect(route('frontend.index'));
    }
}
