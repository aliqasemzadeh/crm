<?php

namespace App\Support\GetProductImages;

class LogitechImageFetcher extends BaseImageFetcher
{
    public static function fetchImageUrls(string $url): array
    {
        $html = static::fetchHtml($url);
        if (! $html) {
            return [];
        }

        $urls = array_merge(
            static::extractFromJson($html, $url),
            static::extractImageUrls($html, $url)
        );

        $productImages = static::filterProductImages($urls);
        $cleanImages = static::cleanLogitechUrls($productImages);

        return array_values(array_unique($cleanImages));
    }

    protected static function extractFromJson(string $html, string $baseUrl): array
    {
        $urls = [];

        // Look for JSON-LD or inline JSON with image data
        if (preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $matches)) {
            foreach ($matches[1] as $jsonContent) {
                $data = json_decode($jsonContent, true);
                if (is_array($data)) {
                    static::collectImagesFromJson($data, $urls);
                }
            }
        }

        // Look for image URLs in script tags
        if (preg_match_all('#<script[^>]*>(.*?)</script>#is', $html, $scriptMatches)) {
            foreach ($scriptMatches[1] as $scriptContent) {
                if (preg_match_all('#["\'](https?://[^"\']*logitech[^"\']*\.(?:jpe?g|png|webp|gif)[^"\']*)["\']#i', $scriptContent, $imgMatches)) {
                    foreach ($imgMatches[1] as $imgUrl) {
                        if (! in_array($imgUrl, $urls, true)) {
                            $urls[] = $imgUrl;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($urls));
    }

    protected static function collectImagesFromJson(mixed $data, array &$urls): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($key) && (str_contains(strtolower($key), 'image') || str_contains(strtolower($key), 'picture'))) {
                    if (is_string($value) && static::looksLikeImageUrl($value)) {
                        if (! in_array($value, $urls, true)) {
                            $urls[] = $value;
                        }
                    } elseif (is_array($value)) {
                        foreach ($value as $item) {
                            if (is_string($item) && static::looksLikeImageUrl($item)) {
                                if (! in_array($item, $urls, true)) {
                                    $urls[] = $item;
                                }
                            }
                        }
                    }
                } else {
                    static::collectImagesFromJson($value, $urls);
                }
            }
        }
    }

    protected static function extractImageUrls(string $html, string $baseUrl): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $queries = [
            "//*[contains(@class, 'product-gallery')]//img",
            "//*[contains(@class, 'product-images')]//img",
            "//*[contains(@class, 'gallery')]//img",
            "//*[contains(@class, 'carousel')]//img",
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
                if (empty($src) || $src === 'data:image') {
                    $src = trim($node->getAttribute('data-src') ?? '');
                }
                if (empty($src) || $src === 'data:image') {
                    $src = trim($node->getAttribute('data-lazy-src') ?? '');
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

        // Also try regex extraction
        $regexUrls = static::extractLogitechUrlsWithRegex($html);
        $urls = array_merge($urls, $regexUrls);

        return array_values(array_unique($urls));
    }

    protected static function extractLogitechUrlsWithRegex(string $html): array
    {
        $urls = [];
        $patterns = [
            '#https?://[^"\'\s<>]+logitech[^"\'\s<>]+\.(?:jpe?g|png|webp|gif)(?:\?[^\s<>"\']*)?#i',
            '#["\'](https?://[^"\']*logitech[^"\']*\.(?:jpe?g|png|webp|gif)[^"\']*)["\']#i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] ?? $matches[0] as $url) {
                    $trimmed = trim($url);
                    $trimmed = rtrim($trimmed, '",\'})];');
                    if (str_contains($trimmed, '?')) {
                        $trimmed = strtok($trimmed, '?');
                    }
                    if ($trimmed !== '' && static::looksLikeImageUrl($trimmed) && ! in_array($trimmed, $urls, true)) {
                        $urls[] = $trimmed;
                    }
                }
            }
        }

        return $urls;
    }

    protected static function cleanLogitechUrls(array $urls): array
    {
        $cleaned = [];
        foreach ($urls as $url) {
            if (preg_match('#/d_[^/]+/content/dam/(.+)$#i', $url, $matches)) {
                $baseUrl = preg_replace('#/d_[^/]+/content/dam/.+$#i', '', $url);
                $cleanUrl = $baseUrl . '/content/dam/' . $matches[1];
            } else {
                $cleanUrl = $url;
            }

            if (strpos($cleanUrl, '?') !== false) {
                $cleanUrl = strtok($cleanUrl, '?');
            }

            if (! in_array($cleanUrl, $cleaned, true)) {
                $cleaned[] = $cleanUrl;
            }
        }

        return array_values($cleaned);
    }

    protected static function filterProductImages(array $urls): array
    {
        $productUrls = [];
        $excludePatterns = ['#logo#i', '#icon#i', '#banner#i', '#header#i', '#footer#i', '#\.svg#i'];

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

            $includePatterns = ['#product#i', '#images?/product#i', '#gallery#i'];
            $shouldInclude = false;
            foreach ($includePatterns as $pattern) {
                if (preg_match($pattern, $url)) {
                    $shouldInclude = true;
                    break;
                }
            }

            if ($shouldInclude || (str_contains(strtolower($url), 'logitech.com') && preg_match('#\.(jpe?g|png|webp)$#i', $url))) {
                $productUrls[] = $url;
            }
        }

        return $productUrls;
    }
}







