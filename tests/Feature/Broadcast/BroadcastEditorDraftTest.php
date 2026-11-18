<?php

namespace Tests\Feature\Broadcast;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use App\Mail\BroadcastAnnouncementMail;
use App\Mail\MonthlyNewsletterMail;
use App\Models\BroadcastAnnouncement;
use App\Models\BroadcastNewsletter;
use App\Models\NewsletterSubscription;
use App\Services\BroadcastDeliveryService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BroadcastEditorDraftTest extends TestCase
{
    public function test_newsletter_index_has_summernote_modal_and_resume_draft_link(): void
    {
        $this->loginAsAdmin();
        $this->get(route('admin.broadcast.newsletters.index'))
            ->assertOk()
            ->assertSee('newsletterEditorModal')
            ->assertSee('js-broadcast-rich-editor')
            ->assertSee('data-broadcast-create="newsletter"', false);

        $this->get(route('admin.broadcast.announcements.index'))
            ->assertOk()
            ->assertSee('announcementEditorModal')
            ->assertSee('js-broadcast-rich-editor')
            ->assertSee('data-broadcast-create="announcement"', false);
    }

    public function test_newsletter_draft_can_be_saved_and_resumed_without_sending(): void
    {
        Mail::fake();
        $this->loginAsAdmin();
        $month = now('America/Denver')->addMonths(2)->format('Y-m');

        $this->post(route('admin.broadcast.newsletters.store'), [
            'issue_month' => $month,
            'body' => '<p>Work in progress</p>',
        ])->assertRedirect(route('admin.broadcast.newsletters.index'));

        $issue = BroadcastNewsletter::firstOrFail();
        $this->assertSame('Untitled newsletter', $issue->title);
        $this->assertSame(BroadcastNewsletter::DRAFT, $issue->status);
        $this->assertSame('html', $issue->body_format);

        $this->getJson(route('admin.broadcast.newsletters.edit', $issue))
            ->assertOk()
            ->assertJsonPath('editable', true)
            ->assertJsonPath('body_html', '<p>Work in progress</p>');

        $this->put(route('admin.broadcast.newsletters.update', $issue), [
            'issue_month' => $month,
            'title' => 'November update',
            'body' => '<h2>Ready later</h2><script>alert(1)</script>',
        ])->assertRedirect(route('admin.broadcast.newsletters.index'));

        $issue->refresh();
        $this->assertSame(BroadcastNewsletter::DRAFT, $issue->status);
        $this->assertStringNotContainsString('<script', $issue->body);

        app(BroadcastDeliveryService::class)->run();
        Mail::assertNothingSent();
    }

    public function test_unscheduled_month_and_incomplete_drafts_never_send_newsletters(): void
    {
        Mail::fake();
        $subscriber = User::factory()->user()->create();
        NewsletterSubscription::create([
            'user_id' => $subscriber->id,
            'subscribed_at' => now(),
        ]);
        $this->loginAsAdmin();
        $month = now('America/Denver')->addMonths(2)->format('Y-m');

        // No newsletter record, even though a subscriber exists.
        app(BroadcastDeliveryService::class)->run();
        Mail::assertNothingSent();

        $this->post(route('admin.broadcast.newsletters.store'), [
            'issue_month' => $month, 'title' => '',
            'body' => '<p><br></p>',
        ])->assertRedirect();

        $issue = BroadcastNewsletter::firstOrFail();
        $this->post(route('admin.broadcast.newsletters.schedule', $issue))
            ->assertSessionHasErrors('body');

        $this->assertSame(BroadcastNewsletter::DRAFT, $issue->fresh()->status);
        app(BroadcastDeliveryService::class)->run();
        Mail::assertNothingSent();
    }

    public function test_announcement_draft_can_be_resumed_then_published_to_user_roles(): void
    {
        Mail::fake();
        $role = Role::factory()->create([
            'name' => 'Broadcast Editor Stakeholder',
            'type' => User::TYPE_USER,
        ]);
        $subscriber = User::factory()->user()->create();
        $subscriber->assignRole($role);
        $other = User::factory()->user()->create();
        $this->loginAsAdmin();

        $this->post(route('admin.broadcast.announcements.store'), [
            'intent' => 'draft',
            'audience' => 'roles',
            'role_ids' => [$role->id],
            'body' => '<p>To be completed</p>',
        ])->assertRedirect(route('admin.broadcast.announcements.index'));

        $announcement = BroadcastAnnouncement::firstOrFail();
        $this->assertSame(BroadcastAnnouncement::DRAFT, $announcement->status);
        // The legacy non-nullable timestamp uses a draft placeholder.
        // Draft status is the authoritative publication flag.
        $this->assertSame(BroadcastAnnouncement::DRAFT, $announcement->status);
        $this->assertSame(0, $announcement->deliveries()->count());

        app(BroadcastDeliveryService::class)->run();
        Mail::assertNothingSent();

        $this->getJson(route('admin.broadcast.announcements.edit', $announcement))
            ->assertOk()
            ->assertJsonPath('editable', true);

        $this->put(route('admin.broadcast.announcements.update', $announcement), [
            'intent' => 'publish',
            'title' => 'Operations Notice',
            'body' => '<h2>New update</h2><script>alert(2)</script>',
            'audience' => 'roles',
            'role_ids' => [$role->id],
        ])->assertRedirect(route('admin.broadcast.announcements.index'));

        $announcement->refresh();
        $this->assertSame(BroadcastAnnouncement::PUBLISHED, $announcement->status);
        $this->assertNotNull($announcement->published_at);
        $this->assertSame(1, $announcement->deliveries()->count());
        $this->assertDatabaseMissing('broadcast_announcement_deliveries', [
            'announcement_id' => $announcement->id,
            'user_id' => $other->id,
        ]);
        $this->assertStringNotContainsString('<script', $announcement->body);

        $this->delete(route('admin.broadcast.announcements.delete', $announcement))
            ->assertForbidden();

        app(BroadcastDeliveryService::class)->run();
        Mail::assertSent(BroadcastAnnouncementMail::class, 1);
    }

    public function test_published_announcements_remain_immutable_and_hidden_from_user_drafts(): void
    {
        $this->loginAsAdmin();

        $this->post(route('admin.broadcast.announcements.store'), [
            'intent' => 'draft',
            'audience' => 'all_users',
            'title' => 'Unpublished',
            'body' => 'Some content',
        ])->assertRedirect();

        $draft = BroadcastAnnouncement::firstOrFail();
        $this->actingAs(User::factory()->user()->create());
        $this->get(route('frontend.broadcast.announcements'))
            ->assertOk()->assertDontSee('Unpublished');
    }
}
