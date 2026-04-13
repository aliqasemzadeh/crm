<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class GigabyteProductFetcher
{
    public static function fetchProductInfo(string $url, $logger = null): ?array
    {
        if ($logger) {
            $logger->info("Fetching product info from gigabyte.com: {$url}");
        }

        try {
            $proxy = collect(config('proxy.proxies'))->random();

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Referer' => 'https://www.gigabyte.com/',
            ])->withOptions([
                'proxy' => $proxy,
            ])->timeout(15)->get($url);

            if (! $response->successful()) {
                if ($logger) {
                    $logger->warning("Request failed with status: {$response->status()}");
                }

                return null;
            }

            $html = $response->body();
            $productInfo = [];

            // Extract product name from title or h1
            if (preg_match('/<h1[^>]*class=["\'][^"\']*product[^"\']*title[^"\']*["\'][^>]*>(.+?)<\/h1>/is', $html, $matches)) {
                $productInfo['name'] = trim(strip_tags($matches[1]));
            } elseif (preg_match('/<title[^>]*>(.+?)\s*[-|]\s*GIGABYTE/i', $html, $matches)) {
                $productInfo['name'] = trim(strip_tags($matches[1]));
            } elseif (preg_match('/<title[^>]*>(.+?)<\/title>/is', $html, $matches)) {
                $productInfo['name'] = trim(strip_tags($matches[1]));
            }

            // Extract description from meta description or product description section
            if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $matches)) {
                $productInfo['description'] = trim($matches[1]);
            } elseif (preg_match('/<div[^>]*class=["\'][^"\']*product[^"\']*description[^"\']*["\'][^>]*>(.+?)<\/div>/is', $html, $matches)) {
                $productInfo['description'] = trim(strip_tags($matches[1]));
            }

            // Extract specifications from specification tables
            $specifications = [];

            // Try to find specification tables
            if (preg_match('/<table[^>]*class=["\'][^"\']*spec[^"\']*["\'][^>]*>(.+?)<\/table>/is', $html, $tableMatch)) {
                $tableContent = $tableMatch[1];
                if (preg_match_all('/<tr[^>]*>.*?<td[^>]*class=["\'][^"\']*label[^"\']*["\'][^>]*>(.+?)<\/td>.*?<td[^>]*class=["\'][^"\']*value[^"\']*["\'][^>]*>(.+?)<\/td>.*?<\/tr>/is', $tableContent, $specMatches, PREG_SET_ORDER)) {
                    foreach ($specMatches as $match) {
                        $key = trim(strip_tags($match[1]));
                        $value = trim(strip_tags($match[2]));
                        if (! empty($key) && ! empty($value)) {
                            $specifications[$key] = $value;
                        }
                    }
                }
            }

            // Try alternative specification format
            if (preg_match_all('/<div[^>]*class=["\'][^"\']*spec[^"\']*item[^"\']*["\'][^>]*>.*?<span[^>]*class=["\'][^"\']*label[^"\']*["\'][^>]*>(.+?)<\/span>.*?<span[^>]*class=["\'][^"\']*value[^"\']*["\'][^>]*>(.+?)<\/span>.*?<\/div>/is', $html, $divMatches, PREG_SET_ORDER)) {
                foreach ($divMatches as $match) {
                    $key = trim(strip_tags($match[1]));
                    $value = trim(strip_tags($match[2]));
                    if (! empty($key) && ! empty($value)) {
                        $specifications[$key] = $value;
                    }
                }
            }

            // Try to extract from JSON-LD structured data
            if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.+?)<\/script>/s', $html, $scriptMatches)) {
                foreach ($scriptMatches[1] as $scriptContent) {
                    $jsonData = json_decode($scriptContent, true);
                    if ($jsonData && is_array($jsonData)) {
                        // Extract from Product schema
                        if (isset($jsonData['@type']) && $jsonData['@type'] === 'Product') {
                            if (isset($jsonData['name']) && empty($productInfo['name'])) {
                                $productInfo['name'] = $jsonData['name'];
                            }
                            if (isset($jsonData['description']) && empty($productInfo['description'])) {
                                $productInfo['description'] = $jsonData['description'];
                            }
                            if (isset($jsonData['weight'])) {
                                $weight = $jsonData['weight'];
                                if (is_string($weight) && preg_match('/(\d+(?:\.\d+)?)\s*(?:kg|g)/i', $weight, $weightMatch)) {
                                    $productInfo['weight'] = (float) $weightMatch[1];
                                    if (stripos($weight, 'kg') !== false) {
                                        $productInfo['weight'] *= 1000; // Convert to grams
                                    }
                                } elseif (is_numeric($weight)) {
                                    $productInfo['weight'] = (float) $weight;
                                }
                            }
                        }
                    }
                }
            }

            if (! empty($specifications)) {
                $productInfo['specifications'] = $specifications;
            }

            // Extract images
            $images = [];
            // Try to find product gallery images
            if (preg_match_all('/<img[^>]*class=["\'][^"\']*product[^"\']*image[^"\']*["\'][^>]*src=["\']([^"\']+)["\']/i', $html, $imgMatches)) {
                foreach ($imgMatches[1] as $imgUrl) {
                    if (str_starts_with($imgUrl, 'http')) {
                        $images[] = $imgUrl;
                    } elseif (str_starts_with($imgUrl, '/')) {
                        $images[] = 'https://www.gigabyte.com'.$imgUrl;
                    }
                }
            }
            // Try data-src attributes
            if (preg_match_all('/<img[^>]*data-src=["\']([^"\']+)["\']/i', $html, $imgMatches)) {
                foreach ($imgMatches[1] as $imgUrl) {
                    if (str_starts_with($imgUrl, 'http')) {
                        $images[] = $imgUrl;
                    } elseif (str_starts_with($imgUrl, '/')) {
                        $images[] = 'https://www.gigabyte.com'.$imgUrl;
                    }
                }
            }
            // Try to find images in product gallery
            if (preg_match_all('/<img[^>]*src=["\']([^"\']*product[^"\']*\.(?:jpg|jpeg|png|webp))["\']/i', $html, $imgMatches)) {
                foreach ($imgMatches[1] as $imgUrl) {
                    if (str_starts_with($imgUrl, 'http')) {
                        $images[] = $imgUrl;
                    } elseif (str_starts_with($imgUrl, '/')) {
                        $images[] = 'https://www.gigabyte.com'.$imgUrl;
                    }
                }
            }

            $productInfo['images'] = array_unique($images);

            // Extract weight from specifications
            if (isset($specifications)) {
                foreach ($specifications as $key => $value) {
                    if (stripos($key, 'weight') !== false || stripos($key, 'وزن') !== false) {
                        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:g|kg|grams?|kilograms?)/i', $value, $weightMatch)) {
                            $weight = (float) $weightMatch[1];
                            if (stripos($value, 'kg') !== false || stripos($value, 'kilogram') !== false) {
                                $weight *= 1000; // Convert to grams
                            }
                            $productInfo['weight'] = $weight;
                            break;
                        }
                    }
                }
            }

            // Extract dimensions from specifications
            if (isset($specifications)) {
                foreach ($specifications as $key => $value) {
                    if (stripos($key, 'dimension') !== false || stripos($key, 'size') !== false || stripos($key, 'ابعاد') !== false) {
                        // Try to extract dimensions like "238 x 141 x 40 mm" or "238x141x40mm"
                        if (preg_match('/(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)\s*(?:mm|cm)/i', $value, $dimMatch)) {
                            $productInfo['x_dimension'] = (float) $dimMatch[1];
                            $productInfo['y_dimension'] = (float) $dimMatch[2];
                            $productInfo['z_dimension'] = (float) $dimMatch[3];

                            // Convert cm to mm if needed
                            if (stripos($value, 'cm') !== false) {
                                $productInfo['x_dimension'] *= 10;
                                $productInfo['y_dimension'] *= 10;
                                $productInfo['z_dimension'] *= 10;
                            }
                            break;
                        }
                    }
                }
            }

            // Generate slug from name
            if (isset($productInfo['name'])) {
                $productInfo['slug'] = \Illuminate\Support\Str::slug($productInfo['name']);
                $productInfo['slug_fa'] = str_replace(' ', '-', $productInfo['name']);
            }

            // Format specifications into description
            if (! empty($specifications)) {
                $specText = "**Technical Specifications:**\n\n";
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
                $logger->error("Exception while fetching product info: {$e->getMessage()}");
            }

            return null;
        }
    }
}
