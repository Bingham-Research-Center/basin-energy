<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarbonMapperSourceMeasurement extends Model
{
    protected $table = 'carbon_mapper_source_measurements';

    protected $fillable = [
        'plume_id',
        'source_name',
        'gas',
        'emission_rate',
        'emission_uncertainty',
        'emission_unit',
        'persistence',
        'plume_count',
        'detection_date_count',
        'observation_scene_count',
        'observation_date_count',
        'last_fetched_at',
    ];

    protected $casts = [
        'emission_rate' => 'float',
        'emission_uncertainty' => 'float',
        'persistence' => 'float',
        'plume_count' => 'integer',
        'detection_date_count' => 'integer',
        'observation_scene_count' => 'integer',
        'observation_date_count' => 'integer',
        'last_fetched_at' => 'datetime',
    ];
}
