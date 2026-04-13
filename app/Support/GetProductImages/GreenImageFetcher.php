<?php

namespace App\Support\GetProductImages;

class GreenImageFetcher extends BaseImageFetcher
{
    public static function fetchImageUrls(string $url): array
    {
        $html = static::fetchHtml($url);
        if (! $html) {
            return [];
        }

        $allUrls = static::extractImageUrls($html, $url);
        return static::filterGalleryImages($allUrls);
    }

    protected static function extractImageUrls(string $html, string $baseUrl): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        
        // XPath queries (same as GreenFetcherCommand)
        $queries = [
            "//*[contains(@class, 'single-product-carousel')]//img",
            "//*[contains(@class, 'single-product-carousel')]//*[contains(@class, 'owl-item')]//img",
            "//*[contains(@class, 'owl-carousel')]//img",
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

                $src = trim($node->getAttribute('src') ?? '');
                if ($src === '' || $src === 'data:image' || str_starts_with($src, 'data:')) {
                    $src = trim($node->getAttribute('data-src') ?? '');
                }
                if ($src === '' || $src === 'data:image' || str_starts_with($src, 'data:')) {
                    $src = trim($node->getAttribute('data-lazy-src') ?? '');
                }
                if ($src === '' || $src === 'data:image' || str_starts_with($src, 'data:')) {
                    $src = trim($node->getAttribute('data-original') ?? '');
                }
                if ($src === '' || $src === 'data:image' || str_starts_with($src, 'data:')) {
                    $srcset = trim($node->getAttribute('data-srcset') ?? '');
                    if ($srcset !== '') {
                        $parts = preg_split('/\s*,\s*/', $srcset);
                        if (! empty($parts)) {
                            $first = trim(explode(' ', $parts[0])[0]);
                            $src = $first;
                        }
                    }
                }
                if ($src === '' || $src === 'data:image' || str_starts_with($src, 'data:')) {
                    $style = (string) $node->getAttribute('style');
                    $bg = static::extractBackgroundImageUrl($style);
                    if ($bg !== null) {
                        $src = $bg;
                    }
                }

                if ($src === '' || str_starts_with($src, 'data:')) {
                    continue;
                }

                if (! static::looksLikeImageUrl($src)) {
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

    protected static function filterGalleryImages(array $urls): array
    {
        // Filter to only URLs with 375x375 size (gallery images) - same as GreenFetcherCommand
        $filtered = [];
        foreach ($urls as $url) {
            if (
                str_contains($url, 'Gallery/') &&
                preg_match('#_375_375\.jpg(\?|$)#i', $url)
            ) {
                $filtered[] = $url;
            }
        }

        return array_values(array_unique($filtered));
    }
}
