<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarbonMapperPlumeOutline extends Model
{
    protected $table = 'carbon_mapper_plume_outlines';

    protected $fillable = [
        'observation_id',
        'geojson',
        'last_fetched_at',
    ];

    protected $casts = [
        'geojson' => 'array',
        'last_fetched_at' => 'datetime',
    ];
}
