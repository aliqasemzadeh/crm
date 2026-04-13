<?php

namespace App\Support\GetProductImages;

class MatinImageFetcher extends BaseImageFetcher
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
        
        // First priority: woocommerce-product-gallery (same as MatinFetcherCommand)
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

                if ($src === '' || $src === 'data:image') {
                    continue;
                }

                $absolute = static::toAbsoluteUrl($src, $baseUrl);

                if (str_ends_with(strtolower($absolute), '.webp')) {
                    if (! in_array($absolute, $webpUrls, true)) {
                        $webpUrls[] = $absolute;
                    }
                } else {
                    if (! in_array($absolute, $otherUrls, true)) {
                        $otherUrls[] = $absolute;
                    }
                }
            }
        }

        return array_merge($webpUrls, $otherUrls);
    }
}
