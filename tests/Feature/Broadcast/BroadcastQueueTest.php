<?php

namespace Tests\Feature\Broadcast;

use App\Domains\Auth\Models\User;
use App\Jobs\DispatchBroadcastAnnouncement;
use App\Jobs\SendBroadcastDelivery;
use App\Mail\BroadcastAnnouncementMail;
use App\Models\BroadcastAnnouncement;
use App\Models\BroadcastNewsletter;
use App\Models\NewsletterSubscription;
use App\Services\BroadcastDeliveryService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BroadcastQueueTest extends TestCase
{
    public function test_published_announcement_dispatches_jobs_and_duplicate_workers_do_not_resend(): void
    {
        Queue::fake();
        Mail::fake();
        $recipient = User::factory()->user()->create();
        $this->loginAsAdmin();

        $this->post(route('admin.broadcast.announcements.store'), [
            'title' => 'Queued update',
            'body' => 'Testing delivery',
            'audience' => 'all_users',
        ])->assertRedirect();

        $announcement = BroadcastAnnouncement::where('title', 'Queued update')->firstOrFail();
        $delivery = $announcement->deliveries()->where('user_id', $recipient->id)->firstOrFail();
        $this->assertSame('pending', $delivery->status);
        Queue::assertPushed(DispatchBroadcastAnnouncement::class, 1);
        Mail::assertNothingSent();

        $service = app(BroadcastDeliveryService::class);
        $service->queueAnnouncement($announcement->id);
        $service->queueAnnouncement($announcement->id);

        Queue::assertPushed(SendBroadcastDelivery::class, 1);
        $this->assertSame('queued', $delivery->fresh()->status);

        $service->sendDelivery('announcement', $delivery->id);
        $service->sendDelivery('announcement', $delivery->id);

        $this->assertSame('sent', $delivery->fresh()->status);
        $this->assertSame(1, (int) $delivery->fresh()->attempts);
        Mail::assertSent(BroadcastAnnouncementMail::class, 1);
    }

    public function test_newsletter_queues_only_after_due_date_and_unsubscribes_are_skipped(): void
    {
        Queue::fake();
        Mail::fake();
        $subscriber = User::factory()->user()->create();
        NewsletterSubscription::create([
            'user_id' => $subscriber->id, 'subscribed_at' => now(),
        ]);

        $issue = BroadcastNewsletter::create([
            'title' => 'Queue test', 'body' => 'News',
            'issue_month' => now('America/Denver')->startOfMonth()->toDateString(),
            'status' => BroadcastNewsletter::SCHEDULED,
            'scheduled_at' => now()->addMinute(),
        ]);

        $service = app(BroadcastDeliveryService::class);
        $service->run();
        Queue::assertNotPushed(SendBroadcastDelivery::class);

        $issue->update(['scheduled_at' => now()->subMinute()]);
        $service->run();
        Queue::assertPushed(SendBroadcastDelivery::class, 1);

        $delivery = $issue->deliveries()->where('user_id', $subscriber->id)->firstOrFail();
        $this->assertSame('queued', $delivery->status);

        $subscriber->newsletterSubscription()->update(['unsubscribed_at' => now()]);
        $service->sendDelivery('newsletter', $delivery->id);

        $this->assertSame('skipped', $delivery->fresh()->status);
        $this->assertSame(BroadcastNewsletter::SENT_WITH_ERRORS, $issue->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_broadcast_table_contains_accessible_progress_markup(): void
    {
        $html = view('backend.broadcast.partials.delivery-progress', [
            'sent' => 1, 'total' => 4, 'failed' => 1, 'skipped' => 0,
        ])->render();

        $this->assertStringContainsString('25%', $html);
        $this->assertStringContainsString('role="progressbar"', $html);
        $this->assertStringContainsString('1 / 4 sent', $html);
        $this->assertStringContainsString('1 failed', $html);
    }
}
