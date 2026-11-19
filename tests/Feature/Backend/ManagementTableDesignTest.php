<?php

namespace Tests\Feature\Backend;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use App\Models\BroadcastAnnouncement;
use Tests\TestCase;

class ManagementTableDesignTest extends TestCase
{
    public function test_user_table_has_badges_avatars_and_existing_livewire_controls(): void
    {
        User::factory()->user()->create([
            'name' => 'Demo Researcher',
            'timezone' => 'America/Denver',
            'email_verified_at' => now(),
        ]);
        $this->loginAsAdmin();

        $this->get(route('admin.auth.user.index'))
            ->assertOk()
            ->assertSee('dashboard-tables.css?v=1')
            ->assertSee('brc-admin-data-table')
            ->assertSee('brc-table-avatar')
            ->assertSee('brc-table-person__name')
            ->assertSee('Demo Researcher')
            ->assertSee('brc-table-pill--admin')
            ->assertSee('brc-table-pill--success')
            ->assertSee('Filters')
            ->assertSee('Columns');
    }

    public function test_table_partials_escape_names_and_labels(): void
    {
        $user = User::factory()->user()->make([
            'name' => '<script>alert(1)</script>',
            'timezone' => '<svg onload=alert(1)>',
        ]);

        $name = view('backend.auth.user.includes.table-name', [
            'user' => $user,
        ])->render();

        $this->assertStringContainsString('brc-table-avatar', $name);
        $this->assertStringContainsString('&lt;script&gt;', $name);
        $this->assertStringNotContainsString('<script>', $name);
        $this->assertStringNotContainsString('<svg', $name);

        $pill = view('backend.includes.table-pill', [
            'text' => '<script>alert(1)</script>',
            'tone' => 'unknown-dangerous-class',
            'title' => 'Safe',
        ])->render();

        $this->assertStringContainsString('brc-table-pill--neutral', $pill);
        $this->assertStringContainsString('&lt;script&gt;', $pill);
        $this->assertStringNotContainsString('<script>', $pill);
        $this->assertStringNotContainsString('unknown-dangerous-class', $pill);
    }

    public function test_roles_table_uses_shared_status_styles(): void
    {
        Role::factory()->create([
            'name' => 'Research Viewer',
            'type' => User::TYPE_USER,
        ]);
        $this->loginAsAdmin();

        $this->get(route('admin.auth.role.index'))
            ->assertOk()
            ->assertSee('dashboard-tables.css?v=1')
            ->assertSee('Research Viewer')
            ->assertSee('brc-table-pill');
    }

    public function test_broadcast_tables_retain_actions_and_progress_with_new_theme(): void
    {
        BroadcastAnnouncement::create([
            'title' => 'Table Theme Example',
            'body' => '<p>Draft message</p>',
            'audience' => 'all_users',
            'role_ids' => [],
            'status' => BroadcastAnnouncement::DRAFT,
            'published_at' => now(),
        ]);

        $this->loginAsAdmin();

        $this->get(route('admin.broadcast.announcements.index'))
            ->assertOk()
            ->assertSee('dashboard-tables.css?v=1')
            ->assertSee('brc-table-pill')
            ->assertSee('Table Theme Example')
            ->assertSee('Edit draft');

        $this->get(route('admin.broadcast.newsletters.index'))
            ->assertOk()
            ->assertSee('dashboard-tables.css?v=1')
            ->assertSee('brc-admin-data-table');
    }
}
