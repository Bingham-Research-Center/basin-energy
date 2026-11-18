<?php

namespace Tests\Feature\Backend;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\Services\UserService;
use App\Domains\Auth\Support\AccountNavigation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminAccountRoutingTest extends TestCase
{
    public function test_admin_edit_form_posts_to_backend_profile_route(): void
    {
        $admin = $this->loginAsAdmin();
        $this->get('/admin/account')
            ->assertOk()
            ->assertSee('action="'.route('admin.account.profile.update').'"', false)
            ->assertDontSee('action="'.route('frontend.user.profile.update').'"', false);
    }

    public function test_admin_name_change_returns_to_backend_without_affecting_roles_or_permissions(): void
    {
        $admin = $this->loginAsAdmin();
        $roleIds = $admin->roles->modelKeys();
        $permissionIds = $admin->permissions->modelKeys();
        $type = $admin->type;

        $response = $this->patch('/admin/account/profile', [
            'name' => 'Updated Administrator',
            'email' => $admin->email,
        ]);

        $response->assertRedirect(route('admin.account.index').'#information');
        $response->assertSessionHas('flash_success');
        $this->assertSame('Updated Administrator', $admin->fresh()->name);
        $this->assertSame($type, $admin->fresh()->type);
        $this->assertEqualsCanonicalizing($roleIds, $admin->fresh()->roles->modelKeys());
        $this->assertEqualsCanonicalizing($permissionIds, $admin->fresh()->permissions->modelKeys());
        $this->get('/admin/account')->assertOk();
    }

    public function test_legacy_frontend_profile_submission_cannot_modify_an_administrator(): void
    {
        $admin = $this->loginAsAdmin();
        $originalName = $admin->name;

        $this->patch('/profile/update', [
            'name' => 'Admin Renamed Again',
            'email' => $admin->email,
        ])->assertForbidden();

        // Administrators update their profile through /admin/account/profile.
        $this->assertSame($originalName, $admin->fresh()->name);
        $this->get('/account')->assertRedirect(route('admin.account.index'));
    }

    public function test_limited_administrator_can_manage_own_account_without_master_role(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $this->get('/admin/account')->assertOk();

        $this->patch('/admin/account/profile', [
            'name' => 'Limited Administrator',
            'email' => $admin->email,
        ])->assertRedirect(route('admin.account.index').'#information');

        $this->assertSame(User::TYPE_ADMIN, $admin->fresh()->type);
        $this->assertFalse($admin->fresh()->roles()->exists());
    }

    public function test_regular_user_profile_stays_in_user_dashboard(): void
    {
        $user = User::factory()->user()->create();
        $this->actingAs($user);

        $this->patch('/profile/update', [
            'name' => 'Updated User',
            'email' => $user->email,
        ])->assertRedirect(route('frontend.user.account').'#information');

        $this->get('/account')->assertOk();
        $this->assertSame(User::TYPE_USER, $user->fresh()->type);
    }

    public function test_password_change_redirects_admin_to_backend_account(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => 'OldStrong123!',
        ]);
        $this->actingAs($admin);

        $this->patch('/password/update', [
            'current_password' => 'OldStrong123!',
            'password' => 'NewStrong456!',
            'password_confirmation' => 'NewStrong456!',
        ])->assertRedirect(route('admin.account.index').'#password');

        $this->assertTrue(Hash::check('NewStrong456!', $admin->fresh()->password));
        $this->assertSame(User::TYPE_ADMIN, $admin->fresh()->type);
    }

    public function test_expired_password_update_checks_the_current_password(): void
    {
        config(['boilerplate.access.user.password_expires_days' => 90]);
        $admin = User::factory()->admin()->create([
            'password' => 'OldStrong123!',
        ]);
        $this->actingAs($admin);

        $this->patch('/password/expired', [
            'current_password' => 'Incorrect123!',
            'password' => 'NewStrong456!',
            'password_confirmation' => 'NewStrong456!',
        ])->assertSessionHas('flash_danger');

        $this->assertTrue(Hash::check('OldStrong123!', $admin->fresh()->password));

        $this->patch('/password/expired', [
            'current_password' => 'OldStrong123!',
            'password' => 'NewStrong456!',
            'password_confirmation' => 'NewStrong456!',
        ])->assertRedirect(route('admin.account.index').'#password');
    }

    public function test_email_change_persists_new_email_before_sending_verification(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create([
            'email' => 'old-admin@example.test',
        ]);
        $this->actingAs($admin);

        $response = $this->patch('/admin/account/profile', [
            'name' => $admin->name,
            'email' => 'new-admin@example.test',
        ]);

        $response->assertRedirect(route('admin.account.index').'#information');
        $this->assertSame('new-admin@example.test', $admin->fresh()->email);
        $this->assertNull($admin->fresh()->email_verified_at);
        Notification::assertSentTo($admin, \App\Domains\Auth\Notifications\Frontend\VerifyEmail::class);
    }

    public function test_2fa_settings_select_layout_by_account_type(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->user()->create();
        $this->assertSame(
            'backend.auth.admin.account.tabs.two-factor-authentication.enable',
            AccountNavigation::twoFactorView($admin, 'enable')
        );
        $this->assertSame(
            'frontend.user.account.tabs.two-factor-authentication.disable',
            AccountNavigation::twoFactorView($user, 'disable')
        );
        $this->assertSame(
            route('admin.account.index').'#two-factor-authentication',
            AccountNavigation::url($admin, 'two-factor-authentication')
        );
    }
}
