<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProducedWaterFlux extends Model
{
    use HasFactory;

    protected $table = 'produced_water_fluxes';

    protected $fillable = [
        'unique_id',
        'can_set',
        'datetime_start',
        'datetime_end',
        'duration_min',

        'ch4_flux',
        'co2_flux',

        'tnmhc',
        'alkanes',
        'alkenes',
        'aromatics',
        'alcohols',
        'carbonyls',

        'species_fluxes',

        'facility_id',
        'pond_type',
        'state',

        'wind_corrected',
        'notes',
    ];

    protected $casts = [
        'datetime_start' => 'datetime',
        'datetime_end'   => 'datetime',

        'duration_min' => 'decimal:2',

        'ch4_flux' => 'decimal:4',
        'co2_flux' => 'decimal:4',
        'tnmhc'    => 'decimal:4',
        'alkanes'  => 'decimal:4',
        'alkenes'  => 'decimal:4',
        'aromatics'=> 'decimal:4',
        'alcohols' => 'decimal:4',
        'carbonyls'=> 'decimal:4',

        'species_fluxes' => 'array',

        'wind_corrected' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Query Scopes (optional but useful later)
    |--------------------------------------------------------------------------
    */

    public function scopeWindCorrected($query, bool $corrected = true)
    {
        return $query->where('wind_corrected', $corrected);
    }

    public function scopeState($query, string $state)
    {
        return $query->where('state', strtoupper($state));
    }
}
