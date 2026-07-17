<?php

namespace App\Console\Commands;

use App\Models\SubsurfaceLeakChamberReading;
use App\Models\SubsurfaceLeakIntervalFlux;
use App\Models\SubsurfaceLeakSurvey;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportSubsurfaceLeakData extends Command
{
    protected $signature = 'data:import-subsurface-leaks {directory} {--fresh : Delete existing imported records first}';
    protected $description = 'Import subsurface natural-gas leak CSV datasets';

    private const CHAMBER_FILES = [
        'GCW_mar2018.csv' => ['GCW', 'Gulf Coast storage well'],
        'RMW1_Jan_Nov_2018.csv' => ['RMW1', 'Rocky Mountain storage well'],
        'RMW2_Jan2018.csv' => ['RMW2', 'Rocky Mountain storage well'],
        'UPW1_Sep2017_Aug2018.csv' => ['UPW1', 'Utah production well'],
        'UPW2_Sep2017_Aug2018.csv' => ['UPW2', 'Utah production well'],
        'UUD1_Jul2018.csv' => ['UUD1', 'Undisturbed soil near UPW1'],
        'UUD2_Aug2018.csv' => ['UUD2', 'Undisturbed soil near UPW2'],
    ];

    public function handle(): int
    {
        $directory = rtrim($this->argument('directory'), DIRECTORY_SEPARATOR);

        if (! is_dir($directory)) {
            $this->error("Directory not found: {$directory}");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            SubsurfaceLeakSurvey::truncate();
            SubsurfaceLeakIntervalFlux::truncate();
            SubsurfaceLeakChamberReading::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->importSurvey($directory.'/Final_SoilEmissDBase_anym_July2017.csv');
        $this->importIntervals($directory.'/15-min_flux_data.csv');

        foreach (self::CHAMBER_FILES as $file => [$site, $type]) {
            $this->importChambers($directory.'/'.$file, $site, $type);
        }

        $this->info('Subsurface natural-gas leak datasets imported successfully.');
        return self::SUCCESS;
    }

    private function importSurvey(string $path): void
    {
        $compoundStart = 'Ethane';
        $rows = [];
        $count = 0;

        foreach ($this->csvRows($path) as $row) {
            $compounds = $this->sliceFrom($row, $compoundStart);
            $rows[] = [
                'flux_sample_date' => $this->date($row['FluxSampDate'] ?? null),
                'soil_gas_sample_date' => $this->date($row['SoilGasSampDate'] ?? null),
                'sample_month' => $this->text($row['SampMonth'] ?? null),
                'notes' => $this->text($row['Notes'] ?? null),
                'well_id' => $this->text($row['Well_ID'] ?? null),
                'completion_decade' => $this->text($row['CompletionDecade'] ?? null),
                'well_type' => $this->text($row['WellType'] ?? null),
                'well_status' => $this->text($row['WellStatus'] ?? null),
                'latitude' => $this->number($row['latitude'] ?? null),
                'longitude' => $this->number($row['longitude'] ?? null),
                'flux_distance_m' => $this->number($row['FluxDist'] ?? null),
                'flux_direction' => $this->text($row['FluxDir'] ?? null),
                'soil_gas_distance_m' => $this->number($row['SoilGasDist'] ?? null),
                'soil_gas_direction' => $this->text($row['SoilGasDir'] ?? null),
                'total_combustible_soil_gas' => $this->number($row['TotCombustSoilGas'] ?? null),
                'flux_start' => $this->time($row['FluxStart'] ?? null),
                'flux_end' => $this->time($row['FluxEnd'] ?? null),
                'flux_duration_min' => $this->number($row['FluxDur'] ?? null),
                'ch4_flux' => $this->number($row['CH4 Flux'] ?? null),
                'co2_flux' => $this->number($row['CO2 Flux'] ?? null),
                'tnmhc_flux' => $this->number($row['TNMHC'] ?? null),
                'alkanes_flux' => $this->number($row['Alkanes'] ?? null),
                'alkenes_flux' => $this->number($row['Alkenes'] ?? null),
                'aromatics_flux' => $this->number($row['Aromatics'] ?? null),
                'ambient_temp_c' => $this->number($row['AmbientTemp'] ?? null),
                'relative_humidity_pct' => $this->number($row['Amb RH'] ?? null),
                'dewpoint_c' => $this->number($row['Dewpoint'] ?? null),
                'solar_radiation_w_m2' => $this->number($row['Solar Rad'] ?? null),
                'pressure_mbar' => $this->number($row['pressure'] ?? null),
                'wind_speed_m_s' => $this->number($row['Wind speed'] ?? null),
                'wind_direction_deg' => $this->number($row['Wind Dir'] ?? null),
                'wind_direction_sd_deg' => $this->number($row['StDevWindDir'] ?? null),
                'soil_water_content' => $this->number($row['SoilH2OCntnt'] ?? null),
                'soil_temp_c' => $this->number($row['SoilTemp'] ?? null),
                'compound_fluxes' => json_encode($compounds, JSON_INVALID_UTF8_SUBSTITUTE),
                'source_file' => basename($path),
                'created_at' => now(), 'updated_at' => now(),
            ];

            if (count($rows) === 500) {
                SubsurfaceLeakSurvey::insert($rows); $count += count($rows); $rows = [];
            }
        }
        if ($rows) { SubsurfaceLeakSurvey::insert($rows); $count += count($rows); }
        $this->info("Survey: {$count} rows");
    }

    private function importIntervals(string $path): void
    {
        $rows = [];
        $count = 0;
        foreach ($this->csvRows($path) as $row) {
            $compounds = $this->sliceFrom($row, 'Ethane');
            $rows[] = [
                'facility' => $this->text($row['Facility'] ?? null),
                'well_id' => $this->text($row['Well ID'] ?? null),
                'paper_id' => $this->text($row['ID_paper'] ?? null),
                'distance_from_wellhead_m' => $this->number($row['Dist_wellhead'] ?? null),
                'direction_from_wellhead' => $this->text($row['Dir_wellhead'] ?? null),
                'started_at' => $this->dateTime($row['Start_Time'] ?? null),
                'ended_at' => $this->dateTime($row['End_Time'] ?? null),
                'duration_min' => $this->number($row['Duration'] ?? null),
                'ch4_flux' => $this->number($row['CH4'] ?? null),
                'ch4_confidence_95' => $this->number($row['CH4_95%conf'] ?? null),
                'co2_flux' => $this->number($row['CO2'] ?? null),
                'co2_confidence_95' => $this->number($row['CO2_95%conf'] ?? null),
                'soil_water_pct' => $this->number($row['Soil_H2O'] ?? null),
                'soil_temp_c' => $this->number($row['Soil_Temp'] ?? null),
                'air_temp_c' => $this->number($row['Air_Temp'] ?? null),
                'relative_humidity_pct' => $this->number($row['Rel_Humid'] ?? null),
                'pressure_mbar' => $this->number($row['Baro_Press'] ?? null),
                'solar_radiation_w_m2' => $this->number($row['Solar_Rad'] ?? null),
                'wind_speed_m_s' => $this->number($row['Wind_Spd'] ?? null),
                'wind_direction_deg' => $this->number($row['Wind_Dir'] ?? null),
                'tnmhc_flux' => $this->number($row['TNMHC'] ?? null),
                'alkanes_flux' => $this->number($row['Alkanes'] ?? null),
                'alkenes_flux' => $this->number($row['Alkenes'] ?? null),
                'aromatics_flux' => $this->number($row['Aromatics'] ?? null),
                'alcohols_flux' => $this->number($row['Alcohols'] ?? null),
                'compound_fluxes' => json_encode($compounds, JSON_INVALID_UTF8_SUBSTITUTE),
                'source_file' => basename($path),
                'created_at' => now(), 'updated_at' => now(),
            ];
            if (count($rows) === 500) { SubsurfaceLeakIntervalFlux::insert($rows); $count += count($rows); $rows = []; }
        }
        if ($rows) { SubsurfaceLeakIntervalFlux::insert($rows); $count += count($rows); }
        $this->info("15-minute flux: {$count} rows");
    }

    private function importChambers(string $path, string $site, string $type): void
    {
        if (! is_file($path)) { $this->warn("Skipped missing file: {$path}"); return; }
        $rows = [];
        $count = 0;
        foreach ($this->csvRows($path) as $row) {
            $measuredAt = $this->dateTime($row['Time'] ?? null);
            if (! $measuredAt) { continue; }
            $item = [
                'site_code' => $site, 'site_type' => $type, 'measured_at' => $measuredAt,
                'air_temp_c' => $this->number($row['Air_Temp'] ?? null),
                'relative_humidity_pct' => $this->number($row['Rel_Humid'] ?? null),
                'solar_radiation_w_m2' => $this->number($row['Solar_Rad'] ?? null),
                'pressure_mbar' => $this->number($row['Baro_Press'] ?? null),
                'wind_speed_m_s' => $this->number($row['Wind_Spd'] ?? null),
                'wind_direction_deg' => $this->number($row['Wind_Dir'] ?? null),
                'soil_water_pct' => $this->number($row['Soil_H2O'] ?? null),
                'soil_temp_c' => $this->number($row['Soil_Temp'] ?? null),
                'source_file' => basename($path), 'created_at' => now(), 'updated_at' => now(),
            ];
            for ($i = 1; $i <= 6; $i++) {
                $item["chm{$i}_ch4"] = $this->number($row["Chm{$i}_CH4"] ?? null);
                $item["chm{$i}_co2"] = $this->number($row["Chm{$i}_CO2"] ?? null);
            }
            $rows[] = $item;
            if (count($rows) === 1000) {
                SubsurfaceLeakChamberReading::upsert($rows, ['site_code', 'measured_at']);
                $count += count($rows); $rows = [];
            }
        }
        if ($rows) { SubsurfaceLeakChamberReading::upsert($rows, ['site_code', 'measured_at']); $count += count($rows); }
        $this->info("{$site}: {$count} rows");
    }

    private function csvRows(string $path): iterable
    {
        if (! is_file($path)) { throw new \RuntimeException("Missing CSV: {$path}"); }
        $handle = fopen($path, 'rb');
        $headers = fgetcsv($handle);
        $headers = array_map(fn ($h) => trim((string) $h), $headers ?: []);
        while (($values = fgetcsv($handle)) !== false) {
            $values = array_pad($values, count($headers), null);
            $row = array_combine($headers, array_slice($values, 0, count($headers)));
            yield array_filter($row, fn ($v, $k) => ! Str::startsWith($k, 'Unnamed:'), ARRAY_FILTER_USE_BOTH);
        }
        fclose($handle);
    }

    private function sliceFrom(array $row, string $start): array
    {
        $keys = array_keys($row); $index = array_search($start, $keys, true);
        if ($index === false) return [];
        $out = [];
        foreach (array_slice($row, $index, null, true) as $key => $value) {
            $number = $this->number($value);
            if ($number !== null) $out[Str::snake(str_replace(['/', ',', '%'], ['_', '_', 'pct'], $key))] = $number;
        }
        return $out;
    }

    private function number($value): ?float
    {
        if ($value === null) return null;
        $value = trim((string) $value);
        if ($value === '' || in_array(strtolower($value), ['na', 'nan', 'null', 'n/a'], true)) return null;
        $value = str_replace(',', '', $value);
        return is_numeric($value) ? (float) $value : null;
    }

    private function text($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function date($value): ?string
    {
        try { return $this->text($value) ? Carbon::parse($value)->toDateString() : null; } catch (\Throwable) { return null; }
    }

    private function dateTime($value): ?string
    {
        try { return $this->text($value) ? Carbon::parse($value)->format('Y-m-d H:i:s') : null; } catch (\Throwable) { return null; }
    }

    private function time($value): ?string
    {
        try { return $this->text($value) ? Carbon::parse($value)->format('H:i:s') : null; } catch (\Throwable) { return null; }
    }
}
