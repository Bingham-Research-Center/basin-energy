<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmissionTrend;

class ImportEmissionTrends extends Command
{
    protected $signature = 'emissions:import {--path=storage/app/emissions.csv}';
    protected $description = 'Import emission trends CSV into the database';

    public function handle(): int
    {
        $path = base_path($this->option('path'));

        if (!file_exists($path)) {
            $this->error("CSV not found: {$path}");
            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        // Build column index map (ignore "Unnamed: 0")
        $map = [];
        foreach ($header as $i => $column) {
            $column = trim($column);
            if ($column === '' || $column === 'Unnamed: 0') {
                continue;
            }
            $map[$column] = $i;
        }

        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (!isset($map['year'])) {
                continue;
            }

            $year = $row[$map['year']] ?? null;
            if (!$year) {
                continue;
            }

            // helper: empty string → null
            $val = function (string $key) use ($map, $row) {
                return isset($map[$key]) && $row[$map[$key]] !== ''
                    ? $row[$map[$key]]
                    : null;
            };

            EmissionTrend::updateOrCreate(
                ['year' => (int) $year],
                [
                    'basinwide_ch4_emiss_mg_hr' => $val('basinwide_ch4_emiss_Mg_hr'),
                    'oil_million_bbls'          => $val('oil_million_bbls'),
                    'gas_million_bbleq'         => $val('gas_million_bbleq'),
                    'energy_million_bbleq'      => $val('energy_million_bbleq'),
                    'oil_gas_ratio'             => $val('oil_gas_ratio'),
                    'winterozone_exceed_num'    => $val('winterozone_exceed_num'),
                    'producing_wells'           => $val('producing_wells'),
                    'newwells'                  => $val('newwells'),
                    'prodfromhighwells_thousbbleq_mnth' => $val('prodfromhighwells_thousbbleq_mnth'),
                    'prodfromlowwells_thousbbleq_mnth'  => $val('prodfromlowwells_thousbbleq_mnth'),
                    'prodfromoilwells_thousbbleq_mnth'  => $val('prodfromoilwells_thousbbleq_mnth'),
                    'prodfromgaswells_thousbbleq_mnth'  => $val('prodfromgaswells_thousbbleq_mnth'),
                    'pcnt_prodfromhighwells'    => $val('pcnt_prodfromhighwells'),
                    'gaswells'                  => $val('gaswells'),
                    'oilwells'                  => $val('oilwells'),
                    'new_gaswells'              => $val('new_gaswells'),
                    'new_oilwells'              => $val('new_oilwells'),
                    'highprodwells'             => $val('highprodwells'),
                    'lowprodwells'              => $val('lowprodwells'),
                    'emissinens_totenergy'      => $val('emissinens_totenergy'),
                    'emissinens_gas'            => $val('emissinens_gas'),
                    'emisco2eq20_millnmg'       => $val('emisCO2eq20_millnMg'),
                    'utahgasprice_dollpermcf'   => $val('Utahgasprice_dollperMCF'),
                    'utahcrudeprice_dollperbbl' => $val('Utahcrudeprice_dollperBBL'),
                ]
            );

            $count++;
        }

        fclose($handle);

        $this->info("Imported/updated {$count} rows.");
        return self::SUCCESS;
    }
}
