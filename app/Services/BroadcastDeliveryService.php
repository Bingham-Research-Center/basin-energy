<?php

namespace App\Services;

use App\Domains\Auth\Models\User;
use App\Jobs\DispatchBroadcastAnnouncement;
use App\Jobs\SendBroadcastDelivery;
use App\Mail\BroadcastAnnouncementMail;
use App\Mail\MonthlyNewsletterMail;
use App\Models\BroadcastAnnouncement;
use App\Models\BroadcastAnnouncementDelivery;
use App\Models\BroadcastNewsletter;
use App\Models\BroadcastNewsletterDelivery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BroadcastDeliveryService
{
    private const MAX_ATTEMPTS = 3;
    private const ENQUEUE_LIMIT = 500;

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

    /**
     * The scheduler queues jobs; it never makes SMTP calls.
     * Subsequent scheduler runs continue where the previous run stopped.
     */
    public function run(): void
    {
        BroadcastNewsletter::query()
            ->whereIn('status', [BroadcastNewsletter::SCHEDULED, BroadcastNewsletter::SENDING])
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->chunkById(50, function ($issues) {
                foreach ($issues as $issue) {
                    $this->prepareNewsletter($issue);
                    $this->queueNewsletter($issue->id);
                }
            });

        BroadcastAnnouncement::query()
            ->where('status', BroadcastAnnouncement::PUBLISHED)
            ->whereHas('deliveries', function ($query) {
                $query->where('status', 'pending')
                    ->orWhere(function ($retry) {
                        $retry->where('status', 'failed')
                            ->where('attempts', '<', self::MAX_ATTEMPTS);
                    });
            })
            ->orderBy('id')
            ->chunkById(50, function ($announcements) {
                foreach ($announcements as $announcement) {
                    $this->queueAnnouncement($announcement->id);
                }
            });
    }

    /** Called after publication commits; the scheduler is a safety net. */
    public function dispatchAnnouncement(int $announcementId): void
    {
        DispatchBroadcastAnnouncement::dispatch($announcementId)
            ->onConnection(config('broadcast.queue_connection', 'database'))
            ->onQueue(config('broadcast.queue_name', 'broadcast-mail'));
    }

    public function queueAnnouncement(int $announcementId): void
    {
        if (! BroadcastAnnouncement::whereKey($announcementId)
            ->where('status', BroadcastAnnouncement::PUBLISHED)->exists()) {
            return;
        }

        $this->queueDeliveries(BroadcastAnnouncementDelivery::class, 'announcement_id', $announcementId, 'announcement');
    }

    public function queueNewsletter(int $newsletterId): void
    {
        if (! BroadcastNewsletter::whereKey($newsletterId)
            ->where('status', BroadcastNewsletter::SENDING)->exists()) {
            return;
        }

        $this->queueDeliveries(BroadcastNewsletterDelivery::class, 'newsletter_id', $newsletterId, 'newsletter');
        $this->finalizeNewsletter($newsletterId);
    }

    private function queueDeliveries(string $model, string $key, int $parentId, string $kind): void
    {
        $rows = $model::where($key, $parentId)
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->where(function (Builder $query) {
                $query->where('status', 'pending')
                    ->orWhere(function (Builder $q) {
                        $q->where('status', 'failed')->where('attempts', 1)
                            ->where('updated_at', '<=', now()->subMinutes(2));
                    })
                    ->orWhere(function (Builder $q) {
                        $q->where('status', 'failed')->where('attempts', 2)
                            ->where('updated_at', '<=', now()->subMinutes(5));
                    });
            })->orderBy('id')->limit(self::ENQUEUE_LIMIT)->get();

        foreach ($rows as $row) {
            $claimed = $model::whereKey($row->id)
                ->where('status', $row->status)
                ->where('attempts', $row->attempts)
                ->update(['status' => 'queued']);

            if (! $claimed) {
                continue;
            }

            try {
                SendBroadcastDelivery::dispatch($kind, $row->id)
                    ->onConnection(config('broadcast.queue_connection', 'database'))
                    ->onQueue(config('broadcast.queue_name', 'broadcast-mail'));
            } catch (Throwable $exception) {
                // Enqueue errors are safe to retry; no SMTP call was made.
                $model::whereKey($row->id)->where('status', 'queued')
                    ->where('attempts', $row->attempts)
                    ->update([
                        'status' => $row->status,
                        'last_error' => mb_substr($exception->getMessage(), 0, 1000),
                    ]);
                Log::error('Broadcast queue dispatch failed', [
                    'kind' => $kind, 'delivery_id' => $row->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Exactly one worker may claim a delivery. An interrupted "sending"
     * record is intentionally not retried automatically: the provider
     * might already have accepted the message.
     */
    public function sendDelivery(string $kind, int $id): void
    {
        if (! in_array($kind, ['newsletter', 'announcement'], true)) {
            throw new \InvalidArgumentException('Unknown broadcast kind');
        }

        $model = $kind === 'newsletter'
            ? BroadcastNewsletterDelivery::class : BroadcastAnnouncementDelivery::class;
        $row = $model::find($id);

        if (! $row || $row->status !== 'queued') {
            return;
        }

        $claimed = $model::whereKey($id)->where('status', 'queued')
            ->where('attempts', $row->attempts)
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->update(['status' => 'sending', 'attempts' => $row->attempts + 1]);
        if (! $claimed) {
            return;
        }

        $row->refresh();
        $newsletterId = $kind === 'newsletter' ? (int) $row->newsletter_id : null;

        try {
            if ($kind === 'newsletter') {
                $newsletter = $row->newsletter;
                if (! $newsletter || $newsletter->status !== BroadcastNewsletter::SENDING) {
                    $row->update(['status' => 'skipped', 'last_error' => null]);
                    return;
                }

                // A subscriber may opt out after the audience was frozen.
                $eligible = User::whereKey($row->user_id)
                    ->where('type', User::TYPE_USER)->where('active', true)
                    ->whereNotNull('email_verified_at')
                    ->whereHas('newsletterSubscription', function (Builder $q) {
                        $q->whereNotNull('subscribed_at')->whereNull('unsubscribed_at');
                    })->exists();
                if (! $eligible) {
                    $row->update(['status' => 'skipped', 'last_error' => null]);
                    return;
                }

                Mail::to($row->email)->send(new MonthlyNewsletterMail($newsletter, $row));
            } else {
                $announcement = $row->announcement;
                if (! $announcement || $announcement->status !== BroadcastAnnouncement::PUBLISHED) {
                    $row->update(['status' => 'skipped', 'last_error' => null]);
                    return;
                }

                Mail::to($row->email)->send(new BroadcastAnnouncementMail($announcement));
            }

            $row->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
        } catch (Throwable $exception) {
            Log::error('Broadcast mail failed', [
                'kind' => $kind, 'delivery_id' => $id,
                'message' => $exception->getMessage(),
            ]);
            $row->update([
                'status' => 'failed',
                'last_error' => mb_substr($exception->getMessage(), 0, 1000),
            ]);
        } finally {
            if ($newsletterId !== null) {
                $this->finalizeNewsletter($newsletterId);
            }
        }
    }

    public function finalizeNewsletter(int $id): void
    {
        DB::transaction(function () use ($id) {
            $issue = BroadcastNewsletter::whereKey($id)->lockForUpdate()->first();
            if (! $issue || $issue->status !== BroadcastNewsletter::SENDING) {
                return;
            }

            $unfinished = $issue->deliveries()->where(function (Builder $query) {
                $query->whereIn('status', ['pending', 'queued', 'sending'])
                    ->orWhere(function (Builder $retry) {
                        $retry->where('status', 'failed')
                            ->where('attempts', '<', self::MAX_ATTEMPTS);
                    });
            })->exists();
            if ($unfinished) {
                return;
            }

            $withErrors = $issue->deliveries()
                ->whereIn('status', ['failed', 'skipped'])->exists();
            $issue->update([
                'status' => $withErrors ? BroadcastNewsletter::SENT_WITH_ERRORS : BroadcastNewsletter::SENT,
                'sent_at' => now(),
            ]);
        });
    }
}
