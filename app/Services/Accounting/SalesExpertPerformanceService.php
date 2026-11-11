<?php

namespace App\Services\Accounting;

use App\Models\Sepidar\FMK\FiscalYear;
use App\Models\Sepidar\FMK\User as SepidarUser;
use App\Models\Sepidar\INV\InventoryReceiptItem;
use App\Models\Sepidar\SLS\Invoice;
use App\Models\Sepidar\SLS\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class SalesExpertPerformanceService
{
    public static function metricsCacheKey(int|string $fiscalYearRef, ?int $month, string $suffix): string
    {
        $monthKey = $month === null ? 'all' : (string) $month;

        return "accounting_sales_expert_metrics_{$fiscalYearRef}_{$monthKey}_{$suffix}";
    }

    public static function clearCache(int|string|null $fiscalYearRef = null, ?int $creatorId = null): void
    {
        $yearIds = $fiscalYearRef !== null
            ? [(int) $fiscalYearRef]
            : Cache::remember('sepidar_fiscal_year_ids', now()->addDay(), function () {
                return FiscalYear::query()->pluck('FiscalYearID')->all();
            });

        foreach ($yearIds as $yearId) {
            foreach ([null, ...range(1, 12)] as $month) {
                Cache::forget(self::metricsCacheKey($yearId, $month, 'by_creator'));
                Cache::forget(self::metricsCacheKey($yearId, $month, 'chart'));
                Cache::forget(self::metricsCacheKey($yearId, $month, 'profit'));
            }

            if ($creatorId !== null) {
                Cache::forget(self::metricsCacheKey($yearId, null, "monthly_{$creatorId}"));
            }
        }
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    public function periodBounds(int|string $fiscalYearRef, ?int $month): array
    {
        if ($month !== null) {
            return $this->monthBounds($fiscalYearRef, $month);
        }

        $fiscalYear = FiscalYear::query()->find($fiscalYearRef);

        if (! $fiscalYear) {
            return [null, null];
        }

        if ($fiscalYear->StartDate && $fiscalYear->EndDate) {
            return [
                Carbon::parse($fiscalYear->StartDate)->startOfDay(),
                Carbon::parse($fiscalYear->EndDate)->endOfDay(),
            ];
        }

        $jalaliYear = (int) $fiscalYear->Title;

        if ($jalaliYear < 1300) {
            return [null, null];
        }

        $start = (new Jalalian($jalaliYear, 1, 1))->toCarbon()->startOfDay();
        $days = (new Jalalian($jalaliYear, 12, 1))->getMonthDays();
        $end = (new Jalalian($jalaliYear, 12, $days))->toCarbon()->endOfDay();

        return [$start, $end];
    }

    /**
     * @param  list<int>  $creatorIds
     * @return array<int, array{
     *     customer_count: int,
     *     invoice_count: int,
     *     sold_quantity: float,
     *     new_customer_count: int,
     *     profit: float|null,
     *     proforma_count: null,
     *     lead_conversion_rate: null,
     *     buyer_type_frequency: null
     * }>
     */
    public function metricsForCreators(
        array $creatorIds,
        int|string $fiscalYearRef,
        ?int $month,
        bool $includeProfit = false
    ): array {
        $creatorIds = array_values(array_unique(array_map('intval', $creatorIds)));

        if ($creatorIds === []) {
            return [];
        }

        $allMetrics = $this->cachedCreatorMetrics($fiscalYearRef, $month);
        $result = [];

        foreach ($creatorIds as $creatorId) {
            $result[$creatorId] = $allMetrics[$creatorId] ?? $this->emptyMetrics();
        }

        if ($includeProfit) {
            $profits = $this->cachedCreatorProfits($fiscalYearRef, $month);

            foreach ($creatorIds as $creatorId) {
                $result[$creatorId]['profit'] = (float) ($profits[$creatorId] ?? 0);
            }
        }

        return $result;
    }

    /**
     * @return list<array{name: string, invoices: int}>
     */
    public function comparisonChart(int|string $fiscalYearRef, ?int $month, int $limit = 12): array
    {
        return Cache::remember(
            self::metricsCacheKey($fiscalYearRef, $month, 'chart'),
            now()->addMinutes(30),
            function () use ($fiscalYearRef, $month, $limit) {
                $metrics = $this->cachedCreatorMetrics($fiscalYearRef, $month);

                if ($metrics === []) {
                    return [];
                }

                uasort($metrics, static fn (array $a, array $b): int => $b['invoice_count'] <=> $a['invoice_count']);

                $topIds = array_slice(array_keys($metrics), 0, $limit);
                $names = SepidarUser::query()
                    ->whereIn('UserID', $topIds)
                    ->pluck('Name', 'UserID');

                $rows = [];

                foreach ($topIds as $creatorId) {
                    $name = trim((string) ($names[$creatorId] ?? '')) ?: ('#'.$creatorId);
                    $rows[] = [
                        'name' => mb_strlen($name) > 18 ? mb_substr($name, 0, 18).'…' : $name,
                        'invoices' => (int) ($metrics[$creatorId]['invoice_count'] ?? 0),
                    ];
                }

                return $rows;
            }
        );
    }

    /**
     * @return list<array{month: string, invoices: int, new_customers: int}>
     */
    public function monthlyTrend(int $creatorId, int|string $fiscalYearRef): array
    {
        return Cache::remember(
            self::metricsCacheKey($fiscalYearRef, null, "monthly_{$creatorId}"),
            now()->addMinutes(30),
            function () use ($creatorId, $fiscalYearRef) {
                [$start, $end] = $this->periodBounds($fiscalYearRef, null);

                if (! $start || ! $end) {
                    return $this->emptyMonthlyTrend();
                }

                $invoices = Invoice::query()
                    ->where('FiscalYearRef', $fiscalYearRef)
                    ->where('Creator', $creatorId)
                    ->where('State', '<>', 2)
                    ->whereBetween('Date', [$start, $end])
                    ->whereNotNull('Date')
                    ->get(['InvoiceId', 'Date', 'CustomerPartyRef']);

                $partyIds = $invoices->pluck('CustomerPartyRef')->filter()->unique()->values();
                $partyCreatedAt = [];

                if ($partyIds->isNotEmpty()) {
                    $partyCreatedAt = DB::connection('sqlsrv')
                        ->table('GNR.Party')
                        ->whereIn('PartyId', $partyIds->all())
                        ->pluck('CreationDate', 'PartyId')
                        ->all();
                }

                $invoiceByMonth = array_fill(1, 12, 0);
                $newCustomersByMonth = array_fill(1, 12, []);

                foreach ($invoices as $invoice) {
                    $month = Jalalian::fromDateTime($invoice->Date)->getMonth();
                    $invoiceByMonth[$month]++;

                    $partyId = $invoice->CustomerPartyRef;
                    $createdAt = $partyCreatedAt[$partyId] ?? null;

                    if ($partyId && $createdAt && $this->isNewCustomer($createdAt, $invoice->Date)) {
                        $newCustomersByMonth[$month][$partyId] = true;
                    }
                }

                $rows = [];

                for ($month = 1; $month <= 12; $month++) {
                    $rows[] = [
                        'month' => __('app.jalali_months.'.$month),
                        'invoices' => $invoiceByMonth[$month],
                        'new_customers' => count($newCustomersByMonth[$month]),
                    ];
                }

                return $rows;
            }
        );
    }

    /**
     * @return array<int, array{
     *     customer_count: int,
     *     invoice_count: int,
     *     sold_quantity: float,
     *     new_customer_count: int,
     *     profit: null,
     *     proforma_count: null,
     *     lead_conversion_rate: null,
     *     buyer_type_frequency: null
     * }>
     */
    private function cachedCreatorMetrics(int|string $fiscalYearRef, ?int $month): array
    {
        return Cache::remember(
            self::metricsCacheKey($fiscalYearRef, $month, 'by_creator'),
            now()->addMinutes(30),
            function () use ($fiscalYearRef, $month) {
                [$start, $end] = $this->periodBounds($fiscalYearRef, $month);

                if (! $start || ! $end) {
                    return [];
                }

                $base = Invoice::query()
                    ->where('FiscalYearRef', $fiscalYearRef)
                    ->where('State', '<>', 2)
                    ->whereBetween('Date', [$start, $end])
                    ->whereNotNull('Creator');

                $invoiceStats = (clone $base)
                    ->selectRaw('Creator, COUNT(*) as invoice_count, COUNT(DISTINCT CustomerPartyRef) as customer_count')
                    ->groupBy('Creator')
                    ->get()
                    ->keyBy('Creator');

                $soldQuantities = InvoiceItem::query()
                    ->selectRaw('SLS.Invoice.Creator as Creator, SUM(SLS.InvoiceItem.Quantity) as sold_quantity')
                    ->join('SLS.Invoice', 'SLS.InvoiceItem.InvoiceRef', '=', 'SLS.Invoice.InvoiceId')
                    ->where('SLS.Invoice.FiscalYearRef', $fiscalYearRef)
                    ->where('SLS.Invoice.State', '<>', 2)
                    ->whereBetween('SLS.Invoice.Date', [$start, $end])
                    ->whereNotNull('SLS.Invoice.Creator')
                    ->groupBy('SLS.Invoice.Creator')
                    ->pluck('sold_quantity', 'Creator');

                $newCustomerRows = DB::connection('sqlsrv')
                    ->table('SLS.Invoice as i')
                    ->join('GNR.Party as p', 'i.CustomerPartyRef', '=', 'p.PartyId')
                    ->where('i.FiscalYearRef', $fiscalYearRef)
                    ->where('i.State', '<>', 2)
                    ->whereBetween('i.Date', [$start, $end])
                    ->whereNotNull('i.Creator')
                    ->whereNotNull('i.CustomerPartyRef')
                    ->whereNotNull('p.CreationDate')
                    ->whereRaw('DATEDIFF(day, p.CreationDate, i.Date) BETWEEN 0 AND 29')
                    ->groupBy('i.Creator')
                    ->selectRaw('i.Creator as Creator, COUNT(DISTINCT i.CustomerPartyRef) as new_customer_count')
                    ->pluck('new_customer_count', 'Creator');

                $result = [];

                foreach ($invoiceStats as $creatorId => $row) {
                    $metrics = $this->emptyMetrics();
                    $metrics['invoice_count'] = (int) $row->invoice_count;
                    $metrics['customer_count'] = (int) $row->customer_count;
                    $metrics['sold_quantity'] = (float) ($soldQuantities[$creatorId] ?? 0);
                    $metrics['new_customer_count'] = (int) ($newCustomerRows[$creatorId] ?? 0);
                    $result[(int) $creatorId] = $metrics;
                }

                foreach ($soldQuantities as $creatorId => $qty) {
                    $creatorId = (int) $creatorId;
                    if (! isset($result[$creatorId])) {
                        $metrics = $this->emptyMetrics();
                        $metrics['sold_quantity'] = (float) $qty;
                        $result[$creatorId] = $metrics;
                    }
                }

                return $result;
            }
        );
    }

    /**
     * @return array<int, float>
     */
    private function cachedCreatorProfits(int|string $fiscalYearRef, ?int $month): array
    {
        return Cache::remember(
            self::metricsCacheKey($fiscalYearRef, $month, 'profit'),
            now()->addMinutes(30),
            function () use ($fiscalYearRef, $month) {
                [$start, $end] = $this->periodBounds($fiscalYearRef, $month);

                if (! $start || ! $end) {
                    return [];
                }

                $items = InvoiceItem::query()
                    ->select([
                        'SLS.InvoiceItem.ItemRef',
                        'SLS.InvoiceItem.Quantity',
                        'SLS.InvoiceItem.NetPrice',
                        'SLS.Invoice.Creator',
                    ])
                    ->join('SLS.Invoice', 'SLS.InvoiceItem.InvoiceRef', '=', 'SLS.Invoice.InvoiceId')
                    ->where('SLS.Invoice.FiscalYearRef', $fiscalYearRef)
                    ->where('SLS.Invoice.State', '<>', 2)
                    ->whereBetween('SLS.Invoice.Date', [$start, $end])
                    ->whereNotNull('SLS.Invoice.Creator')
                    ->where('SLS.InvoiceItem.Quantity', '>', 0)
                    ->get();

                if ($items->isEmpty()) {
                    return [];
                }

                $itemIds = $items->pluck('ItemRef')->unique()->filter()->values();

                $latestFees = InventoryReceiptItem::query()
                    ->whereIn('ItemRef', $itemIds)
                    ->whereIn('InventoryReceiptItemID', function ($query) {
                        $query->selectRaw('MAX(InventoryReceiptItemID)')
                            ->from('INV.InventoryReceiptItem')
                            ->groupBy('ItemRef');
                    })
                    ->pluck('Fee', 'ItemRef');

                $profits = [];

                foreach ($items as $item) {
                    $creatorId = (int) $item->Creator;
                    $fee = (float) ($latestFees[$item->ItemRef] ?? 0);
                    $lineProfit = (float) $item->NetPrice - ($fee * (float) $item->Quantity);
                    $profits[$creatorId] = ($profits[$creatorId] ?? 0) + $lineProfit;
                }

                return $profits;
            }
        );
    }

    /**
     * @return array{
     *     customer_count: int,
     *     invoice_count: int,
     *     sold_quantity: float,
     *     new_customer_count: int,
     *     profit: null,
     *     proforma_count: null,
     *     lead_conversion_rate: null,
     *     buyer_type_frequency: null
     * }
     */
    private function emptyMetrics(): array
    {
        return [
            'customer_count' => 0,
            'invoice_count' => 0,
            'sold_quantity' => 0.0,
            'new_customer_count' => 0,
            'profit' => null,
            'proforma_count' => null,
            'lead_conversion_rate' => null,
            'buyer_type_frequency' => null,
        ];
    }

    /**
     * @return list<array{month: string, invoices: int, new_customers: int}>
     */
    private function emptyMonthlyTrend(): array
    {
        $rows = [];

        for ($month = 1; $month <= 12; $month++) {
            $rows[] = [
                'month' => __('app.jalali_months.'.$month),
                'invoices' => 0,
                'new_customers' => 0,
            ];
        }

        return $rows;
    }

    private function isNewCustomer(mixed $partyCreatedAt, mixed $invoiceDate): bool
    {
        try {
            $created = Carbon::parse($partyCreatedAt)->startOfDay();
            $invoiced = Carbon::parse($invoiceDate)->startOfDay();
        } catch (\Throwable) {
            return false;
        }

        if ($created->gt($invoiced)) {
            return false;
        }

        return $created->diffInDays($invoiced) <= 29;
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function monthBounds(int|string $fiscalYearRef, int $month): array
    {
        $fiscalYear = FiscalYear::query()->find($fiscalYearRef);

        if (! $fiscalYear) {
            return [null, null];
        }

        $jalaliYear = (int) $fiscalYear->Title;
        $month = max(1, min(12, $month));

        if ($jalaliYear < 1300) {
            return [null, null];
        }

        $start = (new Jalalian($jalaliYear, $month, 1))->toCarbon()->startOfDay();
        $days = (new Jalalian($jalaliYear, $month, 1))->getMonthDays();
        $end = (new Jalalian($jalaliYear, $month, $days))->toCarbon()->endOfDay();

        if ($fiscalYear->StartDate) {
            $fyStart = Carbon::parse($fiscalYear->StartDate)->startOfDay();
            if ($start->lt($fyStart)) {
                $start = $fyStart;
            }
        }

        if ($fiscalYear->EndDate) {
            $fyEnd = Carbon::parse($fiscalYear->EndDate)->endOfDay();
            if ($end->gt($fyEnd)) {
                $end = $fyEnd;
            }
        }

        if ($start->gt($end)) {
            return [null, null];
        }

        return [$start, $end];
    }
}
