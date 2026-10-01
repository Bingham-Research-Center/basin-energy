<?php

namespace App\Events;

use App\Models\ContactMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactMessageSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $contactMessage;

    public function __construct(ContactMessage $contactMessage)
    {
        $this->contactMessage = $contactMessage;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('admin.notifications.contacts');
    }

    public function broadcastAs(): string
    {
        return 'contact.message.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->contactMessage->id,
            'name' => $this->contactMessage->name,
            'email' => $this->contactMessage->email,
            'message' => \Illuminate\Support\Str::limit($this->contactMessage->message, 40),
            'created_at' => optional($this->contactMessage->created_at)->format('Y-m-d H:i:s'),
            'url' => route('admin.website-update.contact'),
        ];
    }
}