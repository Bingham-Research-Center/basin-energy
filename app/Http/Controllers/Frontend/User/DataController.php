<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\EmissionTrend;
use App\Models\ProducedWaterFlux;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DataController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Emission Trends
    |--------------------------------------------------------------------------
    */

    public function emissionTrends()
    {
        $rows = EmissionTrend::orderBy('year', 'asc')->paginate(25);

        return view('frontend.user.data.emission_trends', compact('rows'));
    }

    public function emissionTrendsJson(Request $request)
    {
        $columns = $this->emissionTrendColumns();

        $selected = $request->input('cols', []);
        $selected = array_values(array_intersect($selected, array_keys($columns)));

        if (count($selected) === 0) {
            $selected = ['basinwide_ch4_emiss_mg_hr'];
        }

        $rows = EmissionTrend::query()
            ->select(array_merge(['year'], $selected))
            ->orderBy('year')
            ->get();

        return response()->json([
            'labels' => $rows->pluck('year')->values(),
            'series' => collect($selected)->map(function ($col) use ($rows, $columns) {
                return [
                    'key' => $col,
                    'label' => $columns[$col],
                    'data' => $rows->pluck($col)
                        ->map(fn ($v) => $v === null ? null : (float) $v)
                        ->values(),
                ];
            })->values(),
            'available' => collect($columns)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
            ])->values(),
        ]);
    }

    private function emissionTrendColumns(): array
    {
        return [
            'basinwide_ch4_emiss_mg_hr' => 'CH4 (Mg/hr)',
            'oil_million_bbls' => 'Oil (MM bbl)',
            'gas_million_bbleq' => 'Gas (MM bbleq)',
            'energy_million_bbleq' => 'Energy (MM bbleq)',
            'emisco2eq20_millnmg' => 'CO2e20 (million Mg)',
            'producing_wells' => 'Producing wells',
            'newwells' => 'New wells',
            'oil_gas_ratio' => 'Oil/Gas ratio',
            'utahgasprice_dollpermcf' => 'Utah gas price ($/MCF)',
            'utahcrudeprice_dollperbbl' => 'Utah crude price ($/BBL)',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Produced Water
    |--------------------------------------------------------------------------
    */

    public function producedWater()
    {
        return view('frontend.user.data.produced-water');
    }

    public function producedWaterFluxColumns()
    {
        return response()->json([
            'columns' => $this->producedWaterFluxColumnsList(),
            'units' => 'mg/m²/hr',
        ]);
    }

    public function producedWaterFluxJson(Request $request)
    {
        $columns = $this->producedWaterFluxColumnsList();

        $y = $request->get('y', 'ch4_flux');

        abort_unless(array_key_exists($y, $columns), 422, 'Invalid plot column.');

        $windCorrected = filter_var(
            $request->get('wind_corrected', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $query = ProducedWaterFlux::query()
            ->whereNotNull($y)
            ->where('wind_corrected', $windCorrected);

        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }

        if ($request->filled('facility_id')) {
            $query->where('facility_id', $request->facility_id);
        }

        $rows = $query
            ->orderByRaw('COALESCE(datetime_start, created_at)')
            ->get();

        $series = [];

        foreach ($rows as $index => $row) {
            $series[] = [
                'x' => $index,
                'y' => (float) $row->{$y},
                'time' => $row->datetime_start
                    ? Carbon::parse($row->datetime_start)->toIso8601String()
                    : $row->created_at->toIso8601String(),
                'duration' => $row->duration_min,
            ];
        }

        return response()->json([
            'meta' => [
                'y' => $y,
                'label' => $columns[$y],
                'wind_corrected' => $windCorrected,
                'count' => count($series),
                'units' => 'mg/m²/hr',
            ],
            'series' => $series,
        ]);
    }

    public function producedWaterChemistryJson()
    {
        return response()->json([
            'message' => 'Not implemented yet',
        ]);
    }

    public function producedWaterRelationshipsJson()
    {
        return response()->json([
            'message' => 'Not implemented yet',
        ]);
    }

    private function producedWaterFluxColumnsList(): array
    {
        return [
            'ch4_flux' => 'Methane (CH₄)',
            'co2_flux' => 'Carbon Dioxide (CO₂)',
            'tnmhc' => 'Total NMHC',
            'alkanes' => 'Alkanes',
            'alkenes' => 'Alkenes',
            'aromatics' => 'Aromatics',
            'alcohols' => 'Alcohols',
            'carbonyls' => 'Carbonyls',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Realtime Ozone
    |--------------------------------------------------------------------------
    */

    public function realtimeOzone()
    {
        return view('frontend.user.data.realtime_ozone');
    }

    public function realtimeOzoneJson(Request $request)
    {
        $token = config('services.synoptic.token', env('SYNOPTIC_TOKEN'));

        abort_if(blank($token), 500, 'Synoptic token is not configured.');

        $start = $request->get('start', now()->subDays(7)->format('YmdHi'));
        $end = $request->get('end', now()->format('YmdHi'));

        $bbox = '-110.5,39.4,-108.5,41.0';
        $vars = 'ozone_concentration';

        $metadata = Cache::remember('uinta_ozone_metadata', now()->addHours(6), function () use ($token, $bbox, $vars) {
            return Http::timeout(20)->get('https://api.synopticdata.com/v2/stations/metadata', [
                'token' => $token,
                'bbox' => $bbox,
                'vars' => $vars,
                'sensorvars' => 1,
                'output' => 'json',
            ])->json();
        });

        $stations = collect($metadata['STATION'] ?? [])
            ->pluck('STID')
            ->filter()
            ->values();

        $series = [];

        foreach ($stations as $station) {
            $data = Cache::remember("uinta_ozone_{$station}_{$start}_{$end}", now()->addMinutes(15), function () use ($token, $station, $vars, $start, $end) {
                return Http::timeout(30)->get('https://api.synopticdata.com/v2/stations/timeseries', [
                    'token' => $token,
                    'stid' => $station,
                    'vars' => $vars,
                    'start' => $start,
                    'end' => $end,
                ])->json();
            });

            $stationData = $data['STATION'][0] ?? null;

            if (! $stationData) {
                continue;
            }

            $observations = $stationData['OBSERVATIONS'] ?? [];

            $ozoneKey = collect(array_keys($observations))
                ->first(fn ($key) => str_starts_with($key, 'ozone_concentration_set_'));

            if (! $ozoneKey) {
                continue;
            }

            $times = $observations['date_time'] ?? [];
            $values = $observations[$ozoneKey] ?? [];

            $points = [];

            foreach ($times as $i => $time) {
                if (! isset($values[$i]) || $values[$i] === null) {
                    continue;
                }

                $points[] = [
                    'x' => $time,
                    'y' => (float) $values[$i],
                ];
            }

            $series[] = [
                'station' => $station,
                'name' => $stationData['NAME'] ?? $station,
                'latitude' => $stationData['LATITUDE'] ?? null,
                'longitude' => $stationData['LONGITUDE'] ?? null,
                'data' => $points,
            ];
        }

        return response()->json([
            'meta' => [
                'units' => 'ppb',
                'count' => count($series),
                'start' => $start,
                'end' => $end,
            ],
            'series' => $series,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Campbell Logger Realtime Sites
    |--------------------------------------------------------------------------
    |
    | Generic realtime logger support.
    |
    | Expected config:
    |
    | config/services.php
    |
    | 'campbell_sites' => [
    |     'horsepool' => [
    |         'name' => 'Horsepool',
    |         'url' => env('CAMPBELL_HORSEPOOL_URL', 'http://69.55.104.135/'),
    |         'query' => [
    |             'command' => 'DataQuery',
    |             'uri' => 'dl:Synoptic',
    |             'mode' => 'most-recent',
    |             'p1' => 1,
    |         ],
    |     ],
    | ],
    |
    */

    public function campbellLogger(string $site)
    {
        $siteConfig = $this->campbellSiteConfig($site);

        return view('frontend.user.data.campbell_logger', [
            'siteKey' => $site,
            'site' => $siteConfig,
        ]);
    }

    public function campbellLoggerJson(string $site)
    {
        $siteConfig = $this->campbellSiteConfig($site);

        if (empty($siteConfig['url'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Realtime URL is not configured for this site.',
                'meta' => [
                    'site_key' => $site,
                    'station' => $siteConfig['name'] ?? Str::headline($site),
                    'source' => $siteConfig['source'] ?? 'Campbell logger',
                    'latitude' => $siteConfig['latitude'] ?? null,
                    'longitude' => $siteConfig['longitude'] ?? null,
                    'elevation_ft' => $siteConfig['elevation_ft'] ?? null,
                    'fetched_at' => now()->toIso8601String(),
                ],
                'record' => [],
            ], 422);
        }

        try {
            $record = Cache::remember("campbell_logger_latest_{$site}", now()->addMinutes(2), function () use ($siteConfig) {
                $response = Http::timeout($siteConfig['timeout'] ?? 12)
                    ->connectTimeout(6)
                    ->retry(1, 300)
                    ->get($siteConfig['url'], $siteConfig['query'] ?? []);

                if (! $response->ok()) {
                    throw new \RuntimeException('Logger returned HTTP '.$response->status());
                }

                $record = $this->parseCampbellLoggerResponse($response->body());

                if (isset($record['raw'])) {
                    throw new \RuntimeException(
                        'Logger response could not be parsed as a Campbell data table. Check the URL/query configuration.'
                    );
                }

                return $record;
            });

            return response()->json([
                'ok' => true,
                'meta' => [
                    'site_key' => $site,
                    'station' => $siteConfig['name'] ?? Str::headline($site),
                    'source' => $siteConfig['source'] ?? 'Campbell logger',
                    'latitude' => $siteConfig['latitude'] ?? null,
                    'longitude' => $siteConfig['longitude'] ?? null,
                    'elevation_ft' => $siteConfig['elevation_ft'] ?? null,
                    'fetched_at' => now()->toIso8601String(),
                ],
                'record' => $record,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'meta' => [
                    'site_key' => $site,
                    'station' => $siteConfig['name'] ?? Str::headline($site),
                    'source' => $siteConfig['source'] ?? 'Campbell logger',
                    'latitude' => $siteConfig['latitude'] ?? null,
                    'longitude' => $siteConfig['longitude'] ?? null,
                    'elevation_ft' => $siteConfig['elevation_ft'] ?? null,
                    'fetched_at' => now()->toIso8601String(),
                ],
                'record' => [],
            ], 502);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Backward-Compatible Horsepool Methods
    |--------------------------------------------------------------------------
    |
    | Keep these so existing routes do not break while we move to:
    |
    | realtime/logger/{site}
    |
    */

    public function horsepool()
    {
        return $this->campbellLogger('horsepool');
    }

    public function horsepoolJson()
    {
        return $this->campbellLoggerJson('horsepool');
    }

    private function campbellSiteConfig(string $site): array
    {
        $sites = config('services.campbell_sites', []);

        if (isset($sites[$site])) {
            return $sites[$site];
        }

        /*
         * Fallback so Horsepool keeps working even before config/services.php
         * is updated.
         */
        if ($site === 'horsepool') {
            return [
                'name' => 'Horsepool',
                'source' => 'Campbell logger',
                'url' => env('CAMPBELL_HORSEPOOL_URL', 'http://69.55.104.135/'),
                'query' => [
                    'command' => 'DataQuery',
                    'uri' => 'dl:Synoptic',
                    'mode' => 'most-recent',
                    'p1' => 1,
                ],
                'timeout' => 15,
            ];
        }

        abort(404, 'Unknown Campbell logger site.');
    }

    private function parseCampbellLoggerResponse(string $body): array
    {
        $body = trim($body);

        if ($body === '') {
            return [];
        }

        /*
        * 1. JSON response, if any logger ever returns JSON.
        */
        $json = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json['record'] ?? $json['data'] ?? $json;
        }

        /*
        * 2. Campbell CR1000 HTML table response.
        */
        if (str_contains(strtolower($body), '<table')) {
            libxml_use_internal_errors(true);

            $dom = new \DOMDocument();
            $loaded = $dom->loadHTML($body);

            libxml_clear_errors();

            if ($loaded) {
                $xpath = new \DOMXPath($dom);

                $headers = [];
                foreach ($xpath->query('//table//tr[1]/th') as $th) {
                    $headers[] = trim(html_entity_decode($th->textContent));
                }

                $values = [];
                foreach ($xpath->query('//table//tr[2]/td') as $td) {
                    $values[] = trim(html_entity_decode($td->textContent));
                }

                if (count($headers) > 0 && count($headers) === count($values)) {
                    $record = [];

                    foreach ($headers as $index => $header) {
                        $value = $values[$index] ?? null;

                        $record[$header] = $this->normalizeCampbellValue($value);
                    }

                    return $record;
                }
            }
        }

        /*
        * 3. CSV fallback.
        */
        $lines = preg_split('/\r\n|\r|\n/', $body);
        $lines = array_values(array_filter($lines, fn ($line) => trim($line) !== ''));

        if (count($lines) >= 2) {
            $headers = array_map('trim', str_getcsv($lines[0]));
            $values = array_map('trim', str_getcsv($lines[count($lines) - 1]));

            if (count($headers) === count($values)) {
                $record = [];

                foreach ($headers as $index => $header) {
                    $record[$header] = $this->normalizeCampbellValue($values[$index] ?? null);
                }

                return $record;
            }
        }

        return [
            'raw' => $body,
        ];
    }

    private function normalizeCampbellValue($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        $value = trim($value, "\"'");

        if ($value === '' || strtoupper($value) === 'NAN') {
            return null;
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        return $value;
    }
}