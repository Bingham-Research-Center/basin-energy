<?php

namespace App\Events;

use App\Models\TestSensorReading;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class TestSensorReadingCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reading;

    public function __construct(TestSensorReading $reading)
    {
        $this->reading = $reading;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('sensor-readings.' . $this->reading->device_id);
    }

    public function broadcastAs(): string
    {
        return 'sensor.reading.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->reading->id,
            'device_id' => $this->reading->device_id,
            'temperature' => $this->reading->temperature,
            'humidity' => $this->reading->humidity,
            'recorded_at' => optional($this->reading->recorded_at)->format('Y-m-d H:i:s'),
        ];
    }
}