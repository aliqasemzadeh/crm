<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class WooPriceFetcher
{
    public static function fetchPrice(string $url, $logger = null): ?int
    {
        if ($logger) {
            $logger->info("Fetching price from WooCommerce (plazadigital): {$url}");
        }

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'fa-IR,fa;q=0.9,en-US;q=0.8,en;q=0.7',
            ])->timeout(15)->get($url);

            if ($response->successful()) {
                $html = $response->body();

                // Method 1: Extract from <meta name="twitter:data1" content="2,268,000&nbsp;تومان - 3,250,000&nbsp;تومان" />
                if (preg_match('/<meta[^>]*name=["\']twitter:data1["\'][^>]*content=["\']([^"\']+)["\']/', $html, $matches)) {
                    $content = $matches[1];
                    if ($logger) {
                        $logger->info("Found twitter:data1 content: {$content}");
                    }

                    // Handle ranges (take the first price which is usually the lower one)
                    $parts = explode('-', $content);
                    $priceText = trim($parts[0]);

                    $price = self::parsePrice($priceText);
                    if ($price) {
                        return $price;
                    }
                }

                // Method 2: Extract from <meta property="product:price:amount" content="..." /> (common in WooCommerce)
                if (preg_match('/<meta[^>]*property=["\']product:price:amount["\'][^>]*content=["\']([^"\']+)["\']/', $html, $matches)) {
                    $price = (int) $matches[1];
                    if ($price > 0) {
                        return $price;
                    }
                }

                if ($logger) {
                    $logger->warning('Could not find price in HTML content');
                }
            } else {
                if ($logger) {
                    $logger->warning("HTML request failed with status: {$response->status()}");
                }
            }
        } catch (\Exception $e) {
            if ($logger) {
                $logger->warning("WooPriceFetcher exception: {$e->getMessage()}");
            }
        }

        return null;
    }

    private static function parsePrice(string $priceText): ?int
    {
        // Decode HTML entities (like &nbsp;)
        $priceText = html_entity_decode($priceText);

        // Convert Persian digits to English
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $normalized = str_replace($persianDigits, $englishDigits, $priceText);

        // Remove commas and non-numeric characters (except the price itself)
        $normalized = preg_replace('/[^\d]/', '', $normalized);

        if (is_numeric($normalized) && !empty($normalized)) {
            return (int) $normalized;
        }

        return null;
    }
}
