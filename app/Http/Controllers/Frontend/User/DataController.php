<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\EmissionTrend;
use App\Models\ProducedWaterFlux;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;


class DataController extends Controller
{
    public function emissionTrends()
    {
        $rows = EmissionTrend::orderBy('year', 'asc')->paginate(25);

        return view('frontend.user.data.emission_trends', compact('rows'));
    }

    public function emissionTrendsJson(Request $request)
    {
        // Allowed columns user can plot (prevents arbitrary column access)
        $columns = [
            'basinwide_ch4_emiss_mg_hr' => 'CH4 (Mg/hr)',
            'oil_million_bbls'          => 'Oil (MM bbl)',
            'gas_million_bbleq'         => 'Gas (MM bbleq)',
            'energy_million_bbleq'      => 'Energy (MM bbleq)',
            'emisco2eq20_millnmg'       => 'CO2e20 (million Mg)',
            'producing_wells'           => 'Producing wells',
            'newwells'                  => 'New wells',
            'oil_gas_ratio'             => 'Oil/Gas ratio',
            'utahgasprice_dollpermcf'   => 'Utah gas price ($/MCF)',
            'utahcrudeprice_dollperbbl' => 'Utah crude price ($/BBL)',
        ];

        $selected = $request->input('cols', []);
        $selected = array_values(array_intersect($selected, array_keys($columns)));

        // Default if none selected
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
                    'key'   => $col,
                    'label' => $columns[$col],
                    'data'  => $rows->pluck($col)->map(fn($v) => $v === null ? null : (float)$v)->values(),
                ];
            })->values(),
            'available' => collect($columns)->map(fn($label, $key) => [
                'key' => $key,
                'label' => $label,
            ])->values(),
        ]);
    }

    public function producedWater()
    {
        return view('frontend.user.data.produced-water');
    }

    public function producedWaterFluxColumns()
    {
        return response()->json([
            'columns' => [
                'ch4_flux'   => 'Methane (CH₄)',
                'co2_flux'   => 'Carbon Dioxide (CO₂)',
                'tnmhc'      => 'Total NMHC',
                'alkanes'    => 'Alkanes',
                'alkenes'    => 'Alkenes',
                'aromatics'  => 'Aromatics',
                'alcohols'   => 'Alcohols',
                'carbonyls'  => 'Carbonyls',
            ],
            'units' => 'mg/m²/hr',
        ]);
    }

    public function producedWaterFluxJson(Request $request)
    {
        $y = $request->get('y', 'ch4_flux');
        $windCorrected = filter_var(
            $request->get('wind_corrected', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $query = ProducedWaterFlux::query()
            ->whereNotNull($y)
            ->where('wind_corrected', $windCorrected);

        // Optional filters
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
        $index = 0;

        foreach ($rows as $row) {
            $series[] = [
                'x' => $index,
                'y' => (float) $row->{$y},
                'time' => $row->datetime_start
                    ? $row->datetime_start->toIso8601String()
                    : $row->created_at->toIso8601String(),
                'duration' => $row->duration_min, // minutes
            ];
            $index++;
        }

        return response()->json([
            'meta' => [
                'y' => $y,
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

    public function realtimeOzone()
    {
        return view('frontend.user.data.realtime_ozone');
    }

    public function horsepool()
    {
        return view('frontend.user.data.horsepool');
    }

    public function realtimeOzoneJson(Request $request)
    {
        $token = config('services.synoptic.token', env('SYNOPTIC_TOKEN'));

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

        $stations = collect($metadata['STATION'] ?? [])->pluck('STID')->values();

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

    public function horsepoolJson()
    {
        $data = Cache::remember('horsepool_latest_synoptic', now()->addMinutes(2), function () {
            $response = Http::timeout(10)->get('http://69.55.104.135/', [
                'command' => 'DataQuery',
                'uri' => 'dl:Synoptic',
                'mode' => 'most-recent',
                'p1' => 1,
            ]);

            return $response->body();
        });

        preg_match_all('/<th[^>]*>(.*?)<\/th>/i', $data, $headers);
        preg_match_all('/<td[^>]*>(.*?)<\/td>/i', $data, $values);

        $headers = array_map('trim', array_map('strip_tags', $headers[1] ?? []));
        $values = array_map('trim', array_map('strip_tags', $values[1] ?? []));

        return response()->json([
            'meta' => [
                'station' => 'Horsepool',
                'source' => 'Campbell logger',
                'fetched_at' => now()->toIso8601String(),
            ],
            'record' => array_combine($headers, array_slice($values, 0, count($headers))) ?: [],
        ]);
    }




}
