<?php

namespace Tests\Feature\Broadcast;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use App\Models\BroadcastAnnouncement;
use Tests\TestCase;

class BroadcastAudienceRoleNamesTest extends TestCase
{
    public function test_announcement_table_displays_user_role_names_instead_of_ids(): void
    {
        $researchers = Role::factory()->create([
            'name' => 'Research Scientists',
            'type' => User::TYPE_USER,
        ]);
        $stakeholders = Role::factory()->create([
            'name' => 'Community Stakeholders',
            'type' => User::TYPE_USER,
        ]);

        BroadcastAnnouncement::create([
            'title' => 'Multiple Roles Notice',
            'body' => 'Announcement details',
            'audience' => 'roles',
            'role_ids' => [$researchers->id, $stakeholders->id],
            'status' => BroadcastAnnouncement::PUBLISHED,
            'published_at' => now(),
        ]);

        BroadcastAnnouncement::create([
            'title' => 'Everyone Notice',
            'body' => 'Announcement details',
            'audience' => 'all_users',
            'role_ids' => [],
            'status' => BroadcastAnnouncement::PUBLISHED,
            'published_at' => now(),
        ]);

        $this->loginAsAdmin();

        $this->get(route('admin.broadcast.announcements.index'))
            ->assertOk()
            ->assertSee('Selected User roles: Research Scientists, Community Stakeholders')
            ->assertSee('All Users')
            ->assertDontSee('Selected User role IDs:');
    }

    public function test_deleted_role_is_displayed_as_unavailable_instead_of_disappearing(): void
    {
        BroadcastAnnouncement::create([
            'title' => 'Archived Role Notice',
            'body' => 'Announcement details',
            'audience' => 'roles',
            'role_ids' => [999999],
            'status' => BroadcastAnnouncement::PUBLISHED,
            'published_at' => now(),
        ]);

        $this->loginAsAdmin();

        $this->get(route('admin.broadcast.announcements.index'))
            ->assertOk()
            ->assertSee('Selected User roles: Unavailable role (ID 999999)');
    }
}
