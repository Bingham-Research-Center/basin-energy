<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CarbonMapperObservation;
use App\Services\UintahBasinCoverage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Services\CarbonMapperSourceService;

class CarbonMapperController extends Controller
{
    /**
     * Display the Carbon Mapper methane map.
     */
    public function utah()
    {
        return view('frontend.carbon-mapper.index');
    }

    /**
     * Return the Uintah Basin coverage boundary as GeoJSON.
     */
    public function utahBoundary(UintahBasinCoverage $coverage)
    {
        return response()->json($coverage->geoJson());
    }

    /**
     * Return methane observations inside the defined Uintah Basin area.
     */
  public function utahJson(
    Request $request,
    UintahBasinCoverage $coverage,
    CarbonMapperSourceService $sourceService
) {
    $bounds = $coverage->bounds();

    $query = $this->basinQuery($bounds)
        ->select([
            'id',
            'gas',
            'latitude',
            'longitude',
            'observed_at',
            'is_super_emitter',
            'emission_rate',
            'emission_unit',
            'raw_payload',
            'observation_id',
        ]);

    // Limit observations to the visible map area, if a bounding box is provided.
    if ($request->filled('bbox')) {
        $parts = explode(',', $request->input('bbox'));

        if (
            count($parts) !== 4 ||
            !collect($parts)->every(
                fn ($value) => is_numeric(trim($value))
            )
        ) {
            return response()->json([
                'message' => 'The bbox parameter must contain four numbers.',
            ], 422);
        }

        [$minLon, $minLat, $maxLon, $maxLat] = array_map(
            'floatval',
            $parts
        );

        $minLon = max($minLon, $bounds['minLon']);
        $minLat = max($minLat, $bounds['minLat']);
        $maxLon = min($maxLon, $bounds['maxLon']);
        $maxLat = min($maxLat, $bounds['maxLat']);

        if ($minLon > $maxLon || $minLat > $maxLat) {
            return response()->json([]);
        }

        $query->whereBetween('longitude', [$minLon, $maxLon])
            ->whereBetween('latitude', [$minLat, $maxLat]);
    }

    // Validate the optional filters.
    $request->validate([
    'from' => ['nullable', 'date_format:Y-m-d'],
    'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
    'detection_type' => ['nullable', 'string', 'max:50'],
    'gas' => ['nullable', 'in:CH4,CO2'],
]);

// Filter observations by the selected gas.
if ($request->filled('gas')) {
    $query->where('gas', $request->input('gas'));
}

    // Apply the date filters.
    if ($request->filled('from')) {
        $query->whereDate('observed_at', '>=', $request->input('from'));
    }

    if ($request->filled('to')) {
        $query->whereDate('observed_at', '<=', $request->input('to'));
    }

    // Keep only observations inside the actual basin coverage polygon.
    $observations = $this->filterWithinCoverage(
        $query,
        $coverage,
        20000
    );

    $sourcesByPlumeId = $sourceService->byPlumeId($coverage);

    // Prepare the data used by the map and observation popups.
    $results = $observations->map(function ($observation) use ($sourcesByPlumeId) {
        $properties = data_get(
            $observation->raw_payload,
            'properties',
            []
        );
        $source = $sourcesByPlumeId[$observation->observation_id] ?? null;
        $manualSourceRate = $properties['source_emission_rate'] ?? null;
$manualSourceUncertainty = $properties['source_emission_uncertainty'] ?? null;
$manualSourceUnit = $properties['source_emission_unit'] ?? null;

        return [
            'latitude' => $observation->latitude,
            'longitude' => $observation->longitude,
            'observed_at' => $observation->observed_at,
            'published_at' => $properties['cm:published_at'] ?? null,
            'gas' => $observation->gas,
            'source_name' => $source['source_name'] ?? null,
            'source_emission_rate' => $source['emission_rate'] ?? $manualSourceRate,
'source_emission_uncertainty' => $source['emission_uncertainty'] ?? $manualSourceUncertainty,
'source_emission_unit' => isset($source['emission_rate'])
    ? 'kg/hr'
    : $manualSourceUnit,
            'is_super_emitter' => (bool) $observation->is_super_emitter,
            'detection_type' => $properties['cm:cmf_type'] ?? null,
            'sector' => $properties['cm:sector'] ?? null,
            'emission_rate' => $observation->emission_rate,
            'emission_unit' => $observation->emission_unit,
        ];
    });

    // Apply the detection-type filter.
    if ($request->filled('detection_type')) {
        $selectedType = $request->input('detection_type');

        $results = $results->filter(function ($observation) use ($selectedType) {
            return $observation['detection_type'] === $selectedType;
        });
    }

    return response()->json($results->values());
}

    /**
     * Return individual methane detections inside the coverage area.
     */
    public function utahDetections(UintahBasinCoverage $coverage)
    {
        $bounds = $coverage->bounds();

        $query = $this->basinQuery($bounds)
            ->select([
                'id',
                'latitude',
                'longitude',
                'observed_at',
            ]);

        $detections = $this->filterWithinCoverage(
            $query,
            $coverage,
            20000
        );

        return response()->json($detections->values());
    }

    /**
     * Summarize methane observations by approximate location.
     * Only observations inside the coverage polygon are counted.
     */
    public function utahSources(UintahBasinCoverage $coverage)
    {
        $bounds = $coverage->bounds();

        $query = $this->basinQuery($bounds)
            ->select([
                'latitude',
                'longitude',
                'observed_at',
            ]);

        $sources = [];

        $query->orderBy('id')->chunk(2000, function ($rows) use (
            $coverage,
            &$sources
        ) {
            foreach ($rows as $observation) {
                if (! $coverage->contains(
                    (float) $observation->latitude,
                    (float) $observation->longitude
                )) {
                    continue;
                }

                $latitude = round((float) $observation->latitude, 3);
                $longitude = round((float) $observation->longitude, 3);
                $key = $latitude . ',' . $longitude;

                if (! isset($sources[$key])) {
                    $sources[$key] = [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'observations' => 0,
                        'last_seen' => null,
                    ];
                }

                $sources[$key]['observations']++;

                $observedAt = $observation->observed_at;

                if (
                    $observedAt &&
                    (
                        ! $sources[$key]['last_seen'] ||
                        $observedAt->gt($sources[$key]['last_seen'])
                    )
                ) {
                    $sources[$key]['last_seen'] = $observedAt;
                }
            }
        });

        $results = collect(array_values($sources))
            ->filter(fn ($source) => $source['observations'] >= 5)
            ->values();

        return response()->json($results);
    }

    /**
     * Build a database query limited to the coverage area's rectangular
     * bounds. The exact polygon check is applied separately.
     */
    private function basinQuery(array $bounds): Builder
    {
        return CarbonMapperObservation::query()
            ->whereIn('gas', ['CH4', 'CO2'])
            ->whereBetween('longitude', [
                $bounds['minLon'],
                $bounds['maxLon'],
            ])
            ->whereBetween('latitude', [
                $bounds['minLat'],
                $bounds['maxLat'],
            ]);
    }

    /**
     * Filter observations against the actual polygon.
     *
     * The database bounds reduce the number of records to examine.
     * The polygon check excludes records inside the rectangle but
     * outside the defined coverage area.
     */
    private function filterWithinCoverage(
        Builder $query,
        UintahBasinCoverage $coverage,
        int $limit
    ) {
        $results = collect();

        $query->orderBy('id')->chunk(2000, function ($rows) use (
            $coverage,
            $limit,
            &$results
        ) {
            foreach ($rows as $observation) {
                if (! $coverage->contains(
                    (float) $observation->latitude,
                    (float) $observation->longitude
                )) {
                    continue;
                }

                $results->push($observation);

                if ($results->count() >= $limit) {
                    return false;
                }
            }
        });

        return $results;
    }
}
