<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AiSummaryService
{
    public function summarizeEmissionTrends(array $payload): array
    {
        if (! config('services.ai_summary.enabled')) {
            return [
                'ok' => false,
                'summary' => 'AI summaries are currently disabled.',
            ];
        }

        $apiKey = config('services.openai.api_key');

        if (blank($apiKey)) {
            return [
                'ok' => false,
                'summary' => 'AI summary engine is not configured.',
            ];
        }

        $cacheKey = 'ai_summary_emission_' . md5(json_encode($payload));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($payload, $apiKey) {
            $response = Http::timeout(25)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => config('services.openai.model'),
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => 'You summarize environmental and oil/gas trend data for a public research dashboard. Write one brief, careful paragraph. Avoid overclaiming. Mention trends only from the provided data. Do not use bullet points.',
                        ],
                        [
                            'role' => 'user',
                            'content' => 'Summarize this selected emission trend dataset in one brief paragraph: '
                                . json_encode($payload, JSON_PRETTY_PRINT),
                        ],
                    ],
                ]);

            if (! $response->ok()) {
                return [
                    'ok' => false,
                    'summary' => 'AI summary could not be generated at this time.',
                    'debug' => $response->json(),
                ];
            }

            $json = $response->json();

            return [
                'ok' => true,
                'summary' => $this->extractText($json),
            ];
        });
    }

    private function extractText(array $json): string
    {
        if (! empty($json['output_text'])) {
            return trim($json['output_text']);
        }

        $parts = [];

        foreach ($json['output'] ?? [] as $output) {
            foreach ($output['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text') {
                    $parts[] = $content['text'] ?? '';
                }
            }
        }

        $text = trim(implode("\n", array_filter($parts)));

        return $text ?: 'AI summary was generated, but no readable text was returned.';
    }
}