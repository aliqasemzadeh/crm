<?php

namespace App\Support\GetProductImages;

use Illuminate\Support\Facades\Http;

class FaterImageFetcher extends BaseImageFetcher
{
    public static function fetchImageUrls(string $url): array
    {
        $apiUrl = static::buildApiUrl($url);

        try {
            $response = Http::withHeaders(static::getHeaders())
                ->timeout(30)
                ->get($apiUrl);

            if (! $response->ok()) {
                return [];
            }

            $contentType = $response->header('Content-Type', '');

            if (str_contains($contentType, 'application/json')) {
                $json = $response->json();
                if (is_array($json)) {
                    return static::extractImageUrlsFromApi($json);
                }
            } else {
                $html = $response->body();
                return static::extractImageUrls($html, $url);
            }
        } catch (\Throwable $e) {
            return [];
        }

        return [];
    }

    protected static function buildApiUrl(string $url): string
    {
        // If it's already an API URL, return as is
        if (str_contains($url, 'api.faterco.ir/api/v1/Product/GetProductDetail')) {
            return $url;
        }

        // Extract product slug from product page URL
        // Pattern: https://faterco.ir/product/{slug}
        if (preg_match('#faterco\.ir/product/([^/?]+)#i', $url, $matches)) {
            $slug = $matches[1];
            return "https://api.faterco.ir/api/v1/Product/GetProductDetail?id={$slug}";
        }

        // If pattern doesn't match, return original URL
        return $url;
    }

    protected static function extractImageUrlsFromApi(array $json): array
    {
        $urls = [];
        static::collectImageUrlsFromValue($json, $urls);
        return array_values(array_unique($urls));
    }

    /**
     * Recursively walk the JSON structure and collect image URLs.
     *
     * @param  mixed  $value
     * @param  array<int, string>  $urls
     */
    protected static function collectImageUrlsFromValue(mixed $value, array &$urls): void
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                static::collectImageUrlsFromValue($v, $urls);
            }
            return;
        }

        if (! is_string($value)) {
            return;
        }

        // Only keep strings that look like image URLs
        if (! static::looksLikeImageUrl($value)) {
            return;
        }

        $normalized = static::normalizeFaterImageUrl($value);

        if (! in_array($normalized, $urls, true)) {
            $urls[] = $normalized;
        }
    }

    /**
     * Normalize image URLs from Fater:
     * - If already absolute, return as is
     * - If protocol-relative (//...), prefix https:
     * - If path-like starting with /images, prefix https://admin.faterco.ir
     */
    protected static function normalizeFaterImageUrl(string $value): string
    {
        $trimmed = trim($value);

        // Absolute URL
        if (preg_match('#^https?://#i', $trimmed)) {
            return $trimmed;
        }

        // Protocol-relative URL
        if (strpos($trimmed, '//') === 0) {
            return 'https:' . $trimmed;
        }

        // URLs starting with /images -> admin.faterco.ir
        if (strpos($trimmed, '/images') === 0) {
            return 'https://admin.faterco.ir' . $trimmed;
        }

        return $trimmed;
    }

    protected static function extractImageUrls(string $html, string $baseUrl): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        
        // XPath queries for swiper containers (same as FaterFetcherCommand)
        $queries = [
            "//*[contains(@class, 'swiper') and contains(@class, 'product-swiper-main')]//img[@src]",
            "//*[contains(@class, 'swiper-wrapper')]//img[@src]",
            "//*[contains(@class, 'swiper-slide')]//img[@src]",
            "//*[contains(@class, 'swiper')]//img[@src]",
        ];

        $urls = [];

        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            
            if (! $nodes) {
                continue;
            }

            foreach ($nodes as $node) {
                if (! ($node instanceof \DOMElement)) {
                    continue;
                }

                $src = trim($node->getAttribute('src') ?? '');
                if ($src === '') {
                    continue;
                }

                // Also check data-src for lazy-loaded images
                if (empty($src) || $src === 'data:image') {
                    $src = trim($node->getAttribute('data-src') ?? '');
                }

                // Check data-lazy-src for swiper lazy loading
                if (empty($src) || $src === 'data:image') {
                    $src = trim($node->getAttribute('data-lazy-src') ?? '');
                }

                if ($src === '') {
                    continue;
                }

                $absolute = static::toAbsoluteUrl($src, $baseUrl);

                if (! in_array($absolute, $urls, true)) {
                    $urls[] = $absolute;
                }
            }
        }

        return array_values(array_unique($urls));
    }
}

