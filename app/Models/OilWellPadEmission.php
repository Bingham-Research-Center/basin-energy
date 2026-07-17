<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OilWellPadEmission extends Model
{
    protected $fillable = [
        'sample_number',
        'sample_type',
        'methane_g_hr',
        'carbon_dioxide_g_hr',
        'tnmhc_g_hr',
        'alkanes_g_hr',
        'alkenes_alkyne_g_hr',
        'aromatics_g_hr',
        'alcohols_g_hr',
        'carbonyls_g_hr',
        'total_organic_compounds_g_hr',
        'notes',
        'compound_emissions',
        'source_file',
    ];

    protected $casts = [
        'methane_g_hr' => 'float',
        'carbon_dioxide_g_hr' => 'float',
        'tnmhc_g_hr' => 'float',
        'alkanes_g_hr' => 'float',
        'alkenes_alkyne_g_hr' => 'float',
        'aromatics_g_hr' => 'float',
        'alcohols_g_hr' => 'float',
        'carbonyls_g_hr' => 'float',
        'total_organic_compounds_g_hr' => 'float',
        'compound_emissions' => 'array',
    ];
}
