<?php

namespace App\Console\Commands;

use App\Models\CarbonMapperObservation;
use App\Services\CarbonMapperStacService;
use App\Services\UintahBasinCoverage;
use Illuminate\Console\Command;

class ImportCarbonMapper extends Command
{
    protected $signature = 'carbon-mapper:import';

    protected $description =
        'Import Carbon Mapper methane observations within Uintah Basin coverage';

    public function handle(
        CarbonMapperStacService $stac,
        UintahBasinCoverage $coverage
    ): int {
        $this->info('Importing Carbon Mapper methane observations...');

        $bounds = $coverage->bounds();

        $response = $stac->search([
            'collections' => ['l2c-ch4'],
            'bbox' => [
                $bounds['minLon'],
                $bounds['minLat'],
                $bounds['maxLon'],
                $bounds['maxLat'],
            ],
            'limit' => 100,
        ]);

        $fetched = 0;
        $insideCoverage = 0;
        $saved = 0;
        $skipped = 0;
        $page = 0;
        $visitedLinks = [];

        $totalMatched = (int) (
            $response['numberMatched']
            ?? $response['context']['matched']
            ?? 0
        );

        do {
            $page++;

            $features = $response['features'] ?? [];

            if (empty($features)) {
                break;
            }

            foreach ($features as $feature) {
                $fetched++;

                $properties = $feature['properties'] ?? [];
                $coordinates = $feature['geometry']['coordinates'] ?? [];

                $longitude = $properties['cm:longitude']
                    ?? $coordinates[0]
                    ?? null;

                $latitude = $properties['cm:latitude']
                    ?? $coordinates[1]
                    ?? null;

                $gas = $properties['cm:gas'] ?? null;
                $observationId = $feature['id'] ?? null;

                if (
                    ! $observationId ||
                    $gas !== 'CH4' ||
                    ! is_numeric($latitude) ||
                    ! is_numeric($longitude)
                ) {
                    $skipped++;
                    continue;
                }

                if (! $coverage->contains(
                    (float) $latitude,
                    (float) $longitude
                )) {
                    $skipped++;
                    continue;
                }

                $insideCoverage++;

                CarbonMapperObservation::updateOrCreate(
                    [
                        'observation_id' => $observationId,
                        'gas' => $gas,
                    ],
                    [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'is_super_emitter' =>
                            $properties['cm:phme_candidate'] ?? false,
                        'observed_at' => $properties['datetime'] ?? null,
                        'raw_payload' => $feature,
                    ]
                );

                $saved++;
            }

            $this->info(
                "Page {$page}: processed " . count($features) .
                " features; {$fetched} fetched so far."
            );

            $nextLink = collect($response['links'] ?? [])
                ->first(fn ($link) => ($link['rel'] ?? null) === 'next');

            if (! $nextLink || empty($nextLink['href'])) {
                break;
            }

            // Prevent accidentally following the same pagination link forever.
            $nextUrl = $nextLink['href'];

            if (isset($visitedLinks[$nextUrl])) {
                $this->warn('Repeated pagination link detected; stopping.');
                break;
            }

            $visitedLinks[$nextUrl] = true;

            $response = $stac->searchNext($nextLink);

        } while (true);

        $this->newLine();
        $this->info('Carbon Mapper import finished.');

        $this->table(
            ['Result', 'Count'],
            [
                ['API matching records reported', $totalMatched],
                ['API records fetched', $fetched],
                ['Records inside exact basin coverage', $insideCoverage],
                ['Records created or updated', $saved],
                ['Records skipped', $skipped],
            ]
        );

        return self::SUCCESS;
    }
}
