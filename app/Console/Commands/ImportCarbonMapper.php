<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CarbonMapperStacService;
use App\Models\CarbonMapperObservation;

class ImportCarbonMapper extends Command
{
    protected $signature = 'carbon-mapper:import';
    protected $description = 'Import Carbon Mapper L2C methane plume origins';

    public function handle(CarbonMapperStacService $stac)
    {
        $this->info('Fetching Carbon Mapper STAC L2C CH4 data…');

        $limit  = 100;
        $offset = 0;
        $total  = null;

        do {
            $response = $stac->search([
                'collections' => ['l2c-ch4'],
                'limit'       => $limit,
                'offset'      => $offset,
            ]);

            foreach ($response['features'] as $feature) {
                $coords = $feature['geometry']['coordinates'];
                $props  = $feature['properties'];

                CarbonMapperObservation::updateOrCreate(
                    ['observation_id' => $feature['id']],
                    [
                        'latitude'    => $coords[1],
                        'longitude'   => $coords[0],
                        'gas'         => $props['cm:gas'] ?? 'CH4',
                        'sector'      => $props['cm:sector'] ?? null,
                        'is_phme'     => $props['cm:phme_candidate'] ?? false,
                        'observed_at' => $props['datetime'] ?? null,
                        'raw_payload' => $feature,
                    ]
                );
            }

            $offset += $limit;
            $total = $response['context']['matched'] ?? 0;

            $this->info("Imported {$offset} / {$total}");

        } while ($offset < $total);

        $this->info('Carbon Mapper import complete.');
    }
}
