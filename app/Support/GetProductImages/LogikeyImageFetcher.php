<?php

namespace App\Support\GetProductImages;

class LogikeyImageFetcher extends BaseImageFetcher
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
        
        // First priority: woocommerce-product-gallery (same as LogikeyFetcherCommand)
        $productGalleryQuery = "//*[contains(@class, 'woocommerce-product-gallery')]//img[@src or @data-src]";

        $urls = [];
        $webpUrls = [];
        $otherUrls = [];

        $nodes = $xpath->query($productGalleryQuery);
        if ($nodes) {
            foreach ($nodes as $node) {
                if (! ($node instanceof \DOMElement)) {
                    continue;
                }

                $src = trim($node->getAttribute('src') ?? '');

                if (empty($src) || $src === 'data:image') {
                    $src = trim($node->getAttribute('data-src') ?? '');
                }
                if (empty($src) || $src === 'data:image') {
                    $src = trim($node->getAttribute('data-large_image') ?? '');
                }
                if (empty($src) || $src === 'data:image') {
                    $srcset = trim($node->getAttribute('data-srcset') ?? '');
                    if ($srcset) {
                        // Extract first URL from srcset, prefer .webp if available
                        $srcsetUrls = preg_split('/\s*,\s*/', $srcset);
                        foreach ($srcsetUrls as $srcsetItem) {
                            $parts = preg_split('/\s+/', trim($srcsetItem), 2);
                            if (!empty($parts[0])) {
                                $candidate = $parts[0];
                                if (str_ends_with(strtolower($candidate), '.webp')) {
                                    $src = $candidate;
                                    break;
                                } elseif (empty($src) || $src === 'data:image') {
                                    $src = $candidate;
                                }
                            }
                        }
                    }
                }

                if ($src === '' || $src === 'data:image') {
                    continue;
                }

                $absolute = static::toAbsoluteUrl($src, $baseUrl);

                if (str_ends_with(strtolower($absolute), '.webp')) {
                    if (! in_array($absolute, $webpUrls, true)) {
                        $webpUrls[] = $absolute;
                    }
                } else {
                    $webpUrl = static::getWebpUrl($absolute);
                    if ($webpUrl && $webpUrl !== $absolute) {
                        if (! in_array($webpUrl, $webpUrls, true)) {
                            $webpUrls[] = $webpUrl;
                        }
                    } else {
                        if (! in_array($absolute, $otherUrls, true)) {
                            $otherUrls[] = $absolute;
                        }
                    }
                }
            }
        }

        return array_merge($webpUrls, $otherUrls);
    }

    protected static function getWebpUrl(string $url): ?string
    {
        if (str_ends_with(strtolower($url), '.webp')) {
            return $url;
        }

        $patterns = [
            '/\.(jpg|jpeg|png|gif)(\?.*)?$/i' => '.webp$2',
            '/\.(jpg|jpeg|png|gif)$/i' => '.webp',
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $url)) {
                return preg_replace($pattern, $replacement, $url);
            }
        }

        // Try appending .webp before query string
        if (strpos($url, '?') !== false) {
            $parts = explode('?', $url, 2);
            $base = $parts[0];
            $query = $parts[1];
            
            // Remove existing extension and add .webp
            $base = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '', $base);
            return $base . '.webp?' . $query;
        }

        return null;
    }
}
