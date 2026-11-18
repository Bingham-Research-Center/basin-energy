<?php

namespace App\Events;

use App\Domains\Auth\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewsletterSubscriptionChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $subscriber;

    public function __construct(User $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    public function broadcastOn(): array
    {
        return User::query()->where('type', User::TYPE_ADMIN)
            ->where('active', true)->pluck('id')
            ->map(fn ($id) => new PrivateChannel('admin.newsletters.'.$id))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'newsletter.subscription.created';
    }

    public function broadcastWith(): array
    {
        // The real-time message carries only a hint; the browser fetches the
        // details using its own authenticated, administrator-only endpoint.
        return ['refresh' => true];
    }
}
