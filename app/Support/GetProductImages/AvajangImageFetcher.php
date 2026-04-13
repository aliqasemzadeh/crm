<?php

namespace App\Support\GetProductImages;

class AvajangImageFetcher extends BaseImageFetcher
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
        
        // Priority queries for product images on Avajang
        $queries = [
            // Product gallery images
            "//*[contains(@class, 'product-gallery')]//img",
            "//*[contains(@class, 'product-images')]//img",
            "//*[contains(@class, 'gallery')]//img",
            "//*[contains(@id, 'product-gallery')]//img",
            "//*[contains(@id, 'product-images')]//img",
            // Carousel/Slider images
            "//*[contains(@class, 'carousel')]//img",
            "//*[contains(@class, 'slider')]//img",
            "//*[contains(@class, 'swiper')]//img",
            // Main product image
            "//*[contains(@class, 'product-image')]//img",
            "//*[contains(@class, 'main-image')]//img",
            // Thumbnail images
            "//*[contains(@class, 'thumbnail')]//img",
            "//*[contains(@class, 'thumb')]//img",
            // All images as fallback
            "//img",
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

                // Try multiple attributes for image source
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
                    $src = trim($node->getAttribute('data-image') ?? '');
                }

                // Check srcset attribute
                if (empty($src) || $src === 'data:image') {
                    $srcset = trim($node->getAttribute('srcset') ?? '');
                    if ($srcset !== '') {
                        $parts = preg_split('/\s*,\s*/', $srcset);
                        if (! empty($parts)) {
                            $first = trim(explode(' ', $parts[0])[0]);
                            $src = $first;
                        }
                    }
                }

                // Check data-srcset attribute
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

                // Extract from style attribute (background-image)
                if (empty($src) || $src === 'data:image') {
                    $style = (string) $node->getAttribute('style');
                    $bg = static::extractBackgroundImageUrl($style);
                    if ($bg !== null) {
                        $src = $bg;
                    }
                }

                // Skip if not a valid image URL
                if ($src === '' || str_starts_with($src, 'data:') || ! static::looksLikeImageUrl($src)) {
                    continue;
                }

                $absolute = static::toAbsoluteUrl($src, $baseUrl);
                
                // Filter out common non-product images (logos, icons, etc.)
                if (static::isProductImage($absolute)) {
                    if (! in_array($absolute, $urls, true)) {
                        $urls[] = $absolute;
                    }
                }
            }
        }

        // Also extract from source tags
        $sourceQueries = [
            "//*[contains(@class, 'product-gallery')]//source",
            "//*[contains(@class, 'gallery')]//source",
            "//source",
        ];

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
                            if (static::looksLikeImageUrl($absolute) && static::isProductImage($absolute) && ! in_array($absolute, $urls, true)) {
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
                    if (static::looksLikeImageUrl($absolute) && static::isProductImage($absolute) && ! in_array($absolute, $urls, true)) {
                        $urls[] = $absolute;
                    }
                }
            }
        }

        // Sort: prefer webp, then larger images
        usort($urls, function ($a, $b) {
            $aIsWebp = str_ends_with(strtolower($a), '.webp') || str_contains(strtolower($a), '.webp');
            $bIsWebp = str_ends_with(strtolower($b), '.webp') || str_contains(strtolower($b), '.webp');
            
            if ($aIsWebp && ! $bIsWebp) {
                return -1;
            }
            if (! $aIsWebp && $bIsWebp) {
                return 1;
            }
            
            return 0;
        });

        return array_values(array_unique($urls));
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

    /**
     * Check if URL looks like a product image (not a logo, icon, etc.)
     */
    protected static function isProductImage(string $url): bool
    {
        $lowerUrl = strtolower($url);
        
        // Exclude common non-product images
        $excludePatterns = [
            'logo',
            'icon',
            'avatar',
            'banner',
            'header',
            'footer',
            'favicon',
            'placeholder',
        ];

        foreach ($excludePatterns as $pattern) {
            if (str_contains($lowerUrl, $pattern)) {
                return false;
            }
        }

        return true;
    }
}






