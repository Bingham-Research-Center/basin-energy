<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubsurfaceLeakSurvey extends Model
{
    protected $guarded = [];

    protected $casts = [
        'flux_sample_date' => 'date',
        'soil_gas_sample_date' => 'date',
        'compound_fluxes' => 'array',
    ];
}
