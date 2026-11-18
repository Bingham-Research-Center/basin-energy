<?php

namespace Tests\Feature\Frontend;

use App\Domains\Auth\Models\User;
use Tests\TestCase;

class UserOnlyFrontendRoutesTest extends TestCase
{
    public function test_all_user_data_and_carbon_mapper_routes_require_the_user_type(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            $name = $route->getName() ?? '';

            if (str_starts_with($name, 'frontend.user.data.')
                || str_starts_with($name, 'frontend.user.carbon-mapper.')) {
                $this->assertContains('is_user', $route->gatherMiddleware(), $name);
            }
        }

        $this->assertContains(
            'is_user',
            app('router')->getRoutes()
                ->getByName('frontend.user.profile.update')
                ->gatherMiddleware()
        );
    }

    public function test_admin_is_redirected_from_user_data_pages_to_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach ([
            '/dashboard',
            '/data',
            '/data/produced-water',
            '/data/emission-trends',
            '/data/realtime/ozone',
            '/data/subsurface-natural-gas-leaks',
            '/data/oil-well-pad-emissions',
            '/carbon-mapper/utah',
        ] as $path) {
            $this->get($path)->assertRedirect(route('admin.dashboard'));
        }
    }

    public function test_admin_cannot_fetch_user_data_from_json_endpoints(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach ([
            '/data/produced-water/flux/json',
            '/data/produced-water/chemistry/json',
            '/data/emission-trends/json',
            '/data/subsurface-natural-gas-leaks/overview/json',
            '/data/oil-well-pad-emissions/overview/json',
            '/data/realtime/ozone/json',
            '/carbon-mapper/utah/json',
        ] as $path) {
            $this->getJson($path)->assertForbidden();
        }

        $this->postJson('/data/ai-summary/emission-trends', [
            'selected' => [],
        ])->assertForbidden();
    }

    public function test_admin_cannot_mutate_regular_user_profile_through_frontend_endpoint(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->patch('/profile/update', [
            'name' => 'Should Not Change',
            'email' => $admin->email,
        ])->assertForbidden();

        $this->assertSame($admin->name, $admin->fresh()->name);
    }

    public function test_regular_users_can_still_access_protected_data_pages(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $this->get('/dashboard')->assertOk();
        $this->get('/data/produced-water')->assertOk();
    }

    public function test_public_website_is_still_available_to_admins_and_guests(): void
    {
        $this->get('/')->assertOk();
        $this->actingAs(User::factory()->admin()->create());
        $this->get('/')->assertOk();
        $this->get('/about')->assertOk();
        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_guests_still_need_authentication_for_user_data(): void
    {
        $this->get('/data/produced-water')
            ->assertRedirect(route('frontend.auth.login'));
        $this->get('/carbon-mapper/utah')
            ->assertRedirect(route('frontend.auth.login'));
    }
}
