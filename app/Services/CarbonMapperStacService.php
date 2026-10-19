<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CarbonMapperStacService
{
    protected string $baseUrl = 'https://api.carbonmapper.org/api/v1/stac';

    protected string $token;

    public function __construct()
    {
        $this->token = config('services.carbon_mapper.token');
    }

    public function search(array $payload): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/geo+json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/search', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Carbon Mapper STAC error (' . $response->status() . '): ' .
                substr($response->body(), 0, 500)
            );
        }

        return $response->json();
    }

    /**
     * Follow a pagination link returned by the STAC API.
     */
    public function searchNext(array $link): array
    {
        $url = $link['href'] ?? null;
        $method = strtoupper($link['method'] ?? 'GET');

        if (
            ! $url ||
            ! in_array($method, ['GET', 'POST'], true) ||
            parse_url($url, PHP_URL_SCHEME) !== 'https' ||
            parse_url($url, PHP_URL_HOST) !== 'api.carbonmapper.org'
        ) {
            throw new \RuntimeException(
                'Carbon Mapper returned an invalid pagination link.'
            );
        }

        $request = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/geo+json',
            'Content-Type' => 'application/json',
        ]);

        $options = [];

        if ($method === 'POST') {
            $options['json'] = $link['body'] ?? [];
        }

        $response = $request->send($method, $url, $options);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Carbon Mapper pagination error (' . $response->status() . '): ' .
                substr($response->body(), 0, 500)
            );
        }

        return $response->json();
    }
}
