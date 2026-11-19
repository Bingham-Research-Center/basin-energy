<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Read-only, cached NWS station observation for the admin overview.
 *
 * KVEL is Vernal Regional Airport: one representative location in the
 * Uintah Basin, not a basin-wide weather average. No API key is required.
 */
class UintahBasinWeatherService
{
    private const OBSERVATION_URL = 'https://api.weather.gov/stations/KVEL/observations/latest';

    private const CACHE_KEY = 'admin.weather.kvel.latest.v1';
    private const LAST_GOOD_KEY = 'admin.weather.kvel.last_good.v1';

    public function current(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/geo+json',
                // NWS requests a recognizable User-Agent with contact details.
                'User-Agent' => (string) config('services.weather_gov.user_agent'),
            ])
                ->connectTimeout(2)
                ->timeout(6)
                ->get(self::OBSERVATION_URL);

            if (! $response->successful()) {
                throw new RuntimeException('NWS observation returned HTTP '.$response->status());
            }

            $properties = $response->json('properties');

            if (! is_array($properties) || empty($properties['timestamp'])) {
                throw new RuntimeException('NWS observation timestamp is missing.');
            }

            $observedAt = CarbonImmutable::parse($properties['timestamp']);

            $temperature = $this->reading($properties, 'temperature');
            $wind = $this->reading($properties, 'windSpeed');
            $humidity = $this->reading($properties, 'relativeHumidity');
            $windDirection = $this->reading($properties, 'windDirection');

            $temperatureF = match ($temperature['unit']) {
                'wmoUnit:degC' => $temperature['value'] === null
                    ? null : round($temperature['value'] * 9 / 5 + 32),
                'wmoUnit:degF' => $temperature['value'] === null
                    ? null : round($temperature['value']),
                default => null,
            };

            $windMph = match ($wind['unit']) {
                'wmoUnit:km_h-1' => $wind['value'] === null
                    ? null : round($wind['value'] * 0.621371, 1),
                'wmoUnit:m_s-1' => $wind['value'] === null
                    ? null : round($wind['value'] * 2.236936, 1),
                'wmoUnit:kn' => $wind['value'] === null
                    ? null : round($wind['value'] * 1.150779, 1),
                default => null,
            };

            $humidityPercent = $humidity['unit'] === 'wmoUnit:percent'
                && $humidity['value'] !== null
                && $humidity['value'] >= 0
                && $humidity['value'] <= 100
                    ? round($humidity['value']) : null;

            $degrees = $windDirection['value'];
            $direction = $degrees !== null
                && $windDirection['unit'] === 'wmoUnit:degree_(angle)'
                ? $this->compassDirection($degrees) : null;

            $description = trim((string) ($properties['textDescription'] ?? ''));
            $description = $description !== '' ? $description : null;

            if ($temperatureF === null && $windMph === null
                && $humidityPercent === null && $description === null) {
                throw new RuntimeException('NWS observation contains no usable measurements.');
            }

            $result = [
                'ok' => true,
                'station' => 'Vernal Regional Airport (KVEL)',
                'location' => 'Uintah Basin, Utah',
                'observed_at' => $observedAt->toIso8601String(),
                'temperature_f' => $temperatureF,
                'wind_mph' => $windMph,
                'wind_direction' => $direction,
                'humidity_percent' => $humidityPercent,
                'conditions' => $description,
                'stale' => $observedAt->lt(now()->subHours(2)),
                'message' => null,
            ];

            Cache::put(self::CACHE_KEY, $result, now()->addMinutes(10));
            // Preserve a recent known-good report across transient NWS outages.
            Cache::put(self::LAST_GOOD_KEY, $result, now()->addHours(2));

            return $result;
        } catch (Throwable $exception) {
            Log::warning('NWS KVEL observation unavailable', [
                'reason' => $exception->getMessage(),
            ]);

            $lastGood = Cache::get(self::LAST_GOOD_KEY);

            if (is_array($lastGood) && ($lastGood['ok'] ?? false)) {
                $result = array_merge($lastGood, [
                    'stale' => true,
                    'message' => 'Weather service is unavailable; showing the last successful observation.',
                ]);
            } else {
                $result = [
                    'ok' => false,
                    'station' => 'Vernal Regional Airport (KVEL)',
                    'location' => 'Uintah Basin, Utah',
                    'observed_at' => null,
                    'temperature_f' => null,
                    'wind_mph' => null,
                    'wind_direction' => null,
                    'humidity_percent' => null,
                    'conditions' => null,
                    'stale' => false,
                    'message' => 'Weather observations are temporarily unavailable.',
                ];
            }

            // Back off briefly after a failure; keep serving a recent last-good
            // observation if available rather than hammering the NWS endpoint.
            Cache::put(self::CACHE_KEY, $result, now()->addMinute());

            return $result;
        }
    }

    private function reading(array $properties, string $key): array
    {
        $value = data_get($properties, $key.'.value');

        return [
            'value' => is_numeric($value) ? (float) $value : null,
            'unit' => (string) data_get($properties, $key.'.unitCode', ''),
        ];
    }

    private function compassDirection(float $degrees): string
    {
        $directions = [
            'N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE',
            'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW',
        ];

        return $directions[((int) floor(fmod($degrees + 360, 360) / 22.5 + 0.5)) % 16];
    }
}
