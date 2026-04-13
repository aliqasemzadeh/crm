<?php

namespace App\Support\GetProductImages;

class XVisionImageFetcher extends BaseImageFetcher
{
    public static function fetchImageUrls(string $url): array
    {
        $html = static::fetchHtml($url);
        if (! $html) {
            return [];
        }

        $allUrls = static::extractImageUrls($html, $url);
        return static::filterProductImages($allUrls);
    }

    protected static function extractImageUrls(string $html, string $baseUrl): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        
        // XPath queries for Owl Carousel containers (same as XVisionFetcherCommand)
        $queries = [
            "//*[contains(@class, 'owl-stage-outer')]//img",
            "//*[contains(@class, 'owl-stage')]//img",
            "//*[contains(@class, 'owl-item')]//img",
            "//*[contains(@class, 'owl-carousel')]//img",
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
                    $srcset = trim($node->getAttribute('srcset') ?? '');
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

        // Also extract from source tags in picture elements (same as XVisionFetcherCommand)
        $sourceQueries = [
            "//*[contains(@class, 'owl-stage-outer')]//source",
            "//*[contains(@class, 'owl-stage')]//source",
            "//*[contains(@class, 'owl-item')]//source",
            "//*[contains(@class, 'owl-carousel')]//source",
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

    protected static function filterProductImages(array $urls): array
    {
        $productUrls = [];
        $excludePatterns = ['#logo#i', '#icon#i', '#banner#i', '#header#i', '#footer#i'];

        foreach ($urls as $url) {
            $shouldExclude = false;
            foreach ($excludePatterns as $pattern) {
                if (preg_match($pattern, $url)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                continue;
            }

            // Include URLs from uploads directory (product images)
            if (preg_match('#/uploads/#i', $url) && static::looksLikeImageUrl($url)) {
                $productUrls[] = $url;
            }
        }

        return $productUrls;
    }
}
