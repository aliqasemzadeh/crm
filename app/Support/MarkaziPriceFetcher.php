<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class MarkaziPriceFetcher
{
    public static function fetchPrice(string $url, $logger = null): ?int
    {
        if ($logger) {
            $logger->info("Fetching price from: {$url}");
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
                'Referer' => 'https://www.markazi.co/',
            ])->timeout(15)->get($url);

            if ($response->successful()) {
                $html = $response->body();

                // Method 1: Look for price in various common patterns
                $patterns = [
                    // Look for price in data attributes
                    '/data-price=["\'](\d+)["\']/i',
                    '/data-price-value=["\'](\d+)["\']/i',
                    '/price["\']?\s*[:=]\s*["\']?(\d{4,})/i',
                    '/"price"\s*:\s*(\d+)/i',
                    '/"final_price"\s*:\s*(\d+)/i',
                    '/"finalPrice"\s*:\s*(\d+)/i',
                    '/"selling_price"\s*:\s*(\d+)/i',
                    '/"sellingPrice"\s*:\s*(\d+)/i',
                ];

                foreach ($patterns as $pattern) {
                    if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
                        foreach ($matches as $match) {
                            $price = (int) $match[1];
                            // Validate price is reasonable (between 1000 and 100000000)
                            if ($price >= 1000 && $price <= 100000000) {
                                if ($logger) {
                                    $logger->info("Found price via pattern {$pattern}: {$price}");
                                }

                                return $price;
                            }
                        }
                    }
                }

                // Method 2: Extract prices from HTML elements
                $prices = self::extractPricesFromHtml($html, $logger);
                if (! empty($prices)) {
                    // Return the minimum price (lowest available price)
                    $minPrice = min($prices);
                    if ($logger) {
                        $logger->info('Found '.count($prices)." prices, returning minimum: {$minPrice}");
                    }

                    return $minPrice;
                }

                // Method 3: Look for JSON data in script tags
                if (preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $html, $scriptMatches)) {
                    foreach ($scriptMatches[1] as $scriptContent) {
                        // Try to find JSON objects with price data
                        if (preg_match('/\{[^}]*"price"[^}]*\}/i', $scriptContent, $jsonMatch)) {
                            $jsonData = json_decode($jsonMatch[0], true);
                            if ($jsonData && isset($jsonData['price']) && is_numeric($jsonData['price'])) {
                                $price = (int) $jsonData['price'];
                                if ($price >= 1000 && $price <= 100000000) {
                                    if ($logger) {
                                        $logger->info("Found price in JSON: {$price}");
                                    }

                                    return $price;
                                }
                            }
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

    /**
     * Extract prices from HTML content
     */
    private static function extractPricesFromHtml(string $html, $logger = null): array
    {
        $prices = [];

        // Common price container patterns for Persian e-commerce sites
        $priceSelectors = [
            // Price in class names containing "price"
            '/<[^>]*class=["\'][^"\']*price[^"\']*["\'][^>]*>([^<]+)<\/[^>]+>/i',
            // Price in span/div with price-related classes
            '/<span[^>]*class=["\'][^"\']*price[^"\']*["\'][^>]*>([^<]+)<\/span>/i',
            '/<div[^>]*class=["\'][^"\']*price[^"\']*["\'][^>]*>([^<]+)<\/div>/i',
            // Price in elements with data attributes
            '/<[^>]*data-price[^>]*>([^<]+)<\/[^>]+>/i',
            // Price in strong/b tags (common for highlighted prices)
            '/<strong[^>]*>([^<]*\d+[^<]*)<\/strong>/i',
            '/<b[^>]*>([^<]*\d+[^<]*)<\/b>/i',
        ];

        foreach ($priceSelectors as $pattern) {
            if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $priceText = trim($match[1]);
                    $price = self::parsePersianPrice($priceText);
                    if ($price && $price >= 1000 && $price <= 100000000 && ! in_array($price, $prices)) {
                        $prices[] = $price;
                        if ($logger) {
                            $logger->info("Found price from HTML: {$priceText} -> {$price}");
                        }
                    }
                }
            }
        }

        // Also look for prices in meta tags
        if (preg_match('/<meta[^>]*property=["\']product:price:amount["\'][^>]*content=["\'](\d+)["\']/i', $html, $metaMatch)) {
            $price = (int) $metaMatch[1];
            if ($price >= 1000 && $price <= 100000000 && ! in_array($price, $prices)) {
                $prices[] = $price;
                if ($logger) {
                    $logger->info("Found price in meta tag: {$price}");
                }
            }
        }

        return array_unique($prices);
    }

    /**
     * Parse price text that may contain Persian digits and convert to integer
     */
    private static function parsePersianPrice(string $priceText): ?int
    {
        // Convert Persian digits to English digits
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $normalized = str_replace($persianDigits, $englishDigits, $priceText);

        // Remove common currency symbols and text
        $normalized = preg_replace('/[تومان|ریال|Toman|Rial|\$|€|£]/ui', '', $normalized);

        // Remove commas, spaces, and other non-digit characters except digits
        $normalized = preg_replace('/[,\s]/', '', $normalized);

        // Extract only digits
        if (preg_match('/(\d+)/', $normalized, $matches)) {
            $price = (int) $matches[1];

            return $price;
        }

        return null;
    }
}
