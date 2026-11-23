<?php

namespace Tests\Feature\Broadcast;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use App\Jobs\DispatchBroadcastAnnouncement;
use App\Models\BroadcastAnnouncement;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AnnouncementAudienceValidationTest extends TestCase
{
    public function test_cannot_publish_a_new_announcement_to_an_empty_user_role(): void
    {
        Queue::fake();
        $role = Role::factory()->create([
            'name' => 'No Broadcast Recipients',
            'type' => User::TYPE_USER,
        ]);
        $this->loginAsAdmin();

        $this->post(route('admin.broadcast.announcements.store'), [
            'title' => 'Should remain unpublished',
            'body' => '<p>Important notice</p>',
            'audience' => 'roles',
            'role_ids' => [$role->id],
        ])->assertSessionHasErrors('audience');

        $this->assertDatabaseMissing('broadcast_announcements', [
            'title' => 'Should remain unpublished',
        ]);
        Queue::assertNotPushed(DispatchBroadcastAnnouncement::class);
    }

    public function test_cannot_publish_existing_draft_with_no_eligible_recipients(): void
    {
        Queue::fake();
        $role = Role::factory()->create([
            'name' => 'Unassigned Announcement Role',
            'type' => User::TYPE_USER,
        ]);
        $admin = $this->loginAsAdmin();
        $draft = BroadcastAnnouncement::create([
            'title' => 'Waiting for recipients',
            'body' => '<p>Ready to publish</p>',
            'body_format' => 'html',
            'audience' => 'roles',
            'role_ids' => [$role->id],
            'status' => BroadcastAnnouncement::DRAFT,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        $this->post(route('admin.broadcast.announcements.publish', $draft))
            ->assertSessionHasErrors('audience');

        $this->assertSame(BroadcastAnnouncement::DRAFT, $draft->fresh()->status);
        $this->assertSame(0, $draft->deliveries()->count());
        Queue::assertNotPushed(DispatchBroadcastAnnouncement::class);
    }
}
