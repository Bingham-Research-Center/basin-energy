<?php

namespace App\Services;

use App\Domains\Auth\Models\User;
use App\Mail\BroadcastAnnouncementMail;
use App\Mail\MonthlyNewsletterMail;
use App\Models\BroadcastAnnouncement;
use App\Models\BroadcastAnnouncementDelivery;
use App\Models\BroadcastNewsletter;
use App\Models\BroadcastNewsletterDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BroadcastDeliveryService
{
    private const MAX_ATTEMPTS = 3;

    /**
     * Freeze the newsletter audience at the beginning of its month-end send.
     * Only an explicitly scheduled newsletter can reach this point.
     */
    public function prepareNewsletter(BroadcastNewsletter $issue): void
    {
        DB::transaction(function () use ($issue) {
            $locked = BroadcastNewsletter::whereKey($issue->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== BroadcastNewsletter::SCHEDULED
                || $locked->scheduled_at === null
                || $locked->scheduled_at->isFuture()) {
                return;
            }

            User::query()->where('type', User::TYPE_USER)
                ->where('active', true)->whereNotNull('email')
                ->whereNotNull('email_verified_at')
                ->whereHas('newsletterSubscription', function ($query) {
                    $query->whereNotNull('subscribed_at')->whereNull('unsubscribed_at');
                })->select('id', 'email')->chunkById(200, function ($users) use ($locked) {
                    foreach ($users as $user) {
                        BroadcastNewsletterDelivery::firstOrCreate(
                            ['newsletter_id' => $locked->id, 'user_id' => $user->id],
                            ['email' => $user->email, 'status' => 'pending']
                        );
                    }
                });

            $locked->update(['status' => BroadcastNewsletter::SENDING]);
        });
    }

    public function run(): void
    {
        BroadcastNewsletter::query()
            ->whereIn('status', [BroadcastNewsletter::SCHEDULED, BroadcastNewsletter::SENDING])
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->get()
            ->each(function (BroadcastNewsletter $newsletter) {
                $this->prepareNewsletter($newsletter);
                $this->deliverNewsletter($newsletter);
            });

        BroadcastAnnouncement::query()
            ->whereHas('deliveries', function ($query) {
                $query->whereIn('status', ['pending', 'failed'])
                    ->where('attempts', '<', self::MAX_ATTEMPTS);
            })->orderBy('id')->get()
            ->each(fn (BroadcastAnnouncement $announcement) => $this->deliverAnnouncement($announcement));
    }

    private function deliverNewsletter(BroadcastNewsletter $newsletter): void
    {
        if ($newsletter->fresh()->status !== BroadcastNewsletter::SENDING) {
            return;
        }

        BroadcastNewsletterDelivery::query()->where('newsletter_id', $newsletter->id)
            ->whereIn('status', ['pending', 'failed'])
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->orderBy('id')->limit(100)->get()
            ->each(function (BroadcastNewsletterDelivery $delivery) use ($newsletter) {
                // A 'sending' row is never automatically retried after a process
                // crash: this avoids automatically emailing the same recipient twice.
                $claimed = BroadcastNewsletterDelivery::whereKey($delivery->id)
                    ->where('status', $delivery->status)
                    ->where('attempts', $delivery->attempts)
                    ->update(['status' => 'sending', 'attempts' => $delivery->attempts + 1]);
                if (! $claimed) {
                    return;
                }

                // A User can unsubscribe or become inactive after the
                // audience snapshot but before the email is actually sent.
                $eligible = User::query()->whereKey($delivery->user_id)
                    ->where('type', User::TYPE_USER)->where('active', true)
                    ->whereHas('newsletterSubscription', function ($q) {
                        $q->whereNotNull('subscribed_at')->whereNull('unsubscribed_at');
                    })->exists();
                if (! $eligible) {
                    $delivery->update(['status' => 'skipped', 'last_error' => null]);
                    return;
                }

                try {
                    Mail::to($delivery->email)->send(new MonthlyNewsletterMail($newsletter, $delivery));
                    $delivery->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
                } catch (Throwable $error) {
                    Log::error('Newsletter delivery failed', [
                        'newsletter_id' => $newsletter->id,
                        'delivery_id' => $delivery->id,
                        'message' => $error->getMessage(),
                    ]);
                    $delivery->update([
                        'status' => 'failed',
                        'last_error' => mb_substr($error->getMessage(), 0, 1000),
                    ]);
                }
            });

        $unfinished = $newsletter->deliveries()
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere('status', 'sending')
                    ->orWhere(function ($query) {
                        $query->where('status', 'failed')
                            ->where('attempts', '<', self::MAX_ATTEMPTS);
                    });
            })->exists();

        if (! $unfinished) {
            $hasFailures = $newsletter->deliveries()->where('status', 'failed')->exists();
            $newsletter->update([
                'status' => $hasFailures
                    ? BroadcastNewsletter::SENT_WITH_ERRORS : BroadcastNewsletter::SENT,
                'sent_at' => now(),
            ]);
        }
    }

    private function deliverAnnouncement(BroadcastAnnouncement $announcement): void
    {
        BroadcastAnnouncementDelivery::query()->where('announcement_id', $announcement->id)
            ->whereIn('status', ['pending', 'failed'])
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->orderBy('id')->limit(100)->get()
            ->each(function (BroadcastAnnouncementDelivery $delivery) use ($announcement) {
                $claimed = BroadcastAnnouncementDelivery::whereKey($delivery->id)
                    ->where('status', $delivery->status)
                    ->where('attempts', $delivery->attempts)
                    ->update(['status' => 'sending', 'attempts' => $delivery->attempts + 1]);
                if (! $claimed) {
                    return;
                }
                try {
                    Mail::to($delivery->email)->send(new BroadcastAnnouncementMail($announcement));
                    $delivery->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
                } catch (Throwable $error) {
                    Log::error('Announcement email delivery failed', [
                        'announcement_id' => $announcement->id,
                        'delivery_id' => $delivery->id,
                        'message' => $error->getMessage(),
                    ]);
                    $delivery->update([
                        'status' => 'failed',
                        'last_error' => mb_substr($error->getMessage(), 0, 1000),
                    ]);
                }
            });
    }
}
