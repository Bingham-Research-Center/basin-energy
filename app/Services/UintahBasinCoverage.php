<?php

namespace App\Services;

use RuntimeException;

class UintahBasinCoverage
{
    private array $feature;
    private array $bounds;

    public function __construct()
    {
        $path = storage_path(
            'app/geo/uintah-basin-coverage.geojson'
        );

        if (! is_file($path)) {
            throw new RuntimeException(
                'Uintah Basin coverage GeoJSON was not found.'
            );
        }

        $data = json_decode(
            file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->feature = $data['features'][0] ?? [];

        if (empty($this->feature['geometry'])) {
            throw new RuntimeException(
                'Uintah Basin coverage geometry is missing.'
            );
        }

        $coordinates = $this->feature['geometry']['coordinates'];

        $points = [];

        $collectPoints = function ($value) use (&$collectPoints, &$points) {
            if (
                is_array($value)
                && count($value) >= 2
                && is_numeric($value[0])
                && is_numeric($value[1])
            ) {
                $points[] = [(float) $value[0], (float) $value[1]];
                return;
            }

            foreach ($value as $child) {
                if (is_array($child)) {
                    $collectPoints($child);
                }
            }
        };

        $collectPoints($coordinates);

        if (empty($points)) {
            throw new RuntimeException(
                'Uintah Basin coverage contains no coordinates.'
            );
        }

        $longitudes = array_column($points, 0);
        $latitudes = array_column($points, 1);

        $this->bounds = [
            'minLon' => min($longitudes),
            'minLat' => min($latitudes),
            'maxLon' => max($longitudes),
            'maxLat' => max($latitudes),
        ];
    }

    public function bounds(): array
    {
        return $this->bounds;
    }

    public function geoJson(): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => [$this->feature],
        ];
    }

    public function contains(float $latitude, float $longitude): bool
    {
        $b = $this->bounds;

        if (
            $longitude < $b['minLon']
            || $longitude > $b['maxLon']
            || $latitude < $b['minLat']
            || $latitude > $b['maxLat']
        ) {
            return false;
        }

        $geometry = $this->feature['geometry'];

        $polygons = match ($geometry['type']) {
            'Polygon' => [$geometry['coordinates']],
            'MultiPolygon' => $geometry['coordinates'],
            default => [],
        };

        foreach ($polygons as $polygon) {
            if (empty($polygon[0])) {
                continue;
            }

            // The first ring is the exterior boundary.
            if (! $this->pointInRing($longitude, $latitude, $polygon[0])) {
                continue;
            }

            // Any subsequent rings represent holes.
            $insideHole = false;

            foreach (array_slice($polygon, 1) as $hole) {
                if ($this->pointInRing($longitude, $latitude, $hole)) {
                    $insideHole = true;
                    break;
                }
            }

            if (! $insideHole) {
                return true;
            }
        }

        return false;
    }

    private function pointInRing(
        float $longitude,
        float $latitude,
        array $ring
    ): bool {
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) $ring[$i][0];
            $yi = (float) $ring[$i][1];
            $xj = (float) $ring[$j][0];
            $yj = (float) $ring[$j][1];

            // Treat points on an edge as included.
            $cross = ($longitude - $xi) * ($yj - $yi)
                - ($latitude - $yi) * ($xj - $xi);

            if (
                abs($cross) < 1.0e-10
                && $longitude >= min($xi, $xj) - 1.0e-10
                && $longitude <= max($xi, $xj) + 1.0e-10
                && $latitude >= min($yi, $yj) - 1.0e-10
                && $latitude <= max($yi, $yj) + 1.0e-10
            ) {
                return true;
            }

            $intersects = (
                ($yi > $latitude) !== ($yj > $latitude)
            ) && (
                $longitude < (
                    ($xj - $xi) * ($latitude - $yi)
                    / (($yj - $yi) ?: 1.0e-20)
                    + $xi
                )
            );

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
