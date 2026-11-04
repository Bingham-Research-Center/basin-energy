<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CarbonMapperObservation;
use App\Models\CarbonMapperPlumeOutline;
use Illuminate\Support\Facades\Http;
class ImportCarbonMapperPlumeOutlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
protected $signature = 'carbon-mapper:import-plume-outlines
                        {--limit=20 : Maximum number of observations to process}';
    /**
     * The console command description.
     *
     * @var string
     */
protected $description = 'Download and permanently save Carbon Mapper methane plume outlines.';
    /**
     * Execute the console command.
     */
public function handle()
{
    $limit = max(1, (int) $this->option('limit'));

    $observations = CarbonMapperObservation::query()
        ->where('gas', 'CH4')
        ->whereNotIn(
            'observation_id',
            CarbonMapperPlumeOutline::query()->select('observation_id')
        )
        ->limit($limit)
        ->get();

$this->info("Found {$observations->count()} observations missing saved plume outlines.");

$saved = 0;
$failed = 0;

foreach ($observations as $observation) {
    $observationId = $observation->observation_id;

    $this->line("Downloading plume: {$observationId}");

    try {
        $geoJson = $this->downloadPlumeOutline($observationId);

        if ($geoJson === null) {
            $this->warn("Outline unavailable: {$observationId}");
            $failed++;
        } else {
            CarbonMapperPlumeOutline::updateOrCreate(
                ['observation_id' => $observationId],
                [
                    'geojson' => $geoJson,
                    'last_fetched_at' => now(),
                ]
            );

            $this->info("Saved: {$observationId}");
            $saved++;
        }
    } catch (\Throwable $e) {
        $this->error("Failed: {$observationId} - {$e->getMessage()}");
        $failed++;
    }

    // Avoid sending requests too quickly.
    usleep(500000);
}

$this->info("Import complete. Saved: {$saved}, Unavailable/failed: {$failed}");

return self::SUCCESS;
}
private function downloadPlumeOutline(string $observationId): ?array
{
    $token = config('services.carbon_mapper.token');

    if (! is_string($token) || trim($token) === '') {
        $this->error('Carbon Mapper API token is missing.');
        return null;
    }

    $collections = [
        'l3a-vis-ch4',
        'l3a-vis-ch4-mf-v002',
    ];

    foreach ($collections as $collection) {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->get(
                'https://api.carbonmapper.org/api/v1/stac/collections/'
                . $collection
                . '/items/'
                . rawurlencode($observationId)
            );

if ($response->status() === 404) {
    $this->warn("STAC collection {$collection}: HTTP 404");
    continue;
}

$this->line("STAC collection {$collection}: HTTP {$response->status()}");
        if (! $response->successful()) {
            return null;
        }

$assets = $response->json('assets', []);

$assetUrl = $assets['plume-outline.geojson']['href'] ?? null;
$this->line(
    'Available STAC assets: ' .
    implode(', ', array_keys($response->json('assets', [])))
);

$this->line(
    'Plume outline asset: ' .
    (is_string($assetUrl) ? 'Found' : 'Missing')
);

        if (
            ! is_string($assetUrl) ||
            ! str_starts_with(
                $assetUrl,
                'https://api.carbonmapper.org/api/v1/catalog/asset/'
            )
        ) {
            return null;
        }

        $outlineResponse = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->get($assetUrl);

        if (! $outlineResponse->successful()) {
            return null;
        }

        $geoJson = $outlineResponse->json();

        if (
            ! is_array($geoJson) ||
            ($geoJson['type'] ?? null) !== 'FeatureCollection' ||
            ! is_array($geoJson['features'] ?? null)
        ) {
            return null;
        }

        return $geoJson;
    }

    return null;
}
}

