<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class UserRegistrationAlertCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(private array $adminIds)
    {
    }

    public function broadcastOn(): array
    {
        return array_map(
            static fn ($adminId) => new PrivateChannel('admin.registrations.'.$adminId),
            $this->adminIds
        );
    }

    public function broadcastAs(): string
    {
        return 'user.registration.created';
    }

    public function broadcastWith(): array
    {
        // The event contains no name, email address or other user data.
        // The notification bell reloads its own authenticated feed.
        return ['refresh' => true];
    }
}
