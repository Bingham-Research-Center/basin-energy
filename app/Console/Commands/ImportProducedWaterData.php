<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\ProducedWaterFlux;
use App\Models\ProducedWaterChemistry;
use Carbon\Carbon;

class ImportProducedWaterData extends Command
{
    protected $signature = 'produced-water:import';

    protected $description = 'Import produced water flux and chemistry CSV files';

    public function handle(): int
    {
        $this->info('Starting Produced Water data import...');

        $this->importFluxData(false);
        $this->importFluxData(true);
        $this->importChemistryData();

        $this->info('Produced Water import complete.');

        return Command::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | Flux data importer
    |--------------------------------------------------------------------------
    */
    protected function importFluxData(bool $windCorrected): void
    {
        $file = $windCorrected
            ? 'data/produced_water/Prodwat_fluxchamber_windcorr.csv'
            : 'data/produced_water/Prodwat_fluxchamber.csv';

        if (!Storage::exists($file)) {
            $this->error("Missing file: {$file}");
            return;
        }

        $this->info('Importing flux data: ' . basename($file));

        $rows = array_map('str_getcsv', file(Storage::path($file)));
        $header = array_shift($rows);

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        $count = 0;

        foreach ($rows as $row) {
            $data = array_combine($header, $row);

            ProducedWaterFlux::updateOrCreate(
                [
                    'unique_id' => $data['UniqueID'],
                    'wind_corrected' => $windCorrected,
                ],
                [
                    'can_set' => $this->num($data['Can Set'] ?? null),
                    'datetime_start' => $this->parseDateTime($data['Date and Time Start'] ?? null),
                    'datetime_end' => $this->parseDateTime($data['Date and Time End'] ?? null),
                    'duration_min' => $this->num($data['Duration'] ?? null),

                    'ch4_flux' => $this->num($data['CH4 Flux'] ?? null),
                    'co2_flux' => $this->num($data['CO2 Flux'] ?? null),

                    'tnmhc' => $this->num($data['TNMHC (no MeOH)'] ?? null),
                    'alkanes' => $this->num($data['Alkanes'] ?? null),
                    'alkenes' => $this->num($data['Alkenes'] ?? null),
                    'aromatics' => $this->num($data['Aromatics'] ?? null),
                    'alcohols' => $this->num($data['Alcohols'] ?? null),
                    'carbonyls' => $this->num($data['Carbonyls'] ?? null),

                    'species_fluxes' => $this->extractSpecies($data),

                    'facility_id' => $data['Facility ID'] ?? null,
                    'pond_type' => $data['PondType'] ?? null,
                    'state' => $data['State'] ?? null,
                    'notes' => $data['NOTES'] ?? null,
                ]
            );

            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Imported {$count} flux rows (" . ($windCorrected ? 'wind-corrected' : 'raw') . ")");
    }

    /*
    |--------------------------------------------------------------------------
    | Chemistry importer
    |--------------------------------------------------------------------------
    */
    protected function importChemistryData(): void
    {
        $file = 'data/produced_water/Prodwat_fluxchamber_waterdata.csv';

        if (!Storage::exists($file)) {
            $this->error("Missing file: {$file}");
            return;
        }

        $this->info('Importing water chemistry data');

        $rows = array_map('str_getcsv', file(Storage::path($file)));
        $header = array_shift($rows);

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        $count = 0;

        foreach ($rows as $row) {
            $data = array_combine($header, $row);

            ProducedWaterChemistry::updateOrCreate(
                [
                    'unique_id_water' => $data['UniqueID_w'],
                ],
                [
                    'unique_id_flux' => $data['UniqueID'] ?? null,
                    'sample_date' => $this->parseDate($data['Date'] ?? null),
                    'sample_time' => $this->parseTime($data['Time'] ?? null),

                    'temperature' => $this->num($data['Temperature'] ?? null),
                    'ph' => $this->num($data['pH'] ?? null),
                    'tds' => $this->num($data['TDS'] ?? null),
                    'turbidity' => $this->num($data['Turbidity'] ?? null),

                    'toc' => $this->num($data['TOC=TC-IC'] ?? null),
                    'tc' => $this->num($data['TC'] ?? null),
                    'ic' => $this->num($data['IC'] ?? null),

                    'orp' => $this->num($data['ORP'] ?? null),
                    'odo' => $this->num($data['ODO'] ?? null),
                    'mpn' => $this->num($data['MPN'] ?? null),

                    'dissolved_compounds' => $this->extractDissolvedCompounds($data),

                    'facility_id' => $data['Facility ID'] ?? null,
                    'state' => $data['State'] ?? null,
                    'notes' => $data['NOTES'] ?? null,
                ]
            );

            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Imported {$count} water chemistry rows");
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    protected function num($value)
    {
        if ($value === null || $value === '' || $value === '--') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    protected function parseDate($value)
    {
        if (!$value || $value === '--') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function parseTime($value)
    {
        if (!$value || $value === '--') {
            return null;
        }

        try {
            return Carbon::parse($value)->toTimeString();
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function parseDateTime($value)
    {
        if (!$value || $value === '--') {
            return null;
        }

        $value = trim($value);

        $formats = [
            'n/j/y H:i',
            'n/j/Y H:i',
            'm/d/y H:i',
            'm/d/Y H:i',
            'Y-m-d H:i:s',
        ];

        foreach ($formats as $format) {
            try {
                return \Carbon\Carbon::createFromFormat($format, $value);
            } catch (\Exception $e) {
                // try next format
            }
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }


    protected function extractSpecies(array $row): array
    {
        $excludePrefixes = [
            'Date', 'Duration', 'In', 'Out', 'Ambient',
            'Solar', 'pressure', 'Wind', 'Soil',
            'Facility', 'Pond', 'State', 'NOTES',
        ];

        $excludeExact = [
            'UniqueID',
            'Can Set',
            'CH4 Flux',
            'CO2 Flux',
            'TNMHC (no MeOH)',
            'Alkanes',
            'Alkenes',
            'Aromatics',
            'Alcohols',
            'Carbonyls',
        ];

        $species = [];

        foreach ($row as $key => $value) {
            if (in_array($key, $excludeExact, true)) {
                continue;
            }

            foreach ($excludePrefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    continue 2;
                }
            }

            if ($value === '' || $value === '--' || !is_numeric($value)) {
                continue;
            }

            $species[$key] = (float) $value;
        }

        return $species;
    }

    protected function extractDissolvedCompounds(array $row): array
    {
        $skip = [
            'UniqueID', 'UniqueID_w', 'Date', 'Time',
            'Temperature', 'pH', 'TDS', 'Turbidity',
            'TOC=TC-IC', 'TC', 'IC', 'ORP', 'ODO', 'MPN',
            'Facility ID', 'State', 'NOTES'
        ];

        $out = [];

        foreach ($row as $key => $value) {
            if (in_array($key, $skip, true)) {
                continue;
            }

            if (is_numeric($value)) {
                $out[$key] = (float) $value;
            }
        }

        return $out;
    }
}
