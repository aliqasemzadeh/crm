<?php

namespace App\Support\GetProductImages;

use Illuminate\Support\Facades\Http;

abstract class BaseImageFetcher
{
    /**
     * Fetch image URLs from a product page URL.
     *
     * @param  string  $url
     * @return array<int, string>
     */
    abstract public static function fetchImageUrls(string $url): array;

    /**
     * Get HTTP headers for requests.
     *
     * @return array<string, string>
     */
    protected static function getHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
        ];
    }

    /**
     * Make HTTP request to fetch HTML content.
     *
     * @param  string  $url
     * @return string|null
     */
    protected static function fetchHtml(string $url): ?string
    {
        try {
            $response = Http::withHeaders(static::getHeaders())
                ->timeout(30)
                ->retry(2, 1000)
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Throwable $e) {
            // Silently fail and return null
        }

        return null;
    }

    /**
     * Convert a possibly relative URL to an absolute URL using a base URL.
     *
     * @param  string  $src
     * @param  string  $baseUrl
     * @return string
     */
    protected static function toAbsoluteUrl(string $src, string $baseUrl): string
    {
        // Already absolute
        if (preg_match('#^https?://#i', $src)) {
            return $src;
        }

        $base = parse_url($baseUrl);
        if (! $base || ! isset($base['scheme'], $base['host'])) {
            return $src;
        }

        $scheme = $base['scheme'];
        $host = $base['host'];
        $port = isset($base['port']) ? ':' . $base['port'] : '';

        // If src starts with //, keep scheme
        if (strpos($src, '//') === 0) {
            return $scheme . ':' . $src;
        }

        // If src starts with /, it's relative to domain root
        if (strpos($src, '/') === 0) {
            return "{$scheme}://{$host}{$port}{$src}";
        }

        // Otherwise, relative to current path
        $path = $base['path'] ?? '/';
        // Remove filename if present
        if (substr($path, -1) !== '/') {
            $path = dirname($path) . '/';
        }

        return "{$scheme}://{$host}{$port}" . $path . $src;
    }

    /**
     * Check if a string looks like an image URL.
     *
     * @param  string  $value
     * @return bool
     */
    protected static function looksLikeImageUrl(string $value): bool
    {
        return (bool) preg_match('#\.(jpe?g|png|webp|gif)(\?|$)#i', $value);
    }

    /**
     * Factory method to get the appropriate fetcher based on URL.
     *
     * @param  string  $url
     * @return BaseImageFetcher|null
     */
    public static function getFetcher(string $url): ?BaseImageFetcher
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            return null;
        }

        $host = strtolower($host);

        if (str_contains($host, 'logitech.com')) {
            return new LogitechImageFetcher();
        }

        if (str_contains($host, 'logi-key.com')) {
            return new LogikeyImageFetcher();
        }

        if (str_contains($host, 'gigabyte.com')) {
            return new GigabyteImageFetcher();
        }

        if (str_contains($host, 'xvision.ir')) {
            return new XVisionImageFetcher();
        }

        if (str_contains($host, 'matin.store') || str_contains($host, 'matin.co')) {
            return new MatinImageFetcher();
        }

        if (str_contains($host, 'green.com') || str_contains($host, 'green.ir')) {
            return new GreenImageFetcher();
        }

        if (str_contains($host, 'faterco.ir')) {
            return new FaterImageFetcher();
        }

        if (str_contains($host, 'avajang.com')) {
            return new AvajangImageFetcher();
        }

        if (str_contains($host, 'nova-tech.ir')) {
            return new NovaImageFetcher();
        }

        if (str_contains($host, 'fafait.net')) {
            return new FafaitImageFetcher();
        }

        // Default generic fetcher
        return new GenericImageFetcher();
    }

    /**
     * Factory method to get the appropriate fetcher based on site type.
     *
     * @param  string  $siteType
     * @return BaseImageFetcher|null
     */
    public static function getFetcherBySiteType(string $siteType): ?BaseImageFetcher
    {
        return match ($siteType) {
            'logitech' => new LogitechImageFetcher(),
            'logikey' => new LogikeyImageFetcher(),
            'gigabyte' => new GigabyteImageFetcher(),
            'xvision' => new XVisionImageFetcher(),
            'matin' => new MatinImageFetcher(),
            'green' => new GreenImageFetcher(),
            'fater' => new FaterImageFetcher(),
            'avajang' => new AvajangImageFetcher(),
            'nova' => new NovaImageFetcher(),
            'fafait' => new FafaitImageFetcher(),
            'generic' => new GenericImageFetcher(),
            default => null,
        };
    }

    /**
     * Fetch image URLs using the appropriate fetcher for the given URL.
     *
     * @param  string  $url
     * @return array<int, string>
     */
    public static function fetch(string $url): array
    {
        $fetcher = self::getFetcher($url);
        if (! $fetcher) {
            return [];
        }

        // Call the static method on the fetcher instance's class
        return $fetcher::fetchImageUrls($url);
    }

    /**
     * Fetch image URLs using the specified site type fetcher.
     *
     * @param  string  $siteType
     * @param  string  $url
     * @return array<int, string>
     */
    public static function fetchBySiteType(string $siteType, string $url): array
    {
        $fetcher = self::getFetcherBySiteType($siteType);
        if (! $fetcher) {
            return [];
        }

        // Call the static method on the fetcher instance's class
        return $fetcher::fetchImageUrls($url);
    }
}

