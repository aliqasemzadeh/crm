<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class SetareganPriceFetcher
{
    public static function fetchPrice(string $url, $logger = null): ?int
    {
        if ($logger) {
            $logger->info("Fetching price from setaregan.co: {$url}");
        }

        // Try HTML scraping method
        if ($logger) {
            $logger->info('Trying HTML scraping method...');
        }

        $price = self::tryHtmlScraping($url, $logger);

        if ($price) {
            if ($logger) {
                $logger->info("Price found via HTML scraping: {$price}");
            }

            return $price;
        }

        if ($logger) {
            $logger->warning('All methods failed to fetch price');
        }

        return null;
    }

    private static function tryHtmlScraping(string $url, $logger = null): ?int
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'fa-IR,fa;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer' => 'https://setaregan.co/',
            ])->timeout(20)->get($url);

            if (! $response->successful()) {
                if ($logger) {
                    $logger->warning("HTML request failed with status: {$response->status()}");
                }

                return null;
            }

            $html = $response->body();
            $candidates = [];

            // 1) meta product_price (if they use something similar)
            if (preg_match('/<meta[^>]*name=["\']product_price["\'][^>]*content=["\'](\d+)["\']/i', $html, $matches)) {
                $price = (int) $matches[1];
                if (self::isValidPrice($price)) {
                    $candidates[] = ['price' => $price, 'priority' => 30, 'source' => 'meta-product_price'];
                }
            }

            // 2) JSON-LD offers.price, etc.
            if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.+?)<\/script>/s', $html, $scriptMatches)) {
                foreach ($scriptMatches[1] as $scriptContent) {
                    $jsonData = json_decode($scriptContent, true);
                    if ($jsonData && is_array($jsonData)) {
                        $paths = [
                            'offers.price',
                            'offers.0.price',
                            'price',
                            'aggregateOffer.lowPrice',
                            'aggregateOffer.highPrice',
                        ];

                        foreach ($paths as $path) {
                            $value = self::getNestedValue($jsonData, $path);
                            if ($value && is_numeric($value)) {
                                $price = (int) $value;
                                if (self::isValidPrice($price)) {
                                    $candidates[] = ['price' => $price, 'priority' => 15, 'source' => 'json-ld'];
                                }
                            }
                        }
                    }
                }
            }

            // 3) تومان patterns
            if (preg_match_all('/(\d{1,3}(?:[٬،,]\d{3})*)\s*تومان/u', $html, $matches)) {
                foreach ($matches[1] as $match) {
                    $clean = str_replace([',', '،', '٬'], '', $match);
                    if (is_numeric($clean)) {
                        $price = (int) $clean;
                        if (self::isValidPrice($price)) {
                            $candidates[] = ['price' => $price, 'priority' => 25, 'source' => 'toman-text'];
                        }
                    }
                }
            }

            // 4) data-price attributes
            if (preg_match_all('/data-price=["\'](\d+)["\']/i', $html, $matches)) {
                foreach ($matches[1] as $match) {
                    $price = (int) $match;
                    if (self::isValidPrice($price)) {
                        $candidates[] = ['price' => $price, 'priority' => 20, 'source' => 'data-price'];
                    }
                }
            }

            // 5) elements with price-related classes
            if (preg_match_all('/<(?:span|div|p)[^>]*class=["\'][^"\']*price[^"\']*["\'][^>]*>(.+?)<\/(?:span|div|p)>/is', $html, $matches)) {
                foreach ($matches[1] as $priceSection) {
                    $priceText = strip_tags($priceSection);
                    $price = self::parsePersianPrice($priceText);
                    if ($price && self::isValidPrice($price)) {
                        $candidates[] = ['price' => $price, 'priority' => 18, 'source' => 'price-class'];
                    }
                }
            }

            // 6) generic "price" fields inside JSON in scripts
            if (preg_match_all('/"price"\s*:\s*["\']?(\d+)["\']?/i', $html, $matches)) {
                foreach ($matches[1] as $match) {
                    $price = (int) $match;
                    if (self::isValidPrice($price)) {
                        $candidates[] = ['price' => $price, 'priority' => 10, 'source' => 'json-price'];
                    }
                }
            }

            if (empty($candidates)) {
                if ($logger) {
                    $logger->warning('Could not find price in HTML content');
                }

                return null;
            }

            // Deduplicate by price, keep highest priority
            $unique = [];
            foreach ($candidates as $candidate) {
                $price = $candidate['price'];
                if (! isset($unique[$price]) || $unique[$price]['priority'] < $candidate['priority']) {
                    $unique[$price] = $candidate;
                }
            }

            $unique = array_values($unique);

            usort($unique, function ($a, $b) {
                if ($a['priority'] === $b['priority']) {
                    return $a['price'] <=> $b['price'];
                }

                return $b['priority'] <=> $a['priority'];
            });

            $best = $unique[0];

            if ($logger) {
                $logger->info("Selected price {$best['price']} from source {$best['source']}");
            }

            return $best['price'];
        } catch (\Exception $e) {
            if ($logger) {
                $logger->warning("HTML scraping exception: {$e->getMessage()}");
            }

            return null;
        }
    }

    private static function parsePersianPrice(string $priceText): ?int
    {
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $normalized = str_replace($persianDigits, $englishDigits, $priceText);

        // Remove currency words and symbols
        $normalized = preg_replace('/[تومان|ریال|Toman|Rial|\$|€|£]/ui', '', $normalized);

        // Remove commas and spaces
        $normalized = preg_replace('/[,\s٬،]/u', '', $normalized);

        if (preg_match('/(\d+)/', $normalized, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private static function isValidPrice(int $price): bool
    {
        return $price >= 1000 && $price <= 100000000;
    }

    private static function getNestedValue(array $array, string $path): mixed
    {
        $keys = explode('.', $path);
        $value = $array;

        foreach ($keys as $key) {
            if (! isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }
}
