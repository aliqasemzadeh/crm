<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class SnappShopPriceFetcher
{
    public static function fetchPrice(string $url, $logger = null): ?int
    {
        if ($logger) {
            $logger->info("Fetching price from snappshop.ir: {$url}");
        }

        try {
            // Extract product ID from URL
            // Example: https://snappshop.ir/product/snp-1431117297
            if (preg_match('/snp-(\d+)/', $url, $matches)) {
                $productId = $matches[1];
            } elseif (preg_match('/product\/(\d+)/', $url, $matches)) {
                $productId = $matches[1];
            } else {
                if ($logger) {
                    $logger->warning("Could not extract product ID from URL: {$url}");
                }
                return null;
            }

            // API URL
            $apiUrl = "https://apix.snappshop.ir/products/v2/{$productId}?lat=35.77331&lng=51.418591";

            $response = Http::withoutVerifying()->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json',
            ])->timeout(15)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();

                // Extract price from variants
                if (isset($data['data']['variants'][0]['vendor'][0]['price'])) {
                    $price = (int) $data['data']['variants'][0]['vendor'][0]['price'];

                    if ($logger) {
                        $logger->info("Price found via API: {$price}");
                    }

                    return $price;
                }

                if ($logger) {
                    $logger->warning('Could not find price in API response');
                }
            } else {
                if ($logger) {
                    $logger->warning("API request failed with status: {$response->status()}");
                }
            }
        } catch (\Exception $e) {
            if ($logger) {
                $logger->warning("SnappShop fetcher exception: {$e->getMessage()}");
            }
        }

        return null;
    }
}
