<?php

namespace App\Support\GetProductImages;

class FafaitImageFetcher extends BaseImageFetcher
{
    public static function fetchImageUrls(string $url): array
    {
        $html = static::fetchHtml($url);
        if (! $html) {
            return [];
        }

        return static::extractImageUrls($html, $url);
    }

    protected static function extractImageUrls(string $html, string $baseUrl): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        
        // XPath queries for Splide carousel (fafait.net uses Splide.js)
        // Priority: main gallery (#img-gallery) first, then fallbacks
        $queries = [
            "//*[@id='img-gallery']//li[contains(@class, 'splide__slide')]//img[@src]",
            "//*[@id='img-gallery']//img[@src]",
            "//*[contains(@class, 'splide') and contains(@class, 'gallery') and not(contains(@class, 'gallery-thumbnails'))]//li[contains(@class, 'splide__slide')]//img[@src]",
            "//*[contains(@class, 'splide__list') and @id='img-gallery-list']//li[contains(@class, 'splide__slide')]//img[@src]",
            "//*[contains(@class, 'splide__slide')]//img[@src]",
            // Fallback to generic gallery queries
            "//*[contains(@class, 'gallery') and not(contains(@class, 'gallery-thumbnails'))]//img[@src]",
            "//*[contains(@class, 'product-gallery')]//img[@src]",
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
                
                // Also check data-src for lazy-loaded images
                if (empty($src) || $src === 'data:image') {
                    $src = trim($node->getAttribute('data-src') ?? '');
                }
                
                // Check data-lazy-src for lazy loading
                if (empty($src) || $src === 'data:image') {
                    $src = trim($node->getAttribute('data-lazy-src') ?? '');
                }
                
                // Check data-original
                if (empty($src) || $src === 'data:image') {
                    $src = trim($node->getAttribute('data-original') ?? '');
                }

                if ($src === '' || str_starts_with($src, 'data:') || ! static::looksLikeImageUrl($src)) {
                    continue;
                }

                $absolute = static::toAbsoluteUrl($src, $baseUrl);
                
                // Remove query parameters (like ?width=1400&quality=100) to get original image
                $absolute = static::removeQueryParams($absolute);
                
                // Filter: Only keep images from liara.fafait.net/upload/ (main product images)
                // Exclude thumbnails and other assets
                if (static::isFafaitProductImage($absolute)) {
                    if (! in_array($absolute, $urls, true)) {
                        $urls[] = $absolute;
                    }
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Remove query parameters from URL to get original image.
     *
     * @param  string  $url
     * @return string
     */
    protected static function removeQueryParams(string $url): string
    {
        $parsed = parse_url($url);
        if (! $parsed) {
            return $url;
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';

        return "{$scheme}://{$host}{$path}";
    }

    /**
     * Check if URL is a fafait.net product image.
     *
     * @param  string  $url
     * @return bool
     */
    protected static function isFafaitProductImage(string $url): bool
    {
        // Must be from liara.fafait.net or fafait.net
        if (! str_contains($url, 'fafait.net')) {
            return false;
        }

        // Prefer images from /upload/ path (main product images)
        if (str_contains($url, 'liara.fafait.net/upload/')) {
            return true;
        }

        // Exclude thumbnails, resized images, and assets
        $excludePatterns = [
            '/thumbnail/',
            '/thumb/',
            '/resize-on-fly/',
            '/assets.fafait.net/',
            '/icon/',
            '/logo/',
            '/avatar/',
            '/favicon/',
        ];

        foreach ($excludePatterns as $pattern) {
            if (str_contains(strtolower($url), $pattern)) {
                return false;
            }
        }

        // If it's from fafait.net and looks like an image, include it
        return static::looksLikeImageUrl($url);
    }

}

