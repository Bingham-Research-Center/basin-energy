<?php

namespace App\Jobs;

use App\Services\BroadcastDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchBroadcastAnnouncement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(public int $announcementId)
    {
    }

    public function handle(BroadcastDeliveryService $service): void
    {
        $service->queueAnnouncement($this->announcementId);
    }
}
