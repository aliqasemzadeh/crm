<?php

namespace App\Services\Report;

use App\Models\SetareganCo\Order;
use Illuminate\Support\Facades\Cache;
use Morilog\Jalali\Jalalian;

class SiteYearlySalesService
{
    public const START_JALALI_YEAR = 1400;

    public static function cacheKey(): string
    {
        return 'report_site_yearly_sales_from_'.self::START_JALALI_YEAR;
    }

    public static function clearCache(): void
    {
        Cache::forget(self::cacheKey());
    }

    /**
     * @return array{
     *     chart: list<array{year: string, sales: float}>,
     *     total: float
     * }
     */
    public function yearlySales(): array
    {
        return Cache::rememberForever(self::cacheKey(), function () {
            $from = Jalalian::fromFormat('Y/m/d', self::START_JALALI_YEAR.'/01/01')
                ->toCarbon()
                ->startOfDay();

            $currentYear = (int) Jalalian::now()->getYear();
            $byYear = [];

            for ($year = self::START_JALALI_YEAR; $year <= $currentYear; $year++) {
                $byYear[$year] = 0.0;
            }

            $orders = Order::query()
                ->where('IsPayed', 1)
                ->where('Date', '>=', $from)
                ->select(['Date', 'TotalAmount'])
                ->get();

            foreach ($orders as $order) {
                if (! $order->Date) {
                    continue;
                }

                $year = (int) Jalalian::fromDateTime($order->Date)->getYear();

                if ($year < self::START_JALALI_YEAR || $year > $currentYear) {
                    continue;
                }

                $byYear[$year] += (float) $order->TotalAmount;
            }

            $chart = [];
            $total = 0.0;

            foreach ($byYear as $year => $sales) {
                $chart[] = [
                    'year' => (string) $year,
                    'sales' => $sales,
                ];
                $total += $sales;
            }

            return [
                'chart' => $chart,
                'total' => $total,
            ];
        });
    }
}
