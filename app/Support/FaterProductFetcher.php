<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class FaterProductFetcher
{
    public static function fetchProductInfo(string $url, $logger = null): ?array
    {
        if ($logger) {
            $logger->info("Fetching product info from faterco.ir: {$url}");
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'fa-IR,fa;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer' => 'https://faterco.ir/',
            ])->timeout(15)->get($url);

            if (!$response->successful()) {
                if ($logger) {
                    $logger->warning("Request failed with status: {$response->status()}");
                }
                return null;
            }

            $html = $response->body();
            $productInfo = [];

            // Extract product name
            if (preg_match('/<h1[^>]*class=["\'][^"\']*product[^"\']*title[^"\']*["\'][^>]*>(.+?)<\/h1>/is', $html, $matches)) {
                $productInfo['name'] = trim(strip_tags($matches[1]));
            } elseif (preg_match('/<title[^>]*>(.+?)<\/title>/is', $html, $matches)) {
                $productInfo['name'] = trim(strip_tags($matches[1]));
            }

            // Extract description
            $description = [];
            if (preg_match('/<div[^>]*class=["\'][^"\']*product[^"\']*description[^"\']*["\'][^>]*>(.+?)<\/div>/is', $html, $matches)) {
                $description[] = trim(strip_tags($matches[1]));
            }
            if (preg_match('/<div[^>]*class=["\'][^"\']*description[^"\']*["\'][^>]*>(.+?)<\/div>/is', $html, $matches)) {
                $description[] = trim(strip_tags($matches[1]));
            }
            if (!empty($description)) {
                $productInfo['description'] = implode("\n\n", array_unique($description));
            }

            // Extract specifications
            $specifications = [];
            if (preg_match_all('/<tr[^>]*>.*?<td[^>]*>(.+?)<\/td>.*?<td[^>]*>(.+?)<\/td>.*?<\/tr>/is', $html, $specMatches, PREG_SET_ORDER)) {
                foreach ($specMatches as $match) {
                    $key = trim(strip_tags($match[1]));
                    $value = trim(strip_tags($match[2]));
                    if (!empty($key) && !empty($value)) {
                        $specifications[$key] = $value;
                    }
                }
            }

            // Extract from specification lists
            if (preg_match_all('/<li[^>]*>.*?<strong[^>]*>(.+?)<\/strong>.*?<span[^>]*>(.+?)<\/span>.*?<\/li>/is', $html, $listMatches, PREG_SET_ORDER)) {
                foreach ($listMatches as $match) {
                    $key = trim(strip_tags($match[1]));
                    $value = trim(strip_tags($match[2]));
                    if (!empty($key) && !empty($value)) {
                        $specifications[$key] = $value;
                    }
                }
            }

            // Extract from div-based specifications
            if (preg_match_all('/<div[^>]*class=["\'][^"\']*spec[^"\']*["\'][^>]*>.*?<span[^>]*class=["\'][^"\']*label[^"\']*["\'][^>]*>(.+?)<\/span>.*?<span[^>]*class=["\'][^"\']*value[^"\']*["\'][^>]*>(.+?)<\/span>.*?<\/div>/is', $html, $divMatches, PREG_SET_ORDER)) {
                foreach ($divMatches as $match) {
                    $key = trim(strip_tags($match[1]));
                    $value = trim(strip_tags($match[2]));
                    if (!empty($key) && !empty($value)) {
                        $specifications[$key] = $value;
                    }
                }
            }

            if (!empty($specifications)) {
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
                        $images[] = 'https://faterco.ir' . $imgUrl;
                    }
                }
            }
            // Try data-src attributes
            if (preg_match_all('/<img[^>]*data-src=["\']([^"\']+)["\']/i', $html, $imgMatches)) {
                foreach ($imgMatches[1] as $imgUrl) {
                    if (str_starts_with($imgUrl, 'http')) {
                        $images[] = $imgUrl;
                    } elseif (str_starts_with($imgUrl, '/')) {
                        $images[] = 'https://faterco.ir' . $imgUrl;
                    }
                }
            }
            // Try regular src attributes in product sections
            if (preg_match_all('/<img[^>]*src=["\']([^"\']*product[^"\']*\.(?:jpg|jpeg|png|webp))["\']/i', $html, $imgMatches)) {
                foreach ($imgMatches[1] as $imgUrl) {
                    if (str_starts_with($imgUrl, 'http')) {
                        $images[] = $imgUrl;
                    } elseif (str_starts_with($imgUrl, '/')) {
                        $images[] = 'https://faterco.ir' . $imgUrl;
                    }
                }
            }

            $productInfo['images'] = array_unique($images);

            // Extract weight (try to find weight in specifications or text)
            if (isset($specifications)) {
                foreach ($specifications as $key => $value) {
                    if (stripos($key, 'وزن') !== false || stripos($key, 'weight') !== false) {
                        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:گرم|g|kg|کیلوگرم)/i', $value, $weightMatch)) {
                            $weight = (float) $weightMatch[1];
                            if (stripos($value, 'kg') !== false || stripos($value, 'کیلوگرم') !== false) {
                                $weight *= 1000; // Convert to grams
                            }
                            $productInfo['weight'] = $weight;
                            break;
                        }
                    }
                }
            }

            // Extract dimensions (try to find dimensions in specifications)
            if (isset($specifications)) {
                foreach ($specifications as $key => $value) {
                    if (stripos($key, 'ابعاد') !== false || stripos($key, 'dimension') !== false || stripos($key, 'سایز') !== false) {
                        // Try to extract dimensions like "100x200x300 mm" or "100 × 200 × 300"
                        if (preg_match('/(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)\s*(?:mm|میلی‌متر|سانتیمتر|cm)/i', $value, $dimMatch)) {
                            $productInfo['x_dimension'] = (float) $dimMatch[1];
                            $productInfo['y_dimension'] = (float) $dimMatch[2];
                            $productInfo['z_dimension'] = (float) $dimMatch[3];
                            
                            // Convert cm to mm if needed
                            if (stripos($value, 'cm') !== false || stripos($value, 'سانتیمتر') !== false) {
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

            // Format specifications into description if not already set
            if (empty($productInfo['description']) && !empty($specifications)) {
                $specText = "**مشخصات فنی:**\n\n";
                foreach ($specifications as $key => $value) {
                    $specText .= "**{$key}:** {$value}\n";
                }
                $productInfo['description'] = $specText;
            } elseif (!empty($productInfo['description']) && !empty($specifications)) {
                // Append specifications to existing description
                $specText = "\n\n**مشخصات فنی:**\n\n";
                foreach ($specifications as $key => $value) {
                    $specText .= "**{$key}:** {$value}\n";
                }
                $productInfo['description'] .= $specText;
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
