<?php

namespace App\Console\Commands;

use App\Models\CarbonMapperSourceMeasurement;
use App\Services\UintahBasinCoverage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class RefreshCarbonMapperSources extends Command
{
    protected $signature = 'carbon-mapper:refresh-sources';

    protected $description =
        'Manually refresh and permanently store Carbon Mapper source measurements';

    public function handle(UintahBasinCoverage $coverage): int
    {
        $this->info('Retrieving Carbon Mapper source measurements...');

        $token = config('services.carbon_mapper.token');

        if (! is_string($token) || trim($token) === '') {
            $this->error('Carbon Mapper API token is not configured.');
            return self::FAILURE;
        }

        $bounds = $coverage->bounds();

        $query = implode('&', array_map(
            fn ($value) => 'bbox=' . urlencode((string) $value),
            [
                $bounds['minLon'],
                $bounds['minLat'],
                $bounds['maxLon'],
                $bounds['maxLat'],
            ]
        ));

        $url = rtrim(
            config('services.carbon_mapper.base_url'),
            '/'
        ) . '/catalog/sources.geojson?' . $query;

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(60)
                ->get($url);

            if (! $response->successful()) {
                $this->error(
                    'Carbon Mapper API returned HTTP ' . $response->status()
                );

                return self::FAILURE;
            }

            $payload = $response->json();

            if (
                ! is_array($payload) ||
                ($payload['type'] ?? null) !== 'FeatureCollection' ||
                ! isset($payload['features']) ||
                ! is_array($payload['features'])
            ) {
                $this->error('Invalid Carbon Mapper API response.');
                return self::FAILURE;
            }

            $records = [];

            foreach ($payload['features'] as $feature) {
                if (! is_array($feature)) {
                    continue;
                }

                $properties = $feature['properties'] ?? [];
                $coordinates = $feature['geometry']['coordinates'] ?? [];

                if (($properties['gas'] ?? null) !== 'CH4') {
                    continue;
                }

                if (
                    ! isset($coordinates[0], $coordinates[1]) ||
                    ! is_numeric($coordinates[0]) ||
                    ! is_numeric($coordinates[1])
                ) {
                    continue;
                }

                if (! $coverage->contains(
                    (float) $coordinates[1],
                    (float) $coordinates[0]
                )) {
                    continue;
                }

                foreach ($properties['plume_ids'] ?? [] as $plumeId) {
                    if (! is_string($plumeId) || $plumeId === '') {
                        continue;
                    }

                    if (isset($records[$plumeId])) {
                        continue;
                    }

                    $numeric = fn ($value) =>
                        is_numeric($value) ? (float) $value : null;

                    $count = fn ($value) =>
                        is_numeric($value) ? (int) $value : null;

                    $records[$plumeId] = [
                        'plume_id' => $plumeId,
                        'source_name' => $properties['source_name'] ?? null,
                        'gas' => 'CH4',
                        'emission_rate' => $numeric(
                            $properties['emission_auto'] ?? null
                        ),
                        'emission_uncertainty' => $numeric(
                            $properties['emission_uncertainty_auto'] ?? null
                        ),
                        'emission_unit' => 'kg/hr',
                        'persistence' => $numeric(
                            $properties['persistence'] ?? null
                        ),
                        'plume_count' => $count(
                            $properties['plume_count'] ?? null
                        ),
                        'detection_date_count' => $count(
                            $properties['detection_date_count'] ?? null
                        ),
                        'observation_scene_count' => $count(
                            $properties['observation_scene_count'] ?? null
                        ),
                        'observation_date_count' => $count(
                            $properties['observation_date_count'] ?? null
                        ),
                        'last_fetched_at' => now(),
                    ];
                }
            }

            if (empty($records)) {
                $this->warn(
                    'No matching source measurements found. Existing data preserved.'
                );

                return self::FAILURE;
            }

            DB::transaction(function () use ($records) {
                foreach ($records as $record) {
                    CarbonMapperSourceMeasurement::updateOrCreate(
                        ['plume_id' => $record['plume_id']],
                        $record
                    );
                }
            });

            $this->info(
                'Successfully saved ' . count($records) .
                ' Carbon Mapper source measurements.'
            );

            return self::SUCCESS;

        } catch (Throwable $exception) {
            report($exception);

            $this->error(
                'Refresh failed. Previously saved measurements were preserved.'
            );

            return self::FAILURE;
        }
    }
}
