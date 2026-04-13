<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class FaterPriceFetcher
{
    public static function fetchPrice(string $url, $logger = null): ?int
    {
        if ($logger) {
            $logger->info("Fetching price from faterco.ir: {$url}");
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
                'Referer' => 'https://faterco.ir/',
            ])->timeout(15)->get($url);

            if ($response->successful()) {
                $html = $response->body();
                $candidates = [];

                // Method 1: Look for price in meta tags (highest priority)
                if (preg_match('/<meta[^>]*name=["\']product_price["\'][^>]*content=["\'](\d+)["\']/', $html, $matches)) {
                    $price = (int) $matches[1];
                    if ($price >= 1000000 && $price <= 50000000) {
                        $candidates[] = ['price' => $price, 'priority' => 30, 'source' => 'meta-product_price'];
                    }
                }

                // Method 2: Look for price in JSON-LD structured data
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
                                    if ($price >= 1000000 && $price <= 50000000) {
                                        $candidates[] = ['price' => $price, 'priority' => 10, 'source' => 'json-ld'];
                                    }
                                }
                            }
                        }
                    }
                }

                // Method 3: Look for price with تومان currency (high priority)
                if (preg_match_all('/(\d{1,3}(?:[،,]\d{3})*)\s*تومان/u', $html, $matches)) {
                    foreach ($matches[1] as $match) {
                        $cleanPrice = str_replace([',', '،'], '', $match);
                        if (is_numeric($cleanPrice)) {
                            $price = (int) $cleanPrice;
                            if ($price >= 1000000 && $price <= 50000000) {
                                $candidates[] = ['price' => $price, 'priority' => 20, 'source' => 'toman-currency'];
                            }
                        }
                    }
                }

                // Method 4: Look for price in data attributes (high priority)
                if (preg_match_all('/data-price=["\'](\d+)["\']/', $html, $matches)) {
                    foreach ($matches[1] as $match) {
                        $price = (int) $match;
                        if ($price >= 1000000 && $price <= 50000000) {
                            $candidates[] = ['price' => $price, 'priority' => 15, 'source' => 'data-attribute'];
                        }
                    }
                }

                // Method 5: Look for price in elements with price-related classes
                if (preg_match_all('/<(?:span|div|p)[^>]*class=["\'][^"\']*price[^"\']*["\'][^>]*>.*?(\d{1,3}(?:[،,]\d{3})*)/s', $html, $matches)) {
                    foreach ($matches[1] as $match) {
                        $cleanPrice = str_replace([',', '،'], '', $match);
                        if (is_numeric($cleanPrice)) {
                            $price = (int) $cleanPrice;
                            if ($price >= 1000000 && $price <= 50000000) {
                                $candidates[] = ['price' => $price, 'priority' => 12, 'source' => 'price-class'];
                            }
                        }
                    }
                }

                // Method 6: Look for price in JSON data embedded in script tags
                if (preg_match_all('/"price"\s*:\s*["\']?(\d+)["\']?/', $html, $matches)) {
                    foreach ($matches[1] as $match) {
                        $price = (int) $match;
                        if ($price >= 1000000 && $price <= 50000000) {
                            $candidates[] = ['price' => $price, 'priority' => 8, 'source' => 'json-price'];
                        }
                    }
                }

                if (preg_match_all('/"finalPrice"\s*:\s*["\']?(\d+)["\']?/', $html, $matches)) {
                    foreach ($matches[1] as $match) {
                        $price = (int) $match;
                        if ($price >= 1000000 && $price <= 50000000) {
                            $candidates[] = ['price' => $price, 'priority' => 9, 'source' => 'json-finalPrice'];
                        }
                    }
                }

                // Method 7: Look for price in specific faterco.ir structure
                if (preg_match_all('/<div[^>]*class=["\'][^"\']*product[^"\']*price[^"\']*["\'][^>]*>(.+?)<\/div>/s', $html, $matches)) {
                    foreach ($matches[1] as $priceSection) {
                        if (preg_match('/(\d{1,3}(?:[،,]\d{3})*)/', $priceSection, $priceMatch)) {
                            $cleanPrice = str_replace([',', '،'], '', $priceMatch[1]);
                            if (is_numeric($cleanPrice)) {
                                $price = (int) $cleanPrice;
                                if ($price >= 1000000 && $price <= 50000000) {
                                    $candidates[] = ['price' => $price, 'priority' => 14, 'source' => 'product-price-div'];
                                }
                            }
                        }
                    }
                }

                // Remove duplicates and sort by priority
                $uniqueCandidates = [];
                foreach ($candidates as $candidate) {
                    $price = $candidate['price'];
                    if (! isset($uniqueCandidates[$price]) || $uniqueCandidates[$price]['priority'] < $candidate['priority']) {
                        $uniqueCandidates[$price] = $candidate;
                    }
                }

                if (! empty($uniqueCandidates)) {
                    // Sort by priority (descending) and return the highest priority price
                    usort($uniqueCandidates, function ($a, $b) {
                        if ($a['priority'] == $b['priority']) {
                            return $a['price'] <=> $b['price'];
                        }

                        return $b['priority'] <=> $a['priority'];
                    });

                    $bestCandidate = $uniqueCandidates[0];
                    if ($logger) {
                        $logger->info('Found price candidates: '.count($uniqueCandidates));
                        $logger->info("Selected price: {$bestCandidate['price']} from source: {$bestCandidate['source']}");
                    }

                    return $bestCandidate['price'];
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
                $logger->warning("HTML scraping exception: {$e->getMessage()}");
            }
        }

        return null;
    }

    /**
     * Get nested value from array using dot notation
     */
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
