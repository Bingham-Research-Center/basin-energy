<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubsurfaceLeakChamberReading extends Model
{
    protected $guarded = [];

    protected $casts = [
        'measured_at' => 'datetime',
    ];
}
