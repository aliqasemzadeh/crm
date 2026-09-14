<?php

namespace App\Services\Report;

use App\Models\SetareganCo\Order;
use App\Models\SetareganCo\OrderDetail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class SiteYearlySalesService
{
    public const START_JALALI_YEAR = 1400;

    public static function cacheKey(): string
    {
        return 'report_site_yearly_sales_from_'.self::START_JALALI_YEAR;
    }

    public static function topProductsCacheKey(int $jalaliYear): string
    {
        return 'report_site_top_products_by_qty_'.$jalaliYear;
    }

    public static function topProductsBySalesCacheKey(int $jalaliYear): string
    {
        return 'report_site_top_products_by_sales_'.$jalaliYear;
    }

    public static function clearCache(): void
    {
        Cache::forget(self::cacheKey());

        $currentYear = (int) Jalalian::now()->getYear();

        for ($year = self::START_JALALI_YEAR; $year <= $currentYear; $year++) {
            Cache::forget(self::topProductsCacheKey($year));
            Cache::forget(self::topProductsBySalesCacheKey($year));
        }
    }

    /**
     * @return array{
     *     chart: list<array{year: string, sales: float}>,
     *     total: float,
     *     yearCharts: array<int, array{
     *         year: string,
     *         total: float,
     *         months: list<array{month: string, sales: float}>
     *     }>
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
            $byYearMonth = [];

            for ($year = self::START_JALALI_YEAR; $year <= $currentYear; $year++) {
                $byYear[$year] = 0.0;
                $byYearMonth[$year] = array_fill(1, 12, 0.0);
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

                $jalali = Jalalian::fromDateTime($order->Date);
                $year = (int) $jalali->getYear();
                $month = (int) $jalali->getMonth();

                if ($year < self::START_JALALI_YEAR || $year > $currentYear) {
                    continue;
                }

                $amount = (float) $order->TotalAmount;
                $byYear[$year] += $amount;
                $byYearMonth[$year][$month] += $amount;
            }

            $chart = [];
            $yearCharts = [];
            $total = 0.0;

            foreach ($byYear as $year => $sales) {
                $chart[] = [
                    'year' => (string) $year,
                    'sales' => $sales,
                ];
                $total += $sales;

                $months = [];

                for ($month = 1; $month <= 12; $month++) {
                    $months[] = [
                        'month' => __('app.jalali_months.'.$month),
                        'sales' => $byYearMonth[$year][$month],
                    ];
                }

                $yearCharts[$year] = [
                    'year' => (string) $year,
                    'total' => $sales,
                    'months' => $months,
                ];
            }

            return [
                'chart' => $chart,
                'total' => $total,
                'yearCharts' => $yearCharts,
            ];
        });
    }

    /**
     * @return list<array{
     *     rank: int,
     *     product_id: int,
     *     name: string,
     *     average_price: float,
     *     quantity: float,
     *     sales: float
     * }>
     */
    public function topProducts(int $jalaliYear, int $limit = 10): array
    {
        return Cache::rememberForever(
            self::topProductsCacheKey($jalaliYear),
            fn () => $this->fetchTopProducts($jalaliYear, 'quantity', $limit)
        );
    }

    /**
     * @return list<array{
     *     rank: int,
     *     product_id: int,
     *     name: string,
     *     average_price: float,
     *     quantity: float,
     *     sales: float
     * }>
     */
    public function topProductsBySales(int $jalaliYear, int $limit = 10): array
    {
        return Cache::rememberForever(
            self::topProductsBySalesCacheKey($jalaliYear),
            fn () => $this->fetchTopProducts($jalaliYear, 'sales', $limit)
        );
    }

    /**
     * @param  'quantity'|'sales'  $orderBy
     * @return list<array{
     *     rank: int,
     *     product_id: int,
     *     name: string,
     *     average_price: float,
     *     quantity: float,
     *     sales: float
     * }>
     */
    private function fetchTopProducts(int $jalaliYear, string $orderBy, int $limit): array
    {
        $from = Jalalian::fromFormat('Y/m/d', $jalaliYear.'/01/01')
            ->toCarbon()
            ->startOfDay();

        $to = Jalalian::fromFormat('Y/m/d', ($jalaliYear + 1).'/01/01')
            ->toCarbon()
            ->startOfDay()
            ->subSecond();

        if ($jalaliYear === (int) Jalalian::now()->getYear()) {
            $to = now()->endOfDay();
        }

        $rows = OrderDetail::query()
            ->join('Order', 'OrderDetail.OrderId', '=', 'Order.Id')
            ->join('ProductPrice', 'OrderDetail.ProductPriceId', '=', 'ProductPrice.Id')
            ->join('Product', 'ProductPrice.ProductId', '=', 'Product.Id')
            ->where('Order.IsPayed', 1)
            ->whereBetween('Order.Date', [$from, $to])
            ->groupBy('Product.Id', 'Product.Name')
            ->select([
                'Product.Id as product_id',
                'Product.Name as name',
                DB::raw('SUM(OrderDetail.Count) as quantity'),
                DB::raw('SUM(OrderDetail.Price * OrderDetail.Count) as sales'),
            ])
            ->orderByDesc($orderBy)
            ->limit($limit)
            ->get();

        $products = [];

        foreach ($rows as $index => $row) {
            $quantity = (float) $row->quantity;
            $sales = (float) $row->sales;

            $products[] = [
                'rank' => $index + 1,
                'product_id' => (int) $row->product_id,
                'name' => (string) $row->name,
                'average_price' => $quantity > 0 ? $sales / $quantity : 0.0,
                'quantity' => $quantity,
                'sales' => $sales,
            ];
        }

        return $products;
    }
}
