<?php

namespace Tests\Feature\Backend;

use App\Domains\Auth\Http\Middleware\TwoFactorAuthenticationStatus;
use App\Domains\Auth\Models\User;
use Tests\TestCase;

class LogViewerTwoFactorRoutingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Base TestCase disables enrollment middleware; re-enable it here
        // to exercise the actual production behavior, not a bypassed test.
        $this->withMiddleware(TwoFactorAuthenticationStatus::class);
    }

    public function test_optional_admin_two_factor_allows_log_dashboard_and_list(): void
    {
        config(['boilerplate.access.user.admin_requires_2fa' => false]);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('log-viewer::dashboard'))->assertOk();
        $this->get(route('log-viewer::logs.list'))->assertOk();
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_required_admin_two_factor_still_blocks_unenrolled_admins(): void
    {
        config(['boilerplate.access.user.admin_requires_2fa' => true]);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('log-viewer::dashboard'))
            ->assertRedirect(route('frontend.user.account'));
        $this->get(route('log-viewer::logs.list'))
            ->assertRedirect(route('frontend.user.account'));
    }

    public function test_user_accounts_still_cannot_access_logs_with_optional_admin_2fa(): void
    {
        config(['boilerplate.access.user.admin_requires_2fa' => false]);
        $this->actingAs(User::factory()->user()->create());

        $this->get(route('log-viewer::dashboard'))
            ->assertRedirect(route('frontend.index'));
    }
}
