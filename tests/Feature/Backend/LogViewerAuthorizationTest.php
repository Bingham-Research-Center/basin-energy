<?php

namespace Tests\Feature\Backend;

use App\Domains\Auth\Models\User;
use Tests\TestCase;

/**
 * LogViewer registers vendor routes outside routes/web.php.
 * The package middleware must protect every one of them, not just a menu.
 */
class LogViewerAuthorizationTest extends TestCase
{
    public function test_vendor_log_routes_use_the_standard_admin_middleware(): void
    {
        $routes = [
            'log-viewer::dashboard',
            'log-viewer::logs.list',
            'log-viewer::logs.show',
            'log-viewer::logs.download',
            'log-viewer::logs.filter',
            'log-viewer::logs.search',
            'log-viewer::logs.delete',
        ];

        foreach ($routes as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route, 'Missing log route: '.$name);
            $this->assertContains('admin', $route->gatherMiddleware(), $name);
            $this->assertContains('web', $route->gatherMiddleware(), $name);
        }
    }

    public function test_guests_cannot_view_or_download_logs(): void
    {
        $this->get('/log-viewer')->assertRedirect('/login');
        $this->get('/log-viewer/logs')->assertRedirect('/login');
        $this->get('/log-viewer/logs/2026-11-17')->assertRedirect('/login');
        $this->get('/log-viewer/logs/2026-11-17/download')->assertRedirect('/login');
        $this->delete('/log-viewer/logs/delete', ['date' => '2026-11-17'])
            ->assertRedirect('/login');
    }

    public function test_regular_users_cannot_access_any_log_operation(): void
    {
        $user = User::factory()->user()->create();
        $this->actingAs($user);

        $this->get('/log-viewer')->assertRedirect(route('frontend.index'));
        $this->get('/log-viewer/logs')->assertRedirect(route('frontend.index'));
        $this->get('/log-viewer/logs/2026-11-17')->assertRedirect(route('frontend.index'));
        $this->get('/log-viewer/logs/2026-11-17/download')
            ->assertRedirect(route('frontend.index'));
        $this->get('/log-viewer/logs/2026-11-17/error')
            ->assertRedirect(route('frontend.index'));
        $this->get('/log-viewer/logs/2026-11-17/error/search?query=secret')
            ->assertRedirect(route('frontend.index'));
        $this->delete('/log-viewer/logs/delete', ['date' => '2026-11-17'])
            ->assertRedirect(route('frontend.index'));
    }

    public function test_admin_views_logs_with_coreui_navigation(): void
    {
        $this->loginAsAdmin();

        $this->get('/log-viewer')
            ->assertOk()
            ->assertSee('id="sidebar"', false)
            ->assertSee('class="c-wrapper c-fixed-components"', false)
            ->assertSee('Application Logs');

        $this->get('/log-viewer/logs')
            ->assertOk()
            ->assertSee('id="sidebar"', false)
            ->assertSee('Log Files');
    }

    public function test_limited_admin_type_also_has_access(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->get('/log-viewer')->assertOk();
        $this->get('/log-viewer/logs')->assertOk();
    }
}
