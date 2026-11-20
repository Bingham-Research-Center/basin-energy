<?php

namespace Tests\Feature\Backend\Role;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use App\Domains\Auth\Services\DefaultUserRoleService;
use App\Domains\Auth\Services\RoleService;
use App\Domains\Auth\Services\UserService;
use App\Http\Livewire\Backend\RolesTable;
use Livewire\Livewire;
use Tests\TestCase;

class DefaultUserRegistrationRoleTest extends TestCase
{
    private function userRole(string $name): Role
    {
        return Role::factory()->create([
            'name' => $name,
            'type' => User::TYPE_USER,
        ]);
    }

    public function test_no_default_role_is_assumed_before_an_admin_selects_one(): void
    {
        $service = app(DefaultUserRoleService::class);

        $this->assertNull($service->currentRoleId());
        $this->assertNull($service->roleForRegistration());
        $this->assertDatabaseHas('default_user_role_settings', [
            'setting_key' => 'registration',
            'role_id' => null,
        ]);
    }

    public function test_switching_roles_keeps_only_one_selected_and_allows_clearing(): void
    {
        $first = $this->userRole('Regular User');
        $second = $this->userRole('Researcher');
        $admin = $this->getMasterAdmin();

        Livewire::actingAs($admin)->test(RolesTable::class)
            ->call('toggleDefaultRole', $first->id)
            ->assertHasNoErrors();

        $this->assertSame($first->id, app(DefaultUserRoleService::class)->currentRoleId());

        Livewire::actingAs($admin)->test(RolesTable::class)
            ->call('toggleDefaultRole', $second->id)
            ->assertHasNoErrors();

        $this->assertDatabaseCount('default_user_role_settings', 1);
        $this->assertDatabaseHas('default_user_role_settings', [
            'setting_key' => 'registration',
            'role_id' => $second->id,
        ]);

        Livewire::actingAs($admin)->test(RolesTable::class)
            ->call('toggleDefaultRole', $second->id)
            ->assertHasNoErrors();

        $this->assertNull(app(DefaultUserRoleService::class)->currentRoleId());
    }

    public function test_administrator_role_cannot_be_selected_by_a_tampered_request(): void
    {
        Livewire::actingAs($this->getMasterAdmin())->test(RolesTable::class)
            ->call('toggleDefaultRole', $this->getAdminRole()->id)
            ->assertHasErrors(['role']);

        $this->assertNull(app(DefaultUserRoleService::class)->currentRoleId());
    }

    public function test_non_administrator_cannot_change_default_role_via_livewire(): void
    {
        $role = $this->userRole('Standard Visitor');
        $user = User::factory()->user()->create();

        Livewire::actingAs($user)->test(RolesTable::class)
            ->call('toggleDefaultRole', $role->id)
            ->assertForbidden();

        $this->assertNull(app(DefaultUserRoleService::class)->currentRoleId());
    }

    public function test_password_signup_gets_the_selected_role_without_modifying_existing_users(): void
    {
        $existing = User::factory()->user()->create();
        $role = $this->userRole('Environmental Visitor');

        app(DefaultUserRoleService::class)->toggle($role->id);

        $new = app(UserService::class)->registerUser([
            'name' => 'New Visitor',
            'email' => 'new-visitor-role@example.test',
            'password' => 'TestPassw0rd!123',
        ]);

        $this->assertSame(User::TYPE_USER, $new->type);
        $this->assertTrue($new->fresh()->hasRole($role->name));
        $this->assertSame([$role->id], $new->roles()->pluck('id')->all());
        $this->assertFalse($existing->fresh()->roles()->exists());
    }

    public function test_public_password_signup_cannot_submit_admin_type_or_provider_identity(): void
    {
        config([
            'boilerplate.access.user.registration' => true,
            'boilerplate.access.captcha.registration' => false,
        ]);
        $role = $this->userRole('Regular User');
        app(DefaultUserRoleService::class)->toggle($role->id);

        $this->post('/register', [
            'name' => 'Public Registrant',
            'email' => 'signup-overposting@example.test',
            'password' => 'TestPassw0rd!123',
            'password_confirmation' => 'TestPassw0rd!123',
            'terms' => '1',
            'type' => User::TYPE_ADMIN,
            'provider' => 'google',
            'provider_id' => 'forged-google-id',
            'email_verified_at' => now()->toDateTimeString(),
            'active' => '0',
        ])->assertRedirect();

        $user = User::whereEmail('signup-overposting@example.test')->firstOrFail();
        $this->assertSame(User::TYPE_USER, $user->type);
        $this->assertNull($user->provider);
        $this->assertNull($user->provider_id);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->active);
        $this->assertTrue($user->hasRole($role->name));
    }

    public function test_google_provider_signup_gets_the_same_selected_role(): void
    {
        $role = $this->userRole('Student');
        app(DefaultUserRoleService::class)->toggle($role->id);

        $user = app(UserService::class)->registerProvider((object) [
            'id' => 'google-default-123',
            'name' => 'Google Research Student',
            'email' => 'google-student-role@example.test',
        ], 'google');

        $this->assertSame(User::TYPE_USER, $user->type);
        $this->assertTrue($user->fresh()->hasRole('Student'));
        $this->assertSame([$role->id], $user->roles()->pluck('id')->all());

        // Signing in again with the same provider must not reset existing roles.
        app(DefaultUserRoleService::class)->toggle($role->id);
        $returning = app(UserService::class)->registerProvider((object) [
            'id' => 'google-default-123',
            'name' => 'Google Research Student',
            'email' => 'google-student-role@example.test',
        ], 'google');

        $this->assertSame($user->id, $returning->id);
        $this->assertTrue($returning->hasRole('Student'));
    }

    public function test_backend_created_accounts_keep_manually_selected_roles(): void
    {
        $default = $this->userRole('Standard');
        $manual = $this->userRole('Industry Professional');
        app(DefaultUserRoleService::class)->toggle($default->id);

        $account = app(UserService::class)->store([
            'type' => User::TYPE_USER,
            'name' => 'Admin Created User',
            'email' => 'admin-created-user-role@example.test',
            'password' => 'TestPassw0rd!123',
            'roles' => [$manual->id],
        ]);

        $this->assertTrue($account->fresh()->hasRole($manual->name));
        $this->assertFalse($account->fresh()->hasRole($default->name));
    }

    public function test_converting_the_default_role_to_admin_clears_the_setting(): void
    {
        $role = $this->userRole('Temporary Role');
        app(DefaultUserRoleService::class)->toggle($role->id);

        app(RoleService::class)->update($role, [
            'type' => User::TYPE_ADMIN,
            'name' => $role->name,
            'permissions' => [],
        ]);

        $this->assertNull(app(DefaultUserRoleService::class)->currentRoleId());
        $this->assertNull(app(DefaultUserRoleService::class)->roleForRegistration());
    }

    public function test_deleting_an_unused_default_role_clears_the_setting(): void
    {
        $role = $this->userRole('Temporary Default');
        app(DefaultUserRoleService::class)->toggle($role->id);

        app(RoleService::class)->destroy($role);

        $this->assertNull(app(DefaultUserRoleService::class)->currentRoleId());
    }

    public function test_audit_knows_an_unapplied_settings_migration_is_a_normal_creation(): void
    {
        \Illuminate\Support\Facades\DB::table('migrations')
            ->where('migration', '2026_11_23_000001_create_default_user_role_settings_table')
            ->delete();
        \Illuminate\Support\Facades\Schema::dropIfExists('default_user_role_settings');

        $rows = app(\App\Console\Commands\AuditLegacyMigrations::class)
            ->buildReport(app(\App\Support\LegacyMigrationSchemaInspector::class));

        $row = collect($rows)->firstWhere(
            'migration', '2026_11_23_000001_create_default_user_role_settings_table'
        );
        $this->assertSame('create', $row['state']);
    }

    public function test_audit_refuses_to_adopt_settings_table_without_its_singleton_row(): void
    {
        \Illuminate\Support\Facades\DB::table('migrations')
            ->where('migration', '2026_11_23_000001_create_default_user_role_settings_table')
            ->delete();
        \Illuminate\Support\Facades\DB::table('default_user_role_settings')
            ->where('setting_key', 'registration')->delete();

        $rows = app(\App\Console\Commands\AuditLegacyMigrations::class)
            ->buildReport(app(\App\Support\LegacyMigrationSchemaInspector::class));

        $row = collect($rows)->firstWhere(
            'migration', '2026_11_23_000001_create_default_user_role_settings_table'
        );
        $this->assertSame('BLOCKED', $row['state']);
        $this->assertStringContainsString('singleton setting row', $row['detail']);
    }

    public function test_role_management_renders_a_switch_only_for_user_type_roles(): void
    {
        $role = $this->userRole('Example Researcher');
        $this->loginAsAdmin();

        $response = $this->get(route('admin.auth.role.index'));
        $response->assertOk()
            ->assertSee('Default User Role')
            ->assertSee('role="switch"', false)
            ->assertSee('toggleDefaultRole('.$role->id.')', false)
            ->assertSee('Not applicable to Administrator roles', false);
    }
}
