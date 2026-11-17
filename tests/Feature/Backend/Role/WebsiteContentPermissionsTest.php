<?php

namespace Tests\Feature\Backend\Role;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\User;
use App\Domains\Auth\Support\WebsiteContentPermissions;
use Database\Seeders\Auth\WebsiteContentPermissionSeeder;
use Tests\TestCase;

class WebsiteContentPermissionsTest extends TestCase
{
    public function test_permission_tree_has_admin_parent_and_six_sections(): void
    {
        $parent = Permission::where('name', WebsiteContentPermissions::ALL)->firstOrFail();
        $this->assertSame(User::TYPE_ADMIN, $parent->type);
        $this->assertNull($parent->parent_id);
        $this->assertSame('All website content edits', $parent->description);

        foreach (WebsiteContentPermissions::SECTIONS as $section => $description) {
            $permission = Permission::where('name', WebsiteContentPermissions::permission($section))->firstOrFail();
            $this->assertSame(User::TYPE_ADMIN, $permission->type);
            $this->assertSame($parent->id, $permission->parent_id);
            $this->assertSame($description, $permission->description);
        }

        $this->loginAsAdmin();
        $this->get('/admin/auth/role/create')
            ->assertOk()
            ->assertSee('All website content edits')
            ->assertSee('Contacts');
    }

    public function test_seeder_is_idempotent_and_migration_does_not_remove_roles(): void
    {
        $before = Permission::where('name', 'like', 'admin.website.edit%')->count();
        app(WebsiteContentPermissionSeeder::class)->run();
        app(WebsiteContentPermissionSeeder::class)->run();

        $this->assertSame(7, $before);
        $this->assertSame(7, Permission::where('name', 'like', 'admin.website.edit%')->count());
        $this->assertDatabaseHas('roles', ['name' => 'Administrator']);
    }

    public function test_limited_admin_can_edit_only_home(): void
    {
        $user = User::factory()->admin()->create();
        $user->givePermissionTo(WebsiteContentPermissions::permission('home'));
        $this->actingAs($user);

        $this->get('/admin/website-update/home')->assertOk();
        $this->post('/admin/website-update/home', ['hero_title' => 'Approved page'])
            ->assertRedirect();
        $this->assertDatabaseHas('home_page_contents', [
            'section' => 'hero',
            'item_key' => 'main',
            'title' => 'Approved page',
        ]);

        // Viewing and direct POSTing are both protected, not just navigation.
        $this->get('/admin/website-update/about')->assertRedirect(route(homeRoute()));
        $this->post('/admin/website-update/about', ['hero_title' => 'Blocked'])
            ->assertRedirect(route(homeRoute()));
        $this->post('/admin/website-update/blog/images')
            ->assertRedirect(route(homeRoute()));

        $this->get('/admin/website-update/')->assertOk();
        $this->get('/admin/website-update/home')->assertSee('Website Updates');
        $this->get('/admin/website-update/home')->assertDontSee('href="'.route('admin.website-update.about').'"', false);
    }

    public function test_blog_admin_can_manage_blog_but_not_other_sections(): void
    {
        $user = User::factory()->admin()->create();
        $user->givePermissionTo(WebsiteContentPermissions::permission('blog'));
        $this->actingAs($user);

        $this->get('/admin/website-update/blog')->assertOk();
        $this->post('/admin/website-update/blog', ['hero_title' => 'Updated blog'])
            ->assertRedirect();

        $this->get('/admin/website-update/portfolio')->assertRedirect(route(homeRoute()));
        $this->post('/admin/website-update/portfolio/categories', ['name' => 'Blocked'])
            ->assertRedirect(route(homeRoute()));
        $this->delete('/admin/website-update/contact/messages/123')
            ->assertRedirect(route(homeRoute()));
    }

    public function test_parent_permission_grants_all_six_sections(): void
    {
        $user = User::factory()->admin()->create();
        $user->givePermissionTo(WebsiteContentPermissions::ALL);
        $this->actingAs($user);

        foreach (array_keys(WebsiteContentPermissions::SECTIONS) as $section) {
            $this->get('/admin/website-update/'.$section)->assertOk();
            $this->assertTrue(WebsiteContentPermissions::canEdit($user, $section));
        }
    }

    public function test_admin_without_content_permissions_cannot_see_or_modify_website(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $this->get('/admin/website-update/')->assertRedirect(route(homeRoute()));
        $this->get('/admin/website-update/home')->assertRedirect(route(homeRoute()));
        $this->post('/admin/website-update/home', ['hero_title' => 'Forbidden'])
            ->assertRedirect(route(homeRoute()));
        $this->assertDatabaseMissing('home_page_contents', ['title' => 'Forbidden']);

        $this->get('/admin/dashboard')->assertOk()->assertDontSee('Website Updates');
    }

    public function test_regular_users_cannot_access_admin_website_even_with_permission(): void
    {
        $user = User::factory()->user()->create();
        $this->actingAs($user);

        $this->get('/admin/website-update/home')->assertRedirect(route('frontend.index'));
    }

    public function test_administrator_role_keeps_existing_full_access(): void
    {
        $this->loginAsAdmin();

        foreach (array_keys(WebsiteContentPermissions::SECTIONS) as $section) {
            $this->get('/admin/website-update/'.$section)->assertOk();
        }
    }
}
