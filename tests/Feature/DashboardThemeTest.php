<?php

namespace Tests\Feature;

use App\Domains\Auth\Models\User;
use Tests\TestCase;

class DashboardThemeTest extends TestCase
{
    public function test_admin_dashboard_uses_shared_theme_controller_and_bootstrap(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('data-dashboard-theme-toggle', false)
            ->assertSee('data-dashboard-theme-value="light"', false)
            ->assertSee('data-dashboard-theme-value="dark"', false)
            ->assertSee('data-dashboard-theme-value="auto"', false)
            ->assertSee("localStorage.getItem('brc-theme')", false)
            ->assertSee('js/dashboard-theme.js')
            ->assertSee('css/dashboard-theme.css');
    }

    public function test_regular_user_dashboard_uses_same_theme_controller(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $response = $this->get('/dashboard');

        $response->assertOk()
            ->assertSee('data-dashboard-theme-toggle', false)
            ->assertSee('data-dashboard-theme-value="light"', false)
            ->assertSee('data-dashboard-theme-value="dark"', false)
            ->assertSee('data-dashboard-theme-value="auto"', false)
            ->assertSee('js/dashboard-theme.js')
            ->assertSee('css/dashboard-theme.css');
    }
}
