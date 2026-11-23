<?php

namespace Tests\Feature;

use App\Domains\Auth\Models\User;
use Tests\TestCase;

class DashboardSweetAlertToastTest extends TestCase
{
    public function test_admin_dashboard_renders_shared_toasts_instead_of_inline_flash_banner(): void
    {
        $this->loginAsAdmin();
        $this->withSession([
            'flash_success' => 'Research data updated successfully',
            'flash_warning' => 'Please review the deployment settings',
        ])->get(route('admin.broadcast.newsletters.index'))
            ->assertOk()
            ->assertSee('brc-dashboard-toast-data')
            ->assertSee('dashboard-toasts.js')
            ->assertSee('Research data updated successfully')
            ->assertSee('Please review the deployment settings')
            ->assertDontSee('alert alert-success header-message', false)
            ->assertDontSee('alert alert-warning header-message', false);
    }

    public function test_user_dashboard_includes_shared_toast_notifications(): void
    {
        $this->actingAs(User::factory()->user()->create());
        $this->withSession(['status' => 'Your subscription has been saved'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('brc-dashboard-toast-data')
            ->assertSee('dashboard-toasts.js')
            ->assertSee('Your subscription has been saved')
            ->assertDontSee('alert alert-success header-message', false);
    }

    public function test_validation_errors_remain_visible_inside_dashboard(): void
    {
        $this->loginAsAdmin();
        $this->withSession([
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ])->get(route('admin.broadcast.newsletters.index'))
            ->assertOk()
            ->assertSee('newsletterEditorModal');
    }

    public function test_publish_and_schedule_controls_do_not_use_browser_native_confirm(): void
    {
        $this->loginAsAdmin();
        $this->get(route('admin.broadcast.announcements.index'))
            ->assertOk()
            ->assertSee('data-broadcast-confirm-publish', false)
            ->assertDontSee("onclick=\"return confirm('Publish", false);

        $this->get(route('admin.broadcast.newsletters.index'))
            ->assertOk()
            ->assertSee('data-broadcast-confirm-schedule', false)
            ->assertDontSee("onclick=\"return confirm('Schedule", false);
    }

    public function test_shared_toast_payload_is_json_escaped_to_prevent_inline_script_injection(): void
    {
        $this->loginAsAdmin();
        $message = '<script>alert("test")</script>';
        $this->withSession(['flash_info' => $message])
            ->get(route('admin.broadcast.newsletters.index'))
            ->assertOk()
            ->assertSee('brc-dashboard-toast-data')
            ->assertDontSee('<script>alert("test")</script>', false);
    }
}
