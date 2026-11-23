<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PandoraDataService
{
    private const API_BASE = 'https://api.pandonia-global-network.org/v1';

    private const STATION = 'DinosaurUT';

    private const INSTRUMENT = 95;

    private const SPECTROMETER = 1;

    /**
     * Retrieve available Level 2 NO2 files for DinosaurUT.
     *
     * Results are cached to reduce requests to the PGN API.
     */
    public function availableNo2Files(string $start, string $end): array
    {
        $cacheKey = 'pandora.dinosaur.no2.files.' .
            md5($start . '|' . $end);

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(30),
            function () use ($start, $end) {
                $url = self::API_BASE .
                    '/files/' .
                    self::STATION . '/' .
                    self::INSTRUMENT . '/' .
                    self::SPECTROMETER . '/L2';

                $response = Http::acceptJson()
                    ->timeout(30)
                    ->get($url, [
                        'start' => $start,
                        'end' => $end,
                    ]);

if ($response->status() === 404 &&
    $response->json('detail') === 'No files found for the specified parameters'
) {
    return [];
}

if (! $response->successful()) {
    throw new \RuntimeException(
        'Pandora API returned HTTP ' .
        $response->status()
    );
}
                $files = $response->json();

                if (! is_array($files)) {
                    throw new \RuntimeException(
                        'Pandora API returned an invalid file listing.'
                    );
                }

                return array_values(array_filter(
                    $files,
                    fn ($file) =>
                        is_array($file) &&
                        ($file['metadata_code'] ?? null) === 'rnvh3'
                ));
            }
        );
    }
    /**
     * Download and parse a PGN Level 2 NO2 file.
     *
     * Column numbers follow the rnvh3 product header.
     * Only rows containing valid tropospheric NO2 values are returned.
     */
    public function observationsFromFile(string $filename): array
    {
        if (! preg_match(
            '/^Pandora95s1_DinosaurUT_[0-9]{8}_L2_rnvh3[a-zA-Z0-9-]+\.txt$/',
            $filename
        )) {
            throw new \InvalidArgumentException(
                'Invalid Pandora NO2 filename.'
            );
        }

        return Cache::remember(
            'pandora.no2.observations.' . md5($filename),
            now()->addHours(6),
            function () use ($filename) {
                $response = Http::timeout(60)->get(
                    self::API_BASE . '/download/' . rawurlencode($filename)
                );

                if (! $response->successful()) {
                    throw new \RuntimeException(
                        'Pandora download returned HTTP ' .
                        $response->status()
                    );
                }

                $observations = [];

                foreach (preg_split('/\r\n|\r|\n/', $response->body()) as $line) {
                    $line = trim($line);

                    // Ignore headers, comments, and blank lines.
                    if (! preg_match('/^\d{8}T\d{6}/', $line)) {
                        continue;
                    }

                    $columns = preg_split('/\s+/', $line);

                    if (count($columns) < 63) {
                        continue;
                    }

                    $quality = $columns[52];
                    $value = $columns[61];
                    $uncertainty = $columns[62];

                    // PGN uses -9e99 for missing numerical values.
                    if (
                        ! is_numeric($value) ||
                        (float) $value <= -1e90 ||
                        ! is_numeric($quality)
                    ) {
                        continue;
                    }

                    $qualityFlag = (int) $quality;

                    if (! in_array(
                        $qualityFlag,
                        [0, 1, 2, 10, 11, 12, 20, 21, 22],
                        true
                    )) {
                        continue;
                    }

                    // Unusable observations are excluded from the chart.
                    if ($qualityFlag >= 20) {
                        continue;
                    }

                    $observations[] = [
                        'timestamp' => $columns[0],
                        'no2' => (float) $value,
                        'uncertainty' => is_numeric($uncertainty)
                            && (float) $uncertainty > -1e90
                                ? (float) $uncertainty
                                : null,
                        'quality_flag' => $qualityFlag,
                        'quality_assured' => $qualityFlag <= 2,
                    ];
                }

                return $observations;
            }
        );
    }
}
