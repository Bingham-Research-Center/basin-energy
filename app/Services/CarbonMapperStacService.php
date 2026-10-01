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
                'Accept'        => 'application/geo+json',
                'Content-Type'  => 'application/json',
            ])
            ->post($this->baseUrl . '/search', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Carbon Mapper STAC error (' . $response->status() . '): ' .
                substr($response->body(), 0, 500)
            );
        }

        return $response->json();
    }

}
