<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class EmissionTrend extends Model
{
    protected $fillable = [
        'year',
        'basinwide_ch4_emiss_mg_hr',
        'oil_million_bbls',
        'gas_million_bbleq',
        'energy_million_bbleq',
        'oil_gas_ratio',
        'winterozone_exceed_num',
        'producing_wells',
        'newwells',
        'prodfromhighwells_thousbbleq_mnth',
        'prodfromlowwells_thousbbleq_mnth',
        'prodfromoilwells_thousbbleq_mnth',
        'prodfromgaswells_thousbbleq_mnth',
        'pcnt_prodfromhighwells',
        'gaswells',
        'oilwells',
        'new_gaswells',
        'new_oilwells',
        'highprodwells',
        'lowprodwells',
        'emissinens_totenergy',
        'emissinens_gas',
        'emisco2eq20_millnmg',
        'utahgasprice_dollpermcf',
        'utahcrudeprice_dollperbbl',
    ];
}

