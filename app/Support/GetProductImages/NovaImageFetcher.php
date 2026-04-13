<?php

namespace App\Support\GetProductImages;

class NovaImageFetcher extends BaseImageFetcher
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
        
        // Focus on main gallery images - exclude thumbnails (wd-gallery-thumb)
        // Prioritize woocommerce-product-gallery__image which contains the main gallery images
        $queries = [
            "//figure[contains(@class, 'woocommerce-product-gallery__image')]//img",
            "//*[contains(@class, 'woocommerce-product-gallery') and not(contains(@class, 'wd-gallery-thumb'))]//img",
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

                // Skip thumbnail images (150x150, etc.)
                $src = trim($node->getAttribute('src') ?? '');
                if (static::isThumbnail($src)) {
                    continue;
                }

                // Prioritize data-large_image for full-size images (Nova Tech uses this for full-size)
                $imageUrl = trim($node->getAttribute('data-large_image') ?? '');
                if (empty($imageUrl) || $imageUrl === 'data:image') {
                    $imageUrl = trim($node->getAttribute('src') ?? '');
                }
                if (empty($imageUrl) || $imageUrl === 'data:image') {
                    $imageUrl = trim($node->getAttribute('data-src') ?? '');
                }
                if (empty($imageUrl) || $imageUrl === 'data:image') {
                    $imageUrl = trim($node->getAttribute('data-lazy-src') ?? '');
                }
                if (empty($imageUrl) || $imageUrl === 'data:image') {
                    $imageUrl = trim($node->getAttribute('data-original') ?? '');
                }

                // Skip if empty, data URI, or doesn't look like an image URL
                if ($imageUrl === '' || str_starts_with($imageUrl, 'data:') || ! static::looksLikeImageUrl($imageUrl)) {
                    continue;
                }

                // Skip thumbnails even if passed previous checks
                if (static::isThumbnail($imageUrl)) {
                    continue;
                }

                $absolute = static::toAbsoluteUrl($imageUrl, $baseUrl);
                if (! in_array($absolute, $urls, true)) {
                    $urls[] = $absolute;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Check if an image URL is a thumbnail (contains size indicators like 150x150, 300x300, etc.).
     *
     * @param  string  $url
     * @return bool
     */
    protected static function isThumbnail(string $url): bool
    {
        // Check for common thumbnail size patterns in URLs
        return (bool) preg_match('#-\d{1,4}x\d{1,4}(\.(jpg|jpeg|png|webp|gif))#i', $url);
    }
}

