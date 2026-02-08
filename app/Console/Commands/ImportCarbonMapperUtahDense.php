<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CarbonMapperStacService;
use App\Models\CarbonMapperObservation;

class ImportCarbonMapperUtahDense extends Command
{
    protected $signature = 'carbon-mapper:import-utah-dense';
    protected $description = 'Import dense CH4 detections for Utah (no deduplication)';

    public function handle(CarbonMapperStacService $stac)
    {
        $this->info('Importing dense Utah CH4 detections…');

        $payload = [
            'collections' => ['l2c-ch4'],
            'bbox'        => [-114.05, 36.99, -109.05, 42.00],
            'limit'       => 100,
        ];



        do {
            $response = $stac->search($payload);

            foreach ($response['features'] as $feature) {
                $props = $feature['properties'];

                CarbonMapperObservation::create([
                    'observation_id'   => $feature['id'],
                    'gas'              => 'CH4',
                    'latitude'         => $props['cm:latitude'],
                    'longitude'        => $props['cm:longitude'],
                    'observed_at'      => $props['datetime'] ?? null,
                    'is_super_emitter' => $props['cm:phme_candidate'] ?? false,
                    'raw_payload'      => $feature,
                ]);
            }

            if (isset($response['context']['offset'])) {
                $payload['offset'] = $response['context']['offset'] + 100;
            } else {
                break;
            }

        } while (!empty($response['features']));





        $this->info(' Utah dense import complete');
    }

}
