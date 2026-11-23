<?php

namespace App\Console\Commands;

use App\Services\BroadcastDeliveryService;
use Illuminate\Console\Command;

class DeliverBroadcasts extends Command
{
    protected $signature = 'broadcast:deliver';
    protected $description = 'Process scheduled monthly newsletters and role-targeted announcements';

    public function handle(BroadcastDeliveryService $service): int
    {
        $service->run();
        $this->info('Broadcast delivery batch completed.');

        return self::SUCCESS;
    }
}
