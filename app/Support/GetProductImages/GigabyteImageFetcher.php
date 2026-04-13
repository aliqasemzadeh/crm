<?php

namespace App\Support\GetProductImages;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GigabyteImageFetcher extends BaseImageFetcher
{
    public static function fetchImageUrls(string $url): array
    {
        Log::info("GigabyteImageFetcher: Fetching from {$url}");
        try {
            $proxy = collect(config('proxy.proxies'))->random();

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Referer' => 'https://www.gigabyte.com/',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'same-origin',
                'Sec-Fetch-User' => '?1',
                'Upgrade-Insecure-Requests' => '1',
            ])
                ->withOptions([
                    'proxy' => $proxy,
                ])
                ->timeout(30)
                ->retry(2, 1000)
                ->get($url);

            // If we get 403, try with simpler headers as fallback (same as GigaByteFetcherCommand)
            if ($response->status() === 403) {
                Log::warning('GigabyteImageFetcher: Received 403, trying with simpler headers...');
                try {
                    $proxy = collect(config('proxy.proxies'))->random();
                    $response = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'Accept-Language' => 'en-US,en;q=0.9',
                    ])
                        ->withOptions([
                            'proxy' => $proxy,
                        ])
                        ->timeout(30)
                        ->get($url);
                } catch (\Throwable $e) {
                    Log::error('GigabyteImageFetcher: Fallback request failed: '.$e->getMessage());

                    return [];
                }
            }

            if (! $response->ok()) {
                Log::error('GigabyteImageFetcher: Request failed with status: '.$response->status());

                return [];
            }

            $contentType = $response->header('Content-Type', '');
            Log::info("GigabyteImageFetcher: Content-Type: {$contentType}");

            if (str_contains($contentType, 'application/json')) {
                $json = $response->json();
                if (is_array($json)) {
                    Log::info('GigabyteImageFetcher: Detected JSON response.');

                    return static::extractImageUrlsFromApi($json);
                }
            } else {
                $html = $response->body();
                Log::info('GigabyteImageFetcher: Detected HTML response. Length: '.strlen($html));

                $allUrls = static::extractImageUrls($html, $url);
                Log::info('GigabyteImageFetcher: Found '.count($allUrls).' URLs total.');

                // Filter to only static.gigabyte.com URLs with /product/ (including incomplete URLs ending with /Product)
                $staticUrls = array_filter($allUrls, function ($url) {
                    $lowerUrl = strtolower($url);

                    // Accept URLs that contain static.gigabyte.com and /product (with or without trailing slash or extension)
                    return str_contains($lowerUrl, 'static.gigabyte.com') &&
                           (str_contains($lowerUrl, '/product/') || preg_match('#/product[^/]*$#i', $lowerUrl));
                });

                Log::info('GigabyteImageFetcher: Found '.count($staticUrls).' static.gigabyte.com product URLs.');

                // If no Product URLs found but static.gigabyte.com exists in HTML, wait and retry (for JavaScript-loaded content)
                $productUrls = array_filter($staticUrls, function ($url) {
                    return str_contains(strtolower($url), '/product/');
                });

                // Store the HTML content that will be used for product ID extraction
                $htmlContent = $html;

                if (empty($productUrls) && str_contains(strtolower($html), 'static.gigabyte.com')) {
                    Log::info('GigabyteImageFetcher: No Product URLs found, retrying after sleep...');
                    // Wait a bit for JavaScript to potentially load content
                    sleep(2);

                    try {
                        $proxy = collect(config('proxy.proxies'))->random();
                        $retryResponse = Http::withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                            'Referer' => 'https://www.gigabyte.com/',
                        ])
                            ->withOptions([
                                'proxy' => $proxy,
                            ])
                            ->timeout(30)
                            ->get($url);

                        if ($retryResponse->ok()) {
                            $retryHtml = $retryResponse->body();
                            Log::info('GigabyteImageFetcher: Retry successful. Length: '.strlen($retryHtml));
                            $retryUrls = static::extractImageUrls($retryHtml, $url);
                            $allUrls = array_merge($allUrls, $retryUrls);

                            // Re-filter static URLs
                            $staticUrls = array_filter($allUrls, function ($url) {
                                $lowerUrl = strtolower($url);

                                return str_contains($lowerUrl, 'static.gigabyte.com') &&
                                       (str_contains($lowerUrl, '/product/') || str_contains($lowerUrl, '/product'));
                            });

                            // Use retry HTML if it might have more product IDs
                            $htmlContent = $retryHtml;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('GigabyteImageFetcher: Retry failed: '.$e->getMessage());
                    }
                }

                // Convert to webp with largest size (1200)
                // Pass the HTML content (which might be from retry) for better product ID extraction
                $finalUrls = static::convertToWebpUrls(array_values($staticUrls), $htmlContent);
                Log::info('GigabyteImageFetcher: Final webp URLs count before deduplication: '.count($finalUrls));

                // Final deduplication
                $finalUrls = array_values(array_unique($finalUrls));
                Log::info('GigabyteImageFetcher: Final webp URLs count after deduplication: '.count($finalUrls));

                return $finalUrls;
            }
        } catch (\Throwable $e) {
            Log::error('GigabyteImageFetcher: Exception: '.$e->getMessage());

            return [];
        }

        return [];
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

        $normalized = static::normalizeGigabyteImageUrl($value);

        if (! in_array($normalized, $urls, true)) {
            $urls[] = $normalized;
        }
    }

    /**
     * Normalize image URLs from Gigabyte:
     * - If already absolute, return as is
     * - If protocol-relative (//...), prefix https:
     * - If path-like starting with /, prefix https://www.gigabyte.com
     */
    protected static function normalizeGigabyteImageUrl(string $value): string
    {
        $trimmed = trim($value);

        // Absolute URL
        if (preg_match('#^https?://#i', $trimmed)) {
            return $trimmed;
        }

        // Protocol-relative URL
        if (strpos($trimmed, '//') === 0) {
            return 'https:'.$trimmed;
        }

        // URLs starting with / -> www.gigabyte.com
        if (strpos($trimmed, '/') === 0) {
            return 'https://www.gigabyte.com'.$trimmed;
        }

        return $trimmed;
    }

    protected static function extractImageUrls(string $html, string $baseUrl): array
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // XPath queries for gallery images (same as GigaByteFetcherCommand)
        $queries = [
            "//*[contains(@class, 'swiper-wrapper')]//*[contains(@class, 'modal-thumbnail-show-image')]//img",
            "//*[contains(@class, 'swiper-wrapper')]//*[contains(@class, 'lazyFadeIn')]//img",
            "//*[contains(@class, 'swiper-wrapper')]//*[contains(@class, 'entered')]//img",
            "//*[contains(@class, 'swiper-wrapper')]//img",
            "//*[contains(@class, 'js-galleryModalSwiper') or contains(@class, 'gallery-modal-swiper')]//img",
            "//*[contains(@class, 'js-galleryModalSwiper') or contains(@class, 'gallery-modal-swiper')]//*[contains(@class, 'swiper-slide')]//img",
            "//*[contains(@class, 'gallery')]//img",
            "//*[contains(@class, 'product-gallery')]//img",
            "//*[contains(@class, 'swiper')]//img",
            "//*[contains(@id, 'gallery')]//img",
            '//img',
        ];

        $sourceQueries = [
            "//*[contains(@class, 'swiper-wrapper')]//*[contains(@class, 'modal-thumbnail-show-image')]//source",
            "//*[contains(@class, 'swiper-wrapper')]//*[contains(@class, 'lazyFadeIn')]//source",
            "//*[contains(@class, 'swiper-wrapper')]//*[contains(@class, 'entered')]//source",
            "//*[contains(@class, 'swiper-wrapper')]//source",
            "//*[contains(@class, 'js-galleryModalSwiper') or contains(@class, 'gallery-modal-swiper')]//source",
            "//*[contains(@class, 'gallery')]//source",
            "//*[contains(@class, 'swiper')]//source",
            '//source',
        ];

        $urls = [];

        // Extract from img tags
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
                if (empty($src) || $src === 'data:image') {
                    $src = trim($node->getAttribute('data-src') ?? '');
                }
                if (empty($src) || $src === 'data:image') {
                    $src = trim($node->getAttribute('data-lazy-src') ?? '');
                }
                if (empty($src) || $src === 'data:image') {
                    $src = trim($node->getAttribute('data-original') ?? '');
                }
                if (empty($src) || $src === 'data:image') {
                    $srcset = trim($node->getAttribute('data-srcset') ?? '');
                    if ($srcset !== '') {
                        $parts = preg_split('/\s*,\s*/', $srcset);
                        if (! empty($parts)) {
                            $first = trim(explode(' ', $parts[0])[0]);
                            $src = $first;
                        }
                    }
                }
                if (empty($src) || $src === 'data:image') {
                    $style = (string) $node->getAttribute('style');
                    $bg = static::extractBackgroundImageUrl($style);
                    if ($bg !== null) {
                        $src = $bg;
                    }
                }

                if ($src === '' || str_starts_with($src, 'data:') || ! static::looksLikeImageUrl($src)) {
                    continue;
                }

                $absolute = static::toAbsoluteUrl($src, $baseUrl);
                if (! in_array($absolute, $urls, true)) {
                    $urls[] = $absolute;
                }
            }
        }

        // Extract from source tags
        foreach ($sourceQueries as $query) {
            $nodes = $xpath->query($query);

            if (! $nodes) {
                continue;
            }

            foreach ($nodes as $node) {
                if (! ($node instanceof \DOMElement)) {
                    continue;
                }

                $srcset = trim($node->getAttribute('srcset') ?? '');
                if ($srcset === '') {
                    $srcset = trim($node->getAttribute('data-srcset') ?? '');
                }

                if ($srcset !== '') {
                    $parts = preg_split('/\s*,\s*/', $srcset);
                    foreach ($parts as $part) {
                        $urlPart = trim(explode(' ', trim($part))[0]);
                        if ($urlPart !== '') {
                            $absolute = static::toAbsoluteUrl($urlPart, $baseUrl);
                            if (static::looksLikeImageUrl($absolute) && ! in_array($absolute, $urls, true)) {
                                $urls[] = $absolute;
                            }
                        }
                    }
                }

                $src = trim($node->getAttribute('src') ?? '');
                if ($src === '' || str_starts_with($src, 'data:')) {
                    $src = trim($node->getAttribute('data-src') ?? '');
                }

                if ($src !== '' && ! str_starts_with($src, 'data:')) {
                    $absolute = static::toAbsoluteUrl($src, $baseUrl);
                    if (static::looksLikeImageUrl($absolute) && ! in_array($absolute, $urls, true)) {
                        $urls[] = $absolute;
                    }
                }
            }
        }

        // Also try to extract from JSON-LD or script tags that might contain image data
        $urls = array_merge($urls, static::extractFromScriptTags($html, $baseUrl));

        // Also try regex extraction for static.gigabyte.com URLs (in case DOM parsing misses them)
        $regexUrls = static::extractStaticUrlsWithRegex($html);
        $urls = array_merge($urls, $regexUrls);

        return array_values(array_unique($urls));
    }

    /**
     * Extract image URLs from script tags (JSON-LD, inline JSON, etc.)
     */
    protected static function extractFromScriptTags(string $html, string $baseUrl): array
    {
        $urls = [];

        // Look for JSON-LD or inline JSON with image URLs
        if (preg_match_all('#<script[^>]*>(.*?)</script>#is', $html, $matches)) {
            foreach ($matches[1] as $scriptContent) {
                // Pattern 1: Find static.gigabyte.com URLs with /Product (including incomplete URLs)
                if (preg_match_all('#["\']([^"\']*static\.gigabyte\.com[^"\']*Product[^"\']*?)["\']#i', $scriptContent, $imgMatches)) {
                    foreach ($imgMatches[1] as $imgUrl) {
                        $trimmed = trim($imgUrl);
                        $trimmed = rtrim($trimmed, '",\'})];');
                        $absolute = static::toAbsoluteUrl($trimmed, $baseUrl);

                        // Accept URLs that contain /Product (even if incomplete)
                        $lowerAbsolute = strtolower($absolute);
                        if (str_contains($lowerAbsolute, 'static.gigabyte.com') &&
                            (str_contains($lowerAbsolute, '/product/') || preg_match('#/product[^/]*$#i', $lowerAbsolute)) &&
                            ! in_array($absolute, $urls, true)) {
                            $urls[] = $absolute;
                        }
                    }
                }

                // Pattern 2: URLs with image extensions (original pattern)
                if (preg_match_all('#["\']([^"\']*\.(?:jpe?g|png|webp|gif)[^"\']*)["\']#i', $scriptContent, $imgMatches)) {
                    foreach ($imgMatches[1] as $imgUrl) {
                        $absolute = static::toAbsoluteUrl($imgUrl, $baseUrl);
                        if (static::looksLikeImageUrl($absolute) && ! in_array($absolute, $urls, true)) {
                            $urls[] = $absolute;
                        }
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * Extract static.gigabyte.com URLs using regex (for cases where DOM parsing might miss them).
     */
    protected static function extractStaticUrlsWithRegex(string $html): array
    {
        $urls = [];

        // First, try a very simple pattern: find anything between static.gigabyte.com and /Product/
        $simplePattern = '#static\.gigabyte\.com[^\s<>"\']*?/Product/[^\s<>"\']*?#i';

        $matchCount = preg_match_all($simplePattern, $html, $simpleMatches);

        if ($matchCount > 0) {
            foreach ($simpleMatches[0] as $url) {
                $trimmed = trim($url);
                // Remove trailing characters that might be part of JavaScript/JSON syntax
                $trimmed = rtrim($trimmed, '",\'})];');

                // Remove query strings
                if (str_contains($trimmed, '?')) {
                    $parts = explode('?', $trimmed, 2);
                    $trimmed = $parts[0];
                }

                // Clean up any trailing slashes or invalid characters
                $trimmed = rtrim($trimmed, '/');

                // Normalize URL: add https:// if missing
                if (! preg_match('#^https?://#i', $trimmed)) {
                    $trimmed = 'https://'.ltrim($trimmed, '/');
                }

                // Always add if it contains /Product (with or without trailing slash)
                $lowerTrimmed = strtolower($trimmed);
                if ($trimmed !== '' && (str_contains($lowerTrimmed, '/product/') || str_contains($lowerTrimmed, '/product')) && ! in_array($trimmed, $urls, true)) {
                    $urls[] = $trimmed;
                }
            }
        }

        // Also try more specific patterns
        $patterns = [
            '#https?://static\.gigabyte\.com/[^\s<>"\']+/Product/[^\s<>"\']+/(?:webp|png|jpg|jpeg)/\d+#i',
            '#https?://static\.gigabyte\.com/[^\s<>"\']+/Product/[^\s<>"\']+\.(?:jpe?g|png|webp|gif)(?:\?[^\s<>"\']*)?#i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[0] as $url) {
                    $trimmed = trim($url);
                    $trimmed = rtrim($trimmed, '",\'})];');

                    if (str_contains($trimmed, '?')) {
                        $parts = explode('?', $trimmed, 2);
                        $trimmed = $parts[0];
                    }

                    if ($trimmed !== '' && ! in_array($trimmed, $urls, true)) {
                        $urls[] = $trimmed;
                    }
                }
            }
        }

        // Also try to extract from srcset attributes
        $srcsetPattern = '#(?:srcset|data-srcset)=["\']([^"\']*https?://static\.gigabyte\.com[^"\']*Product[^"\']*)["\']#i';
        if (preg_match_all($srcsetPattern, $html, $srcsetMatches)) {
            foreach ($srcsetMatches[1] as $srcset) {
                $parts = preg_split('/\s*,\s*/', $srcset);
                foreach ($parts as $part) {
                    $urlPart = trim(explode(' ', trim($part))[0]);
                    $urlPart = rtrim($urlPart, '",\'})];');

                    if ($urlPart !== '' && str_contains(strtolower($urlPart), '/product/') && ! in_array($urlPart, $urls, true)) {
                        $urls[] = $urlPart;
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * Extract URL from CSS background-image definition in style attribute.
     */
    protected static function extractBackgroundImageUrl(string $style): ?string
    {
        if ($style === '') {
            return null;
        }

        if (preg_match('#background-image\s*:\s*url\((["\']?)([^)]+?)\1\)#i', $style, $m)) {
            $url = trim($m[2], " \t\n\r\0\x0B'\"");

            return $url !== '' ? $url : null;
        }

        return null;
    }

    protected static function convertToWebpUrls(array $urls, ?string $html = null): array
    {
        $webpUrls = [];
        $largestSize = 1200;

        // Extract product IDs from URLs first
        $productIds = [];
        foreach ($urls as $url) {
            if (preg_match('#/Product/(\d+)#i', $url, $match)) {
                $productIds[] = $match[1];
            }
        }
        $productIds = array_unique($productIds);

        // If no Product ID found in URLs, try to extract from HTML (if available)
        // This is important because incomplete URLs ending with /Product need product IDs from HTML
        // Use the same extraction logic as GigaByteFetcherCommand
        if ($html !== null) {
            // Extract from HTML using the same pattern as GigaByteFetcherCommand
            if (preg_match_all('#/Product/(\d+)#i', $html, $htmlMatches)) {
                $htmlProductIds = array_unique($htmlMatches[1]);
                // Merge with product IDs found in URLs
                $productIds = array_merge($productIds, $htmlProductIds);
                $productIds = array_unique($productIds);
            }
        }

        Log::info('GigabyteImageFetcher: Found product IDs: '.implode(', ', $productIds));

        foreach ($urls as $url) {
            $lowerUrl = strtolower($url);

            // If URL is incomplete (ends with /Product), try to complete it
            if (preg_match('#/product$#i', $url) || preg_match('#/product/$#i', $url)) {
                $baseUrl = rtrim($url, '/');

                // Try each Product ID with largest size only
                if (! empty($productIds)) {
                    foreach ($productIds as $productId) {
                        $completeUrl = "{$baseUrl}/{$productId}/webp/{$largestSize}";
                        if (! in_array($completeUrl, $webpUrls, true)) {
                            $webpUrls[] = $completeUrl;
                        }
                    }
                } else {
                    // If Product ID not found, try common Product IDs
                    $commonProductIds = ['47498', '47499', '47500'];
                    foreach ($commonProductIds as $productId) {
                        $completeUrl = "{$baseUrl}/{$productId}/webp/{$largestSize}";
                        if (! in_array($completeUrl, $webpUrls, true)) {
                            $webpUrls[] = $completeUrl;
                        }
                    }
                }
            }
            // If it's already webp, convert to largest size if it has a size parameter
            elseif (str_contains($lowerUrl, '.webp') || str_contains($lowerUrl, '/webp/')) {
                if (preg_match('#/webp/(\d+)#i', $url, $sizeMatch)) {
                    $largestSizeUrl = preg_replace('#/webp/\d+#i', "/webp/{$largestSize}", $url);
                    if (! in_array($largestSizeUrl, $webpUrls, true)) {
                        $webpUrls[] = $largestSizeUrl;
                    }
                } else {
                    if (! in_array($url, $webpUrls, true)) {
                        $webpUrls[] = $url;
                    }
                }
            }
            // If it's PNG, convert to webp with largest size
            elseif (str_contains($lowerUrl, '/png/')) {
                $webpUrl = str_replace('/png/', '/webp/', $url);
                $webpUrl = str_replace('.png', '.webp', $webpUrl);
                if (preg_match('#/webp/(\d+)#i', $webpUrl, $sizeMatch)) {
                    $webpUrl = preg_replace('#/webp/\d+#i', "/webp/{$largestSize}", $webpUrl);
                }
                if (! in_array($webpUrl, $webpUrls, true)) {
                    $webpUrls[] = $webpUrl;
                }
            }
            // If it's jpg/jpeg, try to convert to webp with largest size
            elseif (str_contains($lowerUrl, '/jpg/') || str_contains($lowerUrl, '/jpeg/')) {
                $webpUrl = str_replace(['/jpg/', '/jpeg/'], '/webp/', $url);
                $webpUrl = preg_replace('/\.(jpg|jpeg)$/i', '.webp', $webpUrl);
                if (preg_match('#/webp/(\d+)#i', $webpUrl, $sizeMatch)) {
                    $webpUrl = preg_replace('#/webp/\d+#i', "/webp/{$largestSize}", $webpUrl);
                }
                if (! in_array($webpUrl, $webpUrls, true)) {
                    $webpUrls[] = $webpUrl;
                }
            }
            // Handle complete URLs that don't have extension or size yet but have Product ID
            // For example: https://static.gigabyte.com/.../Product/44008
            elseif (preg_match('#/Product/(\d+)$#i', $url, $match)) {
                $completeUrl = "{$url}/webp/{$largestSize}";
                if (! in_array($completeUrl, $webpUrls, true)) {
                    $webpUrls[] = $completeUrl;
                }
            }
            // If it contains /Product/ but no size/ext, and we have IDs
            // This might catch some more variants
        }

        return $webpUrls;
    }
}
