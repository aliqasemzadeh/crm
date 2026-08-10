<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SevenUpLinkClient
{
    public static function shorten(string $destination): ?string
    {
        $token = (string) config('sevenup.token');
        $endpoint = (string) config('sevenup.endpoint', 'https://7ul.ir/api/v1/links');
        $baseUrl = rtrim((string) config('sevenup.base_url', 'https://7ul.ir'), '/');

        if ($token === '') {
            Log::warning('SevenUp link shorten skipped: missing SEVENUP_TOKEN');

            return null;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->timeout(10)
                ->post($endpoint, [
                    'destination' => $destination,
                    'type' => 'link',
                    'is_public_stats' => false,
                ]);

            $payload = $response->json() ?? [];

            Log::info('SevenUp link create attempt', [
                'http_status' => $response->status(),
                'response' => $payload,
            ]);

            if (! $response->successful()) {
                Log::error('SevenUp link create failed', [
                    'http_status' => $response->status(),
                    'response' => $payload,
                ]);

                return null;
            }

            $shortUrl = self::extractShortUrl($payload, $baseUrl);

            if ($shortUrl === null) {
                Log::error('SevenUp link create succeeded but short URL missing', [
                    'response' => $payload,
                ]);
            }

            return $shortUrl;
        } catch (\Throwable $e) {
            Log::error('SevenUp link create exception: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function extractShortUrl(array $payload, string $baseUrl): ?string
    {
        $candidates = [
            data_get($payload, 'url'),
            data_get($payload, 'short_url'),
            data_get($payload, 'data.url'),
            data_get($payload, 'data.short_url'),
            data_get($payload, 'link.url'),
            data_get($payload, 'link.short_url'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && filter_var($candidate, FILTER_VALIDATE_URL)) {
                return $candidate;
            }
        }

        $shortCode = data_get($payload, 'short_code')
            ?? data_get($payload, 'shortCode')
            ?? data_get($payload, 'data.short_code')
            ?? data_get($payload, 'data.shortCode')
            ?? data_get($payload, 'link.short_code')
            ?? data_get($payload, 'link.shortCode');

        if (is_string($shortCode) && preg_match('/^[A-Za-z0-9]{8}$/', $shortCode)) {
            return $baseUrl.'/'.$shortCode;
        }

        return null;
    }
}
