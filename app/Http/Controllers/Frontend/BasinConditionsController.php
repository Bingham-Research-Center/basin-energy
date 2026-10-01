<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class BasinConditionsController
{
    public function index(): JsonResponse
    {
        try {
            // Get the list of available observation files from BasinWx.
            $fileListResponse = Http::timeout(45)
                ->get('https://basinwx.com/api/filelist/observations');

            if (!$fileListResponse->successful()) {
                return response()->json([
                    'error' => 'Unable to reach BasinWx.',
                ], 502);
            }

            $files = $fileListResponse->json();

            // Find the newest map observation file.
            $latestFile = collect($files)
                ->filter(function ($file) {
                    return preg_match(
                        '/^map_obs_\d{8}_\d{4}Z\.json$/',
                        $file
                    );
                })
                ->sort()
                ->last();

            if (!$latestFile) {
                return response()->json([
                    'error' => 'No current BasinWx observation file found.',
                ], 502);
            }

            // Download the newest observations.
            $dataResponse = Http::timeout(45)
                ->get(
                    'https://basinwx.com/api/static/observations/'
                    . $latestFile
                );

            if (!$dataResponse->successful()) {
                return response()->json([
                    'error' => 'Unable to retrieve BasinWx observations.',
                ], 502);
            }

            $observations = collect($dataResponse->json());

            /*
             * Vernal / KVEL
             */
            $vernal = $observations
                ->where('stid', 'KVEL');

            $temperature = $vernal
                ->where('variable', 'air_temp')
                ->sortByDesc('date_time')
                ->first();

            $wind = $vernal
                ->where('variable', 'wind_speed')
                ->sortByDesc('date_time')
                ->first();

            /*
             * PM2.5
             *
             * Use QCV, one of the BasinWx stations that
             * reports PM2.5.
             */
            $pm25 = $observations
                ->where('stid', 'QCV')
                ->where('variable', 'PM_25_concentration')
                ->sortByDesc('date_time')
                ->first();

            /*
             * NOx
             *
             * Use A1386, one of the BasinWx stations
             * reporting NOx.
             */
            $nox = $observations
                ->where('stid', 'A1386')
                ->where('variable', 'NOx_concentration')
                ->sortByDesc('date_time')
                ->first();

            // Celsius -> Fahrenheit
            $temperatureF = null;

            if ($temperature && is_numeric($temperature['value'])) {
                $temperatureF =
                    ($temperature['value'] * 9 / 5) + 32;
            }

            // meters/second -> miles/hour
            $windMph = null;

            if ($wind && is_numeric($wind['value'])) {
                $windMph =
                    $wind['value'] * 2.236936;
            }

            return response()->json([
                'temperature' => $temperatureF !== null
                    ? round($temperatureF, 1)
                    : null,

                'wind' => $windMph !== null
                    ? round($windMph, 1)
                    : null,

                'pm25' => $pm25
                    ? round((float) $pm25['value'], 1)
                    : null,

                'nox' => $nox
                    ? round((float) $nox['value'], 1)
                    : null,

                'updated_at' => collect([
                    $temperature['date_time'] ?? null,
                    $wind['date_time'] ?? null,
                    $pm25['date_time'] ?? null,
                    $nox['date_time'] ?? null,
                ])->filter()->sort()->last(),

                'stations' => [
                    'weather' => 'Vernal',
                    'pm25' => 'QCV',
                    'nox' => 'A1386',
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Unable to retrieve live BasinWx data.',
            ], 502);
        }
    }
}
