<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class NovaProductFetcher
{
    public static function fetchProductInfo(string $url, $logger = null): ?array
    {
        if ($logger) {
            $logger->info("Fetching product info from nova-tech.ir: {$url}");
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'fa-IR,fa;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer' => 'https://nova-tech.ir/',
            ])->timeout(20)->get($url);

            if (! $response->successful()) {
                if ($logger) {
                    $logger->warning("Request failed with status: {$response->status()}");
                }

                return null;
            }

            $html = $response->body();
            $productInfo = [];

            $parsedUrl = parse_url($url);
            $scheme = $parsedUrl['scheme'] ?? 'https';
            $host = $parsedUrl['host'] ?? 'nova-tech.ir';
            $baseUrl = $scheme.'://'.$host;

            // Extract product name
            if (preg_match('/<h1[^>]*class=["\'][^"\']*(?:product|title|entry-title)[^"\']*["\'][^>]*>(.+?)<\/h1>/is', $html, $matches)) {
                $productInfo['name'] = trim(strip_tags($matches[1]));
            } elseif (preg_match('/<h1[^>]*>(.+?)<\/h1>/is', $html, $matches)) {
                $productInfo['name'] = trim(strip_tags($matches[1]));
            } elseif (preg_match('/<title[^>]*>(.+?)<\/title>/is', $html, $matches)) {
                $productInfo['name'] = trim(strip_tags($matches[1]));
            }

            // Extract description (meta description or product description container)
            if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $matches)) {
                $productInfo['description'] = trim($matches[1]);
            } elseif (preg_match('/<div[^>]*class=["\'][^"\']*(?:product-description|woocommerce-product-details__short-description|tab-pane|description|entry-content)[^"\']*["\'][^>]*>(.+?)<\/div>/is', $html, $matches)) {
                $productInfo['description'] = trim(strip_tags($matches[1]));
            }

            // Extract specifications (tables and lists)
            $specifications = [];

            // Look for specification tables
            if (preg_match_all('/<tr[^>]*>.*?<td[^>]*>(.+?)<\/td>.*?<td[^>]*>(.+?)<\/td>.*?<\/tr>/is', $html, $specMatches, PREG_SET_ORDER)) {
                foreach ($specMatches as $match) {
                    $key = trim(strip_tags($match[1]));
                    $value = trim(strip_tags($match[2]));
                    if ($key !== '' && $value !== '' && !str_contains($key, 'تصویر') && !str_contains($key, 'image')) {
                        $specifications[$key] = $value;
                    }
                }
            }

            // Look for specification lists
            if (preg_match_all('/<li[^>]*>\s*(?:<span[^>]*class=["\'][^"\']*label[^"\']*["\'][^>]*>)?(.+?)<\/span>.*?<span[^>]*class=["\'][^"\']*(?:value|detail)[^"\']*["\'][^>]*>(.+?)<\/span>.*?<\/li>/is', $html, $listMatches, PREG_SET_ORDER)) {
                foreach ($listMatches as $match) {
                    $key = trim(strip_tags($match[1]));
                    $value = trim(strip_tags($match[2]));
                    if ($key !== '' && $value !== '') {
                        $specifications[$key] = $value;
                    }
                }
            }

            if (! empty($specifications)) {
                $productInfo['specifications'] = $specifications;
            }

            // Extract images using NovaImageFetcher
            $images = [];
            try {
                $imageFetcher = \App\Support\GetProductImages\BaseImageFetcher::getFetcher($url);
                if ($imageFetcher) {
                    $images = $imageFetcher::fetchImageUrls($url);
                }
            } catch (\Exception $e) {
                if ($logger) {
                    $logger->warning("Failed to fetch images using NovaImageFetcher: {$e->getMessage()}");
                }
            }

            // Fallback: extract images manually if fetcher failed
            if (empty($images)) {
                // 1) og:image
                if (preg_match_all('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $ogMatches)) {
                    foreach ($ogMatches[1] as $imgUrl) {
                        $images[] = self::normalizeImageUrl($imgUrl, $baseUrl);
                    }
                }

                // 2) WooCommerce gallery images
                if (preg_match_all('/<div[^>]*class=["\'][^"\']*(?:woocommerce-product-gallery|product-gallery|product-images)[^"\']*["\'][^>]*>(.+?)<\/div>/is', $html, $galleryMatches)) {
                    foreach ($galleryMatches[1] as $galleryHtml) {
                        if (preg_match_all('/<img[^>]*(?:src|data-src|data-lazy-src|data-large_image)=["\']([^"\']+)["\'][^>]*>/i', $galleryHtml, $imgMatches)) {
                            foreach ($imgMatches[1] as $imgUrl) {
                                $images[] = self::normalizeImageUrl($imgUrl, $baseUrl);
                            }
                        }
                    }
                }

                // 3) Fallback: all product-related images
                if (empty($images) && preg_match_all('/<img[^>]*(?:src|data-src)=["\']([^"\']+\.(?:jpg|jpeg|png|webp))["\'][^>]*>/i', $html, $allImgMatches)) {
                    foreach ($allImgMatches[1] as $imgUrl) {
                        $images[] = self::normalizeImageUrl($imgUrl, $baseUrl);
                    }
                }
            }

            $images = array_values(array_unique(array_filter($images)));
            $productInfo['images'] = $images;

            // Extract weight from specs
            if (! empty($specifications)) {
                foreach ($specifications as $key => $value) {
                    if (str_contains($key, 'وزن') || str_contains(strtolower($key), 'weight')) {
                        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:گرم|g|kg|کیلوگرم)/iu', $value, $weightMatch)) {
                            $weight = (float) $weightMatch[1];
                            if (stripos($value, 'kg') !== false || str_contains($value, 'کیلوگرم')) {
                                $weight *= 1000;
                            }
                            $productInfo['weight'] = $weight;
                            break;
                        }
                    }
                }
            }

            // Extract dimensions
            if (! empty($specifications)) {
                foreach ($specifications as $key => $value) {
                    if (str_contains($key, 'ابعاد') || str_contains(strtolower($key), 'dimension') || str_contains($key, 'سایز')) {
                        if (preg_match('/(\d+(?:\.\d+)?)\s*[x×*]\s*(\d+(?:\.\d+)?)\s*[x×*]\s*(\d+(?:\.\d+)?)\s*(?:mm|میلی|سانتی|cm)/iu', $value, $dimMatch)) {
                            $x = (float) $dimMatch[1];
                            $y = (float) $dimMatch[2];
                            $z = (float) $dimMatch[3];

                            if (stripos($value, 'cm') !== false || str_contains($value, 'سانتی')) {
                                $x *= 10;
                                $y *= 10;
                                $z *= 10;
                            }

                            $productInfo['x_dimension'] = $x;
                            $productInfo['y_dimension'] = $y;
                            $productInfo['z_dimension'] = $z;
                            break;
                        }
                    }
                }
            }

            // Slugs
            if (isset($productInfo['name'])) {
                $productInfo['slug'] = \Illuminate\Support\Str::slug($productInfo['name']);
                $productInfo['slug_fa'] = str_replace(' ', '-', $productInfo['name']);
            }

            // Append specs to description if needed
            if (! empty($specifications)) {
                $specText = "**مشخصات فنی:**\n\n";
                foreach ($specifications as $key => $value) {
                    $specText .= "**{$key}:** {$value}\n";
                }

                if (empty($productInfo['description'])) {
                    $productInfo['description'] = $specText;
                } else {
                    $productInfo['description'] .= "\n\n".$specText;
                }
            }

            return $productInfo;
        } catch (\Exception $e) {
            if ($logger) {
                $logger->error("Exception while fetching product info from nova-tech.ir: {$e->getMessage()}");
            }

            return null;
        }
    }

    protected static function normalizeImageUrl(string $url, string $baseUrl): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return rtrim($baseUrl, '/').$url;
        }

        return rtrim($baseUrl, '/').'/'.$url;
    }
}





