<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\EmissionTrend;
use App\Models\ProducedWaterFlux;
use App\Models\SubsurfaceLeakSurvey;
use App\Models\SubsurfaceLeakIntervalFlux;
use App\Models\SubsurfaceLeakChamberReading;
use App\Models\OilWellPadEmission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Services\AiSummaryService;

class DataController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Emission Trends
    |--------------------------------------------------------------------------
    */

    public function emissionTrendsAiSummary(Request $request, AiSummaryService $aiSummaryService)
    {
        $payload = $request->validate([
            'selected' => ['required', 'array'],
            'selected.*.label' => ['required', 'string'],
            'selected.*.first_year' => ['nullable'],
            'selected.*.last_year' => ['nullable'],
            'selected.*.first_value' => ['nullable'],
            'selected.*.last_value' => ['nullable'],
            'selected.*.percent_change' => ['nullable'],
            'selected.*.min' => ['nullable'],
            'selected.*.max' => ['nullable'],
            'selected.*.avg' => ['nullable'],
            'normalize' => ['nullable', 'boolean'],
            'log_scale' => ['nullable', 'boolean'],
        ]);

        return response()->json(
            $aiSummaryService->summarizeEmissionTrends($payload)
        );
    }

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
    | Surface leaks / Subsurface Natural Gas Leaks
    |--------------------------------------------------------------------------
    */

    public function subsurfaceNaturalGasLeaks()
    {
        return view('frontend.user.data.subsurface_natural_gas_leaks');
    }

    public function subsurfaceNaturalGasLeaksOverviewJson()
    {
        return response()->json([
            'survey_records' => SubsurfaceLeakSurvey::count(),
            'interval_records' => SubsurfaceLeakIntervalFlux::count(),
            'chamber_records' => SubsurfaceLeakChamberReading::count(),
            'sites' => SubsurfaceLeakChamberReading::query()->distinct()->orderBy('site_code')->pluck('site_code'),
            'survey_ch4' => [
                'min' => SubsurfaceLeakSurvey::min('ch4_flux'),
                'max' => SubsurfaceLeakSurvey::max('ch4_flux'),
                'avg' => SubsurfaceLeakSurvey::avg('ch4_flux'),
            ],
        ]);
    }

    public function subsurfaceNaturalGasLeaksSurveyJson(Request $request)
    {
        $metric = $request->get('metric', 'ch4_flux');
        $allowed = ['ch4_flux', 'co2_flux', 'tnmhc_flux', 'alkanes_flux', 'alkenes_flux', 'aromatics_flux'];
        abort_unless(in_array($metric, $allowed, true), 422, 'Invalid metric.');

        $query = SubsurfaceLeakSurvey::query()->whereNotNull($metric);
        if ($request->filled('well_type')) $query->where('well_type', $request->well_type);
        if ($request->filled('well_status')) $query->where('well_status', $request->well_status);
        if ($request->filled('month')) $query->where('sample_month', $request->month);

        $rows = $query->orderBy('flux_sample_date')->limit(5000)->get([
            'id', 'flux_sample_date', 'sample_month', 'well_id', 'well_type', 'well_status',
            'flux_distance_m', 'total_combustible_soil_gas', 'ch4_flux', 'co2_flux',
            'tnmhc_flux', 'alkanes_flux', 'alkenes_flux', 'aromatics_flux',
            'ambient_temp_c', 'soil_temp_c', 'soil_water_content',
        ]);

        return response()->json([
            'metric' => $metric,
            'rows' => $rows,
            'options' => [
                'well_types' => SubsurfaceLeakSurvey::whereNotNull('well_type')->distinct()->orderBy('well_type')->pluck('well_type'),
                'well_statuses' => SubsurfaceLeakSurvey::whereNotNull('well_status')->distinct()->orderBy('well_status')->pluck('well_status'),
                'months' => SubsurfaceLeakSurvey::whereNotNull('sample_month')->distinct()->orderBy('sample_month')->pluck('sample_month'),
            ],
        ]);
    }

    public function subsurfaceNaturalGasLeaksTemporalJson(Request $request)
    {
        $site = $request->get('site', 'UPW1');
        $gas = strtolower($request->get('gas', 'ch4'));
        $chamber = (int) $request->get('chamber', 1);
        $aggregation = $request->get('aggregation', 'hourly');

        abort_unless(in_array($gas, ['ch4', 'co2'], true), 422, 'Invalid gas.');
        abort_unless($chamber >= 1 && $chamber <= 6, 422, 'Invalid chamber.');
        abort_unless(in_array($aggregation, ['raw', 'hourly', 'daily'], true), 422, 'Invalid aggregation.');

        $column = "chm{$chamber}_{$gas}";
        $query = SubsurfaceLeakChamberReading::query()
            ->where('site_code', $site)
            ->whereNotNull($column);

        if ($request->filled('start')) $query->where('measured_at', '>=', $request->start);
        if ($request->filled('end')) $query->where('measured_at', '<=', $request->end);

        if ($aggregation === 'raw') {
            $rows = $query->orderBy('measured_at')->limit(10000)->get(['measured_at', $column, 'soil_temp_c', 'soil_water_pct']);
        } else {
            $format = $aggregation === 'daily' ? '%Y-%m-%d 00:00:00' : '%Y-%m-%d %H:00:00';
            $rows = $query
                ->selectRaw("DATE_FORMAT(measured_at, '{$format}') AS measured_at")
                ->selectRaw("AVG({$column}) AS {$column}")
                ->selectRaw('AVG(soil_temp_c) AS soil_temp_c, AVG(soil_water_pct) AS soil_water_pct')
                ->groupByRaw("DATE_FORMAT(measured_at, '{$format}')")
                ->orderBy('measured_at')
                ->get();
        }

        return response()->json([
            'site' => $site,
            'gas' => $gas,
            'chamber' => $chamber,
            'aggregation' => $aggregation,
            'column' => $column,
            'rows' => $rows,
        ]);
    }

    public function subsurfaceNaturalGasLeaksOptionsJson()
    {
        return response()->json([
            'sites' => SubsurfaceLeakChamberReading::query()
                ->select('site_code', 'site_type')
                ->distinct()->orderBy('site_code')->get(),
            'metrics' => [
                ['key' => 'ch4_flux', 'label' => 'Methane (CH₄)'],
                ['key' => 'co2_flux', 'label' => 'Carbon dioxide (CO₂)'],
                ['key' => 'tnmhc_flux', 'label' => 'Total NMHC'],
                ['key' => 'alkanes_flux', 'label' => 'Alkanes'],
                ['key' => 'alkenes_flux', 'label' => 'Alkenes'],
                ['key' => 'aromatics_flux', 'label' => 'Aromatics'],
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Oil Well Pad Emissions
    |--------------------------------------------------------------------------
    */

    public function oilWellPadEmissions()
    {
        return view('frontend.user.data.oil_well_pad_emissions');
    }

    public function oilWellPadEmissionsOverviewJson()
    {
        $query = OilWellPadEmission::query();

        return response()->json([
            'measurement_count' => (clone $query)->count(),
            'source_type_count' => (clone $query)->distinct('sample_type')->count('sample_type'),
            'max_total_organic_g_hr' => (float) ((clone $query)->max('total_organic_compounds_g_hr') ?? 0),
            'max_methane_g_hr' => (float) ((clone $query)->max('methane_g_hr') ?? 0),
            'source_summary' => (clone $query)
                ->selectRaw('sample_type, COUNT(*) AS measurement_count')
                ->selectRaw('AVG(total_organic_compounds_g_hr) AS avg_total_organic_g_hr')
                ->selectRaw('AVG(methane_g_hr) AS avg_methane_g_hr')
                ->selectRaw('AVG(tnmhc_g_hr) AS avg_tnmhc_g_hr')
                ->groupBy('sample_type')
                ->orderByDesc('avg_total_organic_g_hr')
                ->get()
                ->map(fn ($row) => [
                    'sample_type' => $row->sample_type,
                    'measurement_count' => (int) $row->measurement_count,
                    'avg_total_organic_g_hr' => round((float) $row->avg_total_organic_g_hr, 4),
                    'avg_methane_g_hr' => round((float) $row->avg_methane_g_hr, 4),
                    'avg_tnmhc_g_hr' => round((float) $row->avg_tnmhc_g_hr, 4),
                ]),
        ]);
    }

    public function oilWellPadEmissionsSamplesJson(Request $request)
    {
        $metrics = $this->oilWellPadEmissionMetrics();
        $metric = $request->get('metric', 'total_organic_compounds_g_hr');

        abort_unless(array_key_exists($metric, $metrics), 422, 'Invalid emission metric.');

        $query = OilWellPadEmission::query();

        if ($request->filled('sample_type')) {
            $query->where('sample_type', $request->string('sample_type'));
        }

        $rows = $query
            ->orderBy('sample_number')
            ->get([
                'id',
                'sample_number',
                'sample_type',
                $metric,
                'methane_g_hr',
                'tnmhc_g_hr',
                'notes',
            ]);

        return response()->json([
            'metric' => $metric,
            'metric_label' => $metrics[$metric],
            'rows' => $rows,
        ]);
    }

    public function oilWellPadEmissionsCompositionJson(Request $request)
    {
        $query = OilWellPadEmission::query();

        if ($request->filled('sample_type')) {
            $query->where('sample_type', $request->string('sample_type'));
        }

        $rows = $query->get([
            'methane_g_hr',
            'alkanes_g_hr',
            'alkenes_alkyne_g_hr',
            'aromatics_g_hr',
            'alcohols_g_hr',
            'carbonyls_g_hr',
        ]);

        $groups = [
            'Methane' => 'methane_g_hr',
            'Alkanes' => 'alkanes_g_hr',
            'Alkenes + alkyne' => 'alkenes_alkyne_g_hr',
            'Aromatics' => 'aromatics_g_hr',
            'Alcohols' => 'alcohols_g_hr',
            'Carbonyls' => 'carbonyls_g_hr',
        ];

        $averages = collect($groups)->map(function ($column, $label) use ($rows) {
            $values = $rows->pluck($column)->filter(fn ($value) => $value !== null);

            return [
                'label' => $label,
                'value' => $values->count() ? round((float) $values->avg(), 6) : 0,
            ];
        })->values();

        return response()->json([
            'sample_type' => $request->get('sample_type'),
            'measurement_count' => $rows->count(),
            'groups' => $averages,
        ]);
    }

    public function oilWellPadEmissionsOptionsJson()
    {
        return response()->json([
            'sample_types' => OilWellPadEmission::query()
                ->distinct()
                ->orderBy('sample_type')
                ->pluck('sample_type')
                ->values(),
            'metrics' => collect($this->oilWellPadEmissionMetrics())
                ->map(fn ($label, $key) => compact('key', 'label'))
                ->values(),
        ]);
    }

    private function oilWellPadEmissionMetrics(): array
    {
        return [
            'total_organic_compounds_g_hr' => 'Total organic compounds (g/hr)',
            'methane_g_hr' => 'Methane (g/hr)',
            'carbon_dioxide_g_hr' => 'Carbon dioxide (g/hr)',
            'tnmhc_g_hr' => 'Total non-methane hydrocarbons (g/hr)',
            'alkanes_g_hr' => 'Total alkanes (g/hr)',
            'alkenes_alkyne_g_hr' => 'Total alkenes + alkyne (g/hr)',
            'aromatics_g_hr' => 'Total aromatics (g/hr)',
            'alcohols_g_hr' => 'Total alcohols (g/hr)',
            'carbonyls_g_hr' => 'Total carbonyls (g/hr)',
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