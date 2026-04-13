<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class TechnolifePriceFetcher
{
    public static function fetchPrice(string $url, $logger = null): ?int
    {
        if ($logger) {
            $logger->info("Fetching price from technolife.ir: {$url}");
        }

        // Try HTML scraping method
        if ($logger) {
            $logger->info('Trying HTML scraping method...');
        }
        $price = self::tryHtmlScraping($url, $logger);
        if ($price) {
            if ($logger) {
                $logger->info("Price found via HTML scraping: {$price} Toman");
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
                'Referer' => 'https://www.technolife.ir/',
            ])->timeout(15)->get($url);

            if ($response->successful()) {
                $html = $response->body();

                // Method 1: Look for price in JSON-LD structure
                if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.+?)<\/script>/s', $html, $scriptMatches)) {
                    foreach ($scriptMatches[1] as $scriptContent) {
                        $jsonData = json_decode($scriptContent, true);
                        if ($jsonData && is_array($jsonData)) {
                            // Check if it's a Product or a list of items
                            $items = isset($jsonData['@type']) && $jsonData['@type'] === 'Product' ? [$jsonData] : (isset($jsonData['@graph']) ? $jsonData['@graph'] : []);

                            foreach ($items as $item) {
                                if (isset($item['@type']) && $item['@type'] === 'Product') {
                                    $price = self::getNestedValue($item, 'offers.price') ?? self::getNestedValue($item, 'offers.lowPrice');
                                    if ($price && is_numeric($price)) {
                                        return (int) $price;
                                    }
                                }
                            }
                        }
                    }
                }

                // Method 2: Extract price from the HTML structure provided in the issue
                // The issue description shows: <p class="text-[19px] font-semiBold !leading-5 xl:text-[22px] text-primary-shade-1">17,800,000</p>
                if (preg_match('/text-\[19px\][^>]*>([\d,]+)/u', $html, $matches)) {
                    $priceText = $matches[1];
                    $price = self::parsePrice($priceText);
                    if ($price) {
                        return $price;
                    }
                }

                // Alternative Method 2.1: Look for any price-like pattern in a paragraph
                if (preg_match('/<p[^>]*>([\d,]{4,})<\/p>/u', $html, $matches)) {
                    $price = self::parsePrice($matches[1]);
                    if ($price) {
                        return $price;
                    }
                }

                // Method 3: Broad search for price with comma separators
                if (preg_match_all('/([\d,]{4,})\s*(?:تومان|Toman)/u', $html, $matches)) {
                    foreach ($matches[1] as $match) {
                        $price = self::parsePrice($match);
                        if ($price && $price >= 1000) {
                            return $price;
                        }
                    }
                }
            } else {
                if ($logger) {
                    $logger->warning("HTML request failed with status: {$response->status()}");
                }
            }
        } catch (\Exception $e) {
            if ($logger) {
                $logger->warning("HTML scraping exception: {$e->getMessage()}");
            }
        }

        return null;
    }

    private static function parsePrice(string $priceText): ?int
    {
        // Remove commas and spaces
        $normalized = preg_replace('/[,\s]/', '', $priceText);

        // Convert Persian digits if any (though Technolife usually uses English)
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $normalized = str_replace($persianDigits, $englishDigits, $normalized);

        if (is_numeric($normalized)) {
            return (int) $normalized;
        }

        return null;
    }

    private static function convertTomanToRial(int $priceInToman): int
    {
        return $priceInToman * 10;
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
