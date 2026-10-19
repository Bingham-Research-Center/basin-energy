<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CarbonMapperSourceService
{
    /**
     * Return methane source information indexed by plume ID.
     *
     * Results are cached to avoid calling the Carbon Mapper API
     * every time someone loads or filters the map.
     */
    public function byPlumeId(UintahBasinCoverage $coverage): array
    {
        return Cache::remember(
            'carbon_mapper.uintah_basin.source_emissions.v1',
            now()->addMinutes(30),
            function () use ($coverage) {
                $bounds = $coverage->bounds();

                $values = [
                    $bounds['minLon'],
                    $bounds['minLat'],
                    $bounds['maxLon'],
                    $bounds['maxLat'],
                ];

                // Carbon Mapper expects bbox as four separate parameters.
                $query = implode('&', array_map(
                    fn ($value) => 'bbox=' . urlencode((string) $value),
                    $values
                ));

                $url = 'https://api.carbonmapper.org/api/v1/catalog/sources.geojson';

                $response = Http::withToken(
                    config('services.carbon_mapper.token')
                )
                    ->acceptJson()
                    ->timeout(20)
                    ->get($url . '?' . $query);

                if (! $response->successful()) {
                    report(new \RuntimeException(
                        'Carbon Mapper source API returned HTTP ' .
                        $response->status()
                    ));

                    return [];
                }

                $sourcesByPlumeId = [];

                foreach ($response->json('features', []) as $feature) {
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

                        // Keep the first source if an ID appears more than once.
                        if (isset($sourcesByPlumeId[$plumeId])) {
                            continue;
                        }

                        $sourcesByPlumeId[$plumeId] = [
                            'source_name' => $properties['source_name'] ?? null,
                            'emission_rate' => is_numeric(
                                $properties['emission_auto'] ?? null
                            )
                                ? (float) $properties['emission_auto']
                                : null,
                            'emission_uncertainty' => is_numeric(
                                $properties['emission_uncertainty_auto'] ?? null
                            )
                                ? (float) $properties['emission_uncertainty_auto']
                                : null,
                            'sector' => $properties['sector'] ?? null,
                            'persistence' => is_numeric(
                                $properties['persistence'] ?? null
                            )
                                ? (float) $properties['persistence']
                                : null,
                        ];
                    }
                }

                return $sourcesByPlumeId;
            }
        );
    }
}
