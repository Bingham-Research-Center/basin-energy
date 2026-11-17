<?php

namespace Tests\Feature\Backend\Role;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use App\Domains\Auth\Services\UserService;
use Database\Seeders\Auth\DataCategorySeeder;
use Tests\TestCase;

class DataCategoryPermissionsTest extends TestCase
{
    public function test_user_data_categories_are_seeded_but_no_user_roles_are_precreated(): void
    {
        $group = Permission::whereName('user.data')->firstOrFail();
        $this->assertSame(User::TYPE_USER, $group->type);

        foreach (DataCategorySeeder::CATEGORIES as $key => $label) {
            $permission = Permission::whereName('user.data.'.$key)->firstOrFail();
            $this->assertSame(User::TYPE_USER, $permission->type);
            $this->assertSame($label, $permission->description);
            $this->assertSame($group->id, $permission->parent_id);
        }

        $this->assertSame(0, Role::where('type', User::TYPE_USER)->count());
        $this->assertSame(1, Role::whereName('Administrator')->count());
        $this->assertDatabaseMissing('permissions', ['name' => 'user.audience.student']);
    }

    public function test_admin_can_create_arbitrarily_named_user_role_with_multiple_data_permissions(): void
    {
        $this->loginAsAdmin();

        $ids = Permission::whereIn('name', [
            'user.data.research',
            'user.data.weather',
        ])->pluck('id')->all();

        $this->post('/admin/auth/role', [
            'type' => User::TYPE_USER,
            'name' => 'Environmental Researcher',
            'permissions' => $ids,
        ])->assertRedirect(route('admin.auth.role.index'));

        $role = Role::whereName('Environmental Researcher')->firstOrFail();

        $this->assertSame(User::TYPE_USER, $role->type);
        $this->assertTrue($role->hasPermissionTo('user.data.research'));
        $this->assertTrue($role->hasPermissionTo('user.data.weather'));
        $this->assertFalse($role->hasPermissionTo('admin.access.user.list'));
    }

    public function test_data_categories_heading_is_not_assignable_as_a_permission(): void
    {
        $this->loginAsAdmin();
        $group = Permission::whereName('user.data')->firstOrFail();

        $this->post('/admin/auth/role', [
            'type' => User::TYPE_USER,
            'name' => 'Invalid Broad Access',
            'permissions' => [$group->id],
        ])->assertSessionHasErrors('permissions.0');

        $this->assertDatabaseMissing('roles', ['name' => 'Invalid Broad Access']);
    }

    public function test_admin_permissions_are_not_allowed_on_user_roles(): void
    {
        $this->loginAsAdmin();
        $adminId = Permission::whereName('admin.access.user.list')->firstOrFail()->id;

        $this->post('/admin/auth/role', [
            'type' => User::TYPE_USER,
            'name' => 'Invalid Admin Access',
            'permissions' => [$adminId],
        ])->assertSessionHasErrors('permissions.0');
    }

    public function test_new_self_registered_user_has_no_automatically_assigned_role(): void
    {
        $user = app(UserService::class)->registerUser([
            'name' => 'Research Visitor',
            'email' => 'visitor-data-access@example.test',
            'password' => 'TestPassword#123',
        ]);

        $this->assertSame(User::TYPE_USER, $user->type);
        $this->assertFalse($user->roles()->exists());
        $this->assertFalse($user->can('user.data.classified'));
        $this->assertFalse($user->isAdmin());
    }

    public function test_migration_preserves_existing_roles_and_users_but_retires_old_permissions(): void
    {
        // Simulate a legacy audience permission without requiring the
        // personal-webapp-only UserAudienceSeeder.
        $legacyGroup = Permission::create([
            'name' => 'user.audience',
            'guard_name' => 'web',
            'type' => User::TYPE_USER,
            'description' => 'Audience Categories',
        ]);
        $legacyPermission = Permission::create([
            'name' => 'user.audience.student',
            'guard_name' => 'web',
            'type' => User::TYPE_USER,
            'description' => 'Student',
            'parent_id' => $legacyGroup->id,
        ]);
        $student = Role::create([
            'name' => 'Student',
            'guard_name' => 'web',
            'type' => User::TYPE_USER,
        ]);
        $student->givePermissionTo($legacyPermission);
        $user = User::factory()->user()->create();
        $user->assignRole($student);

        $migration = require database_path('migrations/2026_11_18_000000_replace_user_audience_permissions_with_data_categories.php');
        $migration->up();

        $this->assertTrue($user->fresh()->hasRole('Student'));
        $this->assertDatabaseHas('roles', ['id' => $student->id, 'name' => 'Student']);
        $this->assertDatabaseMissing('permissions', ['name' => 'user.audience.student']);
        $this->assertDatabaseMissing('permissions', ['name' => 'user.audience']);
        $this->assertDatabaseHas('permissions', ['name' => 'user.data.classified', 'type' => 'user']);
    }

    public function test_data_categories_can_be_seeded_repeatedly_without_duplicates(): void
    {
        app(DataCategorySeeder::class)->run();
        app(DataCategorySeeder::class)->run();

        $this->assertSame(7, Permission::where('type', User::TYPE_USER)->count());
        $this->assertSame(0, Role::where('type', User::TYPE_USER)->count());
    }

    public function test_new_categories_are_visible_in_role_management(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/auth/role/create')
            ->assertOk()
            ->assertSee('<h5 class="mb-3">Data Categories</h5>', false)
            ->assertSee('<ul class="list-unstyled ml-0">', false)
            ->assertSee('Research only')
            ->assertSee('Classified')
            ->assertSee('Real-time')
            ->assertSee('Weather')
            ->assertDontSee('Audience Categories')
            ->assertSee('All User Permissions');
    }
}
