<?php

namespace Tests\Feature\Broadcast;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use App\Mail\BroadcastAnnouncementMail;
use App\Mail\MonthlyNewsletterMail;
use App\Models\BroadcastAnnouncement;
use App\Models\BroadcastNewsletter;
use App\Models\NewsletterAdminAlert;
use App\Models\NewsletterSubscription;
use App\Services\BroadcastDeliveryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BroadcastFeatureTest extends TestCase
{
    public function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_user_can_subscribe_once_and_admin_receives_persistent_alert(): void
    {
        $admin = $this->getMasterAdmin();
        $user = User::factory()->user()->create();
        $this->actingAs($user);

        $this->get('/dashboard')->assertOk()->assertSee('Subscribe to Newsletter');

        $this->post(route('frontend.broadcast.newsletter.subscribe'))->assertRedirect();
        $this->post(route('frontend.broadcast.newsletter.subscribe'))->assertRedirect();

        $this->assertSame(1, NewsletterSubscription::where('user_id', $user->id)->count());
        $this->assertNotNull(NewsletterSubscription::where('user_id', $user->id)->first()->subscribed_at);
        $this->assertSame(1, NewsletterAdminAlert::where('admin_id', $admin->id)
            ->where('subscriber_id', $user->id)->count());

        $this->delete(route('frontend.broadcast.newsletter.unsubscribe'))->assertRedirect();
        $this->assertNotNull(NewsletterSubscription::where('user_id', $user->id)->first()->unsubscribed_at);
        $this->post(route('frontend.broadcast.newsletter.subscribe'))->assertRedirect();
        $this->assertSame(2, NewsletterAdminAlert::where('admin_id', $admin->id)
            ->where('subscriber_id', $user->id)->count());
    }

    public function test_admin_cannot_subscribe_as_a_regular_user(): void
    {
        $this->loginAsAdmin();
        $this->post(route('frontend.broadcast.newsletter.subscribe'))
            ->assertForbidden();
        $this->assertSame(0, NewsletterSubscription::count());
    }

    public function test_alert_endpoint_is_admin_only_and_read_markers_are_scoped_to_account(): void
    {
        $user = User::factory()->user()->create();
        NewsletterAdminAlert::create([
            'admin_id' => $this->getMasterAdmin()->id,
            'subscriber_id' => $user->id,
        ]);
        $this->actingAs($user);
        $this->getJson(route('admin.broadcast.alerts'))
            ->assertRedirect(route('frontend.index'));

        $this->loginAsAdmin();
        $this->getJson(route('admin.broadcast.alerts'))
            ->assertOk()->assertJsonPath('count', 1);
        $this->post(route('admin.broadcast.alerts.read'))
            ->assertRedirect(route('admin.broadcast.newsletters.index'));
        $this->getJson(route('admin.broadcast.alerts'))
            ->assertJsonPath('count', 0);
    }

    public function test_newsletter_requires_explicit_schedule_and_sends_only_at_month_end(): void
    {
        Mail::fake();
        $subscriber = User::factory()->user()->create();
        NewsletterSubscription::create([
            'user_id' => $subscriber->id,
            'subscribed_at' => now(),
        ]);
        $this->loginAsAdmin();
        $month = now('America/Denver')->addMonths(2)->format('Y-m');

        $this->post(route('admin.broadcast.newsletters.store'), [
            'title' => 'Research Summary',
            'body' => 'The monthly research digest.',
            'issue_month' => $month,
        ])->assertRedirect();

        $issue = BroadcastNewsletter::where('title', 'Research Summary')->firstOrFail();
        $this->assertSame(BroadcastNewsletter::DRAFT, $issue->status);
        app(BroadcastDeliveryService::class)->run();
        Mail::assertNothingSent();

        $this->post(route('admin.broadcast.newsletters.schedule', $issue))
            ->assertRedirect();
        $issue->refresh();
        $this->assertSame(BroadcastNewsletter::SCHEDULED, $issue->status);
        $this->assertSame($month,
            $issue->scheduled_at->timezone('America/Denver')->format('Y-m'));
        $this->assertTrue($issue->scheduled_at->timezone('America/Denver')->isLastOfMonth());

        app(BroadcastDeliveryService::class)->run();
        Mail::assertNothingSent();

        Carbon::setTestNow($issue->scheduled_at->copy()->addSecond());
        app(BroadcastDeliveryService::class)->run();
        $issue->refresh();

        $this->assertSame(BroadcastNewsletter::SENT, $issue->status);
        $this->assertSame(1, $issue->deliveries()->where('status', 'sent')->count());
        Mail::assertSent(MonthlyNewsletterMail::class, 1);

        app(BroadcastDeliveryService::class)->run();
        Mail::assertSent(MonthlyNewsletterMail::class, 1);

        $this->put(route('admin.broadcast.newsletters.update', $issue), [
            'title' => 'Changed',
            'body' => 'Changed body',
            'issue_month' => $month,
        ])->assertForbidden();
        $this->delete(route('admin.broadcast.newsletters.delete', $issue))
            ->assertForbidden();
    }

    public function test_scheduled_issue_can_be_edited_and_returns_to_draft(): void
    {
        $this->loginAsAdmin();
        $month = now('America/Denver')->addMonths(2)->format('Y-m');

        $this->post(route('admin.broadcast.newsletters.store'), [
            'title' => 'Draft A', 'body' => 'Version one', 'issue_month' => $month,
        ])->assertRedirect();

        $issue = BroadcastNewsletter::where('title', 'Draft A')->firstOrFail();
        $this->post(route('admin.broadcast.newsletters.schedule', $issue))->assertRedirect();
        $this->put(route('admin.broadcast.newsletters.update', $issue), [
            'title' => 'Draft B', 'body' => 'Version two', 'issue_month' => $month,
        ])->assertRedirect();

        $this->assertSame(BroadcastNewsletter::DRAFT, $issue->fresh()->status);
        $this->assertNull($issue->fresh()->scheduled_at);
        $this->delete(route('admin.broadcast.newsletters.delete', $issue))->assertRedirect();
        $this->assertDatabaseMissing('broadcast_newsletters', ['id' => $issue->id]);
    }

    public function test_announcement_recipients_are_frozen_to_selected_user_roles(): void
    {
        Mail::fake();
        $stakeholderRole = Role::factory()->create([
            'name' => 'Newsletter Stakeholder', 'type' => User::TYPE_USER,
        ]);
        $stakeholder = User::factory()->user()->create();
        $stakeholder->assignRole($stakeholderRole);
        $other = User::factory()->user()->create();
        $admin = $this->loginAsAdmin();

        $this->post(route('admin.broadcast.announcements.store'), [
            'title' => 'Stakeholder Update',
            'body' => 'Research field campaign notice.',
            'audience' => 'roles',
            'role_ids' => [$stakeholderRole->id],
        ])->assertRedirect(route('admin.broadcast.announcements.index'));

        $announcement = BroadcastAnnouncement::where('title', 'Stakeholder Update')->firstOrFail();
        $this->assertSame(1, $announcement->deliveries()->count());
        $this->assertDatabaseHas('broadcast_announcement_deliveries', [
            'announcement_id' => $announcement->id,
            'user_id' => $stakeholder->id,
        ]);
        $this->assertDatabaseMissing('broadcast_announcement_deliveries', [
            'announcement_id' => $announcement->id,
            'user_id' => $other->id,
        ]);
        $this->assertDatabaseMissing('broadcast_announcement_deliveries', [
            'announcement_id' => $announcement->id,
            'user_id' => $admin->id,
        ]);

        $this->actingAs($stakeholder);
        $this->get(route('frontend.broadcast.announcements'))
            ->assertOk()->assertSee('Stakeholder Update');

        $this->actingAs($other);
        $this->get(route('frontend.broadcast.announcements'))
            ->assertOk()->assertDontSee('Stakeholder Update');

        app(BroadcastDeliveryService::class)->run();
        Mail::assertSent(BroadcastAnnouncementMail::class, 1);
    }

    public function test_announcement_role_selection_rejects_admin_roles(): void
    {
        $role = Role::factory()->create([
            'name' => 'Sensitive Admin Role', 'type' => User::TYPE_ADMIN,
        ]);
        $this->loginAsAdmin();
        $this->post(route('admin.broadcast.announcements.store'), [
            'title' => 'Invalid Audience', 'body' => 'No send',
            'audience' => 'roles', 'role_ids' => [$role->id],
        ])->assertSessionHasErrors('role_ids.0');
        $this->assertDatabaseMissing('broadcast_announcements', ['title' => 'Invalid Audience']);
    }

    public function test_email_unsubscribe_requires_a_signed_confirmation_and_post(): void
    {
        $user = User::factory()->user()->create();
        NewsletterSubscription::create(['user_id' => $user->id, 'subscribed_at' => now()]);

        $url = URL::temporarySignedRoute(
            'frontend.newsletter.unsubscribe.email', now()->addHour(), ['user' => $user->id]
        );
        $this->get($url)->assertOk();
        $this->assertNull($user->fresh()->newsletterSubscription->unsubscribed_at);

        $submitUrl = URL::temporarySignedRoute(
            'frontend.newsletter.unsubscribe.email.submit', now()->addHour(), ['user' => $user->id]
        );
        $this->post($submitUrl)->assertOk();
        $this->assertNotNull($user->fresh()->newsletterSubscription->unsubscribed_at);
        $this->post('/newsletter/unsubscribe/'.$user->id)->assertForbidden();
    }

    public function test_unsubscribing_before_delivery_prevents_newsletter_email(): void
    {
        Mail::fake();
        $user = User::factory()->user()->create();
        NewsletterSubscription::create(['user_id' => $user->id, 'subscribed_at' => now()]);
        $this->loginAsAdmin();

        $month = now('America/Denver')->addMonths(3)->format('Y-m');
        $this->post(route('admin.broadcast.newsletters.store'), [
            'title' => 'Unsubscribe check', 'body' => 'Test', 'issue_month' => $month,
        ])->assertRedirect();
        $issue = BroadcastNewsletter::where('title', 'Unsubscribe check')->firstOrFail();
        $this->post(route('admin.broadcast.newsletters.schedule', $issue))->assertRedirect();

        Carbon::setTestNow($issue->fresh()->scheduled_at->copy()->addSecond());
        $this->actingAs($user)->delete(route('frontend.broadcast.newsletter.unsubscribe'))
            ->assertRedirect();
        app(BroadcastDeliveryService::class)->run();
        Mail::assertNothingSent();
        $this->assertSame(BroadcastNewsletter::SENT, $issue->fresh()->status);
    }

}
