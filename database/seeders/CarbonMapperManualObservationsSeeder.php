<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarbonMapperObservation;

class CarbonMapperManualObservationsSeeder extends Seeder
{
    public function run(): void
    {
        CarbonMapperObservation::updateOrCreate(
            [
                'observation_id' => 'manual-co2-20230728-40086833',
                'gas' => 'CO2',
            ],
            [
                'latitude' => 40.0868333,
                'longitude' => -109.2851667,
                'observed_at' => '2023-07-28 21:43:40',
                'emission_rate' => null,
                'emission_unit' => null,
                'is_super_emitter' => false,
                'raw_payload' => [
                    'properties' => [
                        'cm:gas' => 'CO2',
                        'cm:sector' => 'Energy generation',
                        'cm:published_at' => '2024-11-15T16:31:37Z',
                        'source_emission_rate' => 376000,
                        'source_emission_uncertainty' => 203100,
                        'source_emission_unit' => 'kg/hr',
                        'manual_entry' => true,
                    ],
                ],
            ]
        );
    }
}
