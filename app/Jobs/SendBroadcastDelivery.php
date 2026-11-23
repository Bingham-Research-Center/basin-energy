<?php

namespace App\Jobs;

use App\Services\BroadcastDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBroadcastDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 60;

    public function __construct(public string $kind, public int $deliveryId)
    {
    }

    public function handle(BroadcastDeliveryService $service): void
    {
        $service->sendDelivery($this->kind, $this->deliveryId);
    }
}
