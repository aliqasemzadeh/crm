<?php

namespace App\Services\Report;

use App\Models\Sepidar\SLS\Invoice;
use App\Models\Sepidar\SLS\InvoiceItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class ItemYearlySalesService
{
    public const START_JALALI_YEAR = 1400;

    public static function cacheKey(): string
    {
        return 'report_item_yearly_sales_from_'.self::START_JALALI_YEAR;
    }

    public static function topProductsCacheKey(int $jalaliYear): string
    {
        return 'report_item_top_products_by_qty_'.$jalaliYear;
    }

    public static function topProductsBySalesCacheKey(int $jalaliYear): string
    {
        return 'report_item_top_products_by_sales_'.$jalaliYear;
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

            $invoices = Invoice::query()
                ->where('Date', '>=', $from)
                ->select(['Date', 'Price'])
                ->get();

            foreach ($invoices as $invoice) {
                if (! $invoice->Date) {
                    continue;
                }

                $jalali = Jalalian::fromDateTime($invoice->Date);
                $year = (int) $jalali->getYear();
                $month = (int) $jalali->getMonth();

                if ($year < self::START_JALALI_YEAR || $year > $currentYear) {
                    continue;
                }

                $amount = (float) $invoice->Price;
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

        $rows = InvoiceItem::query()
            ->join('SLS.Invoice', 'SLS.InvoiceItem.InvoiceRef', '=', 'SLS.Invoice.InvoiceId')
            ->join('INV.Item', 'SLS.InvoiceItem.ItemRef', '=', 'INV.Item.ItemID')
            ->whereBetween('SLS.Invoice.Date', [$from, $to])
            ->groupBy('INV.Item.ItemID', 'INV.Item.Title')
            ->select([
                'INV.Item.ItemID as product_id',
                'INV.Item.Title as name',
                DB::raw('SUM(SLS.InvoiceItem.Quantity) as quantity'),
                DB::raw('SUM(SLS.InvoiceItem.Price) as sales'),
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
