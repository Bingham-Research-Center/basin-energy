<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarbonMapperObservation extends Model
{
    protected $table = 'carbon_mapper_observations';

    protected $fillable = [
        'observation_id',     // STAC feature.id
        'gas',                // CH4 / CO2
        'satellite',
        'instrument',
        'latitude',
        'longitude',
        'country',
        'region',
        'emission_rate',
        'emission_unit',
        'confidence',
        'is_super_emitter',
        'observed_at',
        'raw_payload',
    ];

    protected $casts = [
        'observed_at'       => 'datetime',
        'raw_payload'       => 'array',
        'is_super_emitter'  => 'boolean',
        'emission_rate'     => 'float',
        'confidence'        => 'float',
        'latitude'          => 'float',
        'longitude'         => 'float',
    ];

    public $timestamps = true;

    /**
     * Scope: methane only
     */
    public function scopeMethane($query)
    {
        return $query->where('gas', 'CH4');
    }

    /**
     * Scope: bounding box filter
     */
    public function scopeWithinBbox($query, $minLon, $minLat, $maxLon, $maxLat)
    {
        return $query
            ->whereBetween('longitude', [$minLon, $maxLon])
            ->whereBetween('latitude', [$minLat, $maxLat]);
    }
}
