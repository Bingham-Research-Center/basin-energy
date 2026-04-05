<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestSensorReading extends Model
{
    protected $fillable = [
        'device_id',
        'temperature',
        'humidity',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];
}