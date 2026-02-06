<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProducedWaterChemistry extends Model
{
    use HasFactory;

    protected $table = 'produced_water_chemistry';

    protected $fillable = [
        'unique_id_flux',
        'unique_id_water',

        'sample_date',
        'sample_time',

        'temperature',
        'ph',
        'tds',
        'turbidity',

        'toc',
        'tc',
        'ic',

        'orp',
        'odo',
        'mpn',

        'dissolved_compounds',

        'facility_id',
        'state',

        'notes',
    ];

    protected $casts = [
        'sample_date' => 'date',
        'sample_time' => 'datetime:H:i:s',

        'temperature' => 'decimal:2',
        'ph'          => 'decimal:2',
        'tds'         => 'decimal:2',
        'turbidity'   => 'decimal:2',

        'toc' => 'decimal:2',
        'tc'  => 'decimal:2',
        'ic'  => 'decimal:2',

        'orp' => 'decimal:2',
        'odo' => 'decimal:2',
        'mpn' => 'decimal:2',

        'dissolved_compounds' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships (logical, not FK enforced)
    |--------------------------------------------------------------------------
    */

    public function flux()
    {
        return $this->belongsTo(
            ProducedWaterFlux::class,
            'unique_id_flux',
            'unique_id'
        );
    }

    public function scopeState($query, string $state)
    {
        return $query->where('state', strtoupper($state));
    }
}