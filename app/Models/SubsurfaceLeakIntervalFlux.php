<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubsurfaceLeakIntervalFlux extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'compound_fluxes' => 'array',
    ];
}
