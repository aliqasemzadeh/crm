<?php

namespace App\Services\Warehouse;

use App\Models\Sepidar\FMK\FiscalYear;
use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\INV\InventoryReceiptItem;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Models\SetareganCo\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class WarehouseDashboardService
{
    public const EXCLUDED_CODING_GROUP = 584;

    public const STALE_MONTHS = 6;

    /** @var list<int> */
    public const ORGANIZATIONAL_GROUPING_IDS = [51, 52, 53, 612, 54];

    /** @var list<int> */
    public const COLLEAGUE_GROUPING_IDS = [58, 63];

    public static function cacheKey(
        int|string $fiscalYearRef,
        ?int $month,
        ?int $stockRef,
        ?int $groupingId,
        string $suffix
    ): string {
        $monthKey = $month === null ? 'all' : (string) $month;
        $stockKey = $stockRef === null ? 'all' : (string) $stockRef;
        $groupKey = $groupingId === null ? 'all' : (string) $groupingId;

        return "warehouse_dashboard_{$fiscalYearRef}_{$monthKey}_{$stockKey}_{$groupKey}_{$suffix}";
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
     * @return list<int>
     */
    public function groupingIds(?int $groupingId): array
    {
        if (! $groupingId) {
            return [];
        }

        $grouping = Grouping::query()->find($groupingId);

        if (! $grouping) {
            return [$groupingId];
        }

        return array_map('intval', $grouping->getAllChildrenIds());
    }

    /**
     * @return list<array{id: int, title: string}>
     */
    public function stocks(): array
    {
        return Cache::remember('warehouse_dashboard_stocks', now()->addHour(), function () {
            $stockIds = ItemStockSummary::query()
                ->whereNotNull('StockRef')
                ->distinct()
                ->orderBy('StockRef')
                ->pluck('StockRef')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();

            if ($stockIds === []) {
                return [];
            }

            $placeholders = implode(',', array_fill(0, count($stockIds), '?'));
            $rows = DB::connection('sqlsrv')->select(
                "SELECT StockID, Title FROM INV.Stock WHERE StockID IN ({$placeholders})",
                $stockIds
            );

            $titles = collect($rows)->mapWithKeys(fn ($row) => [(int) $row->StockID => (string) $row->Title]);

            return collect($stockIds)->map(fn (int $id) => [
                'id' => $id,
                'title' => $titles[$id] ?? ('#'.$id),
            ])->all();
        });
    }

    /**
     * @return array{
     *     inventory_value: float,
     *     overview: array{total: int, without_image: int, without_irancode: int},
     *     top_by_quantity: list<array{id: int, code: string, title: string, quantity: float, sales: float, profit: float}>,
     *     top_by_profit: list<array{id: int, code: string, title: string, quantity: float, sales: float, profit: float}>,
     *     stagnant_items: list<array{id: int, code: string, title: string, quantity: float, last_sale_at: string|null}>,
     *     top_groups_by_sales: list<array{id: int, title: string, quantity: float, sales: float, profit: float}>,
     *     top_groups_by_profit: list<array{id: int, title: string, quantity: float, sales: float, profit: float}>,
     *     top_brands_by_sales: list<array{id: int|string, title: string, quantity: float, sales: float, profit: float}>|null,
     *     top_brands_by_profit: list<array{id: int|string, title: string, quantity: float, sales: float, profit: float}>|null,
     *     top_organizational: list<array{id: int, code: string, title: string, quantity: float, sales: float, profit: float}>,
     *     top_colleague: list<array{id: int, code: string, title: string, quantity: float, sales: float, profit: float}>,
     *     top_users: list<array{id: int, code: string, title: string, quantity: float, sales: float, profit: float}>,
     *     item_type_available: false
     * }
     */
    public function metrics(
        int|string $fiscalYearRef,
        ?int $month = null,
        ?int $stockRef = null,
        ?int $groupingId = null,
        int $limit = 10
    ): array {
        return Cache::remember(
            self::cacheKey($fiscalYearRef, $month, $stockRef, $groupingId, "metrics_{$limit}"),
            now()->addMinutes(30),
            function () use ($fiscalYearRef, $month, $stockRef, $groupingId, $limit) {
                $groupingIds = $this->groupingIds($groupingId);
                $itemSales = $this->itemSalesRows($fiscalYearRef, $month, $groupingIds);

                $byQty = $itemSales;
                usort($byQty, static fn (array $a, array $b): int => $b['quantity'] <=> $a['quantity']);

                $byProfit = $itemSales;
                usort($byProfit, static fn (array $a, array $b): int => $b['profit'] <=> $a['profit']);

                $groups = $this->aggregateGroups($itemSales);
                $groupsBySales = $groups;
                usort($groupsBySales, static fn (array $a, array $b): int => $b['sales'] <=> $a['sales']);
                $groupsByProfit = $groups;
                usort($groupsByProfit, static fn (array $a, array $b): int => $b['profit'] <=> $a['profit']);

                $brands = $this->aggregateBrands($itemSales);

                return [
                    'inventory_value' => $this->inventoryValue($fiscalYearRef, $stockRef, $groupingIds),
                    'overview' => $this->overview($fiscalYearRef, $stockRef, $groupingIds),
                    'top_by_quantity' => array_slice($byQty, 0, $limit),
                    'top_by_profit' => array_slice($byProfit, 0, $limit),
                    'stagnant_items' => $this->stagnantItems($fiscalYearRef, $stockRef, $groupingIds, $limit),
                    'top_groups_by_sales' => array_slice($groupsBySales, 0, $limit),
                    'top_groups_by_profit' => array_slice($groupsByProfit, 0, $limit),
                    'top_brands_by_sales' => $brands['by_sales'],
                    'top_brands_by_profit' => $brands['by_profit'],
                    'top_organizational' => $this->topItemsByPartyGroupings(
                        $fiscalYearRef,
                        $month,
                        $groupingIds,
                        self::ORGANIZATIONAL_GROUPING_IDS,
                        $limit
                    ),
                    'top_colleague' => $this->topItemsByPartyGroupings(
                        $fiscalYearRef,
                        $month,
                        $groupingIds,
                        self::COLLEAGUE_GROUPING_IDS,
                        $limit
                    ),
                    'top_users' => $this->topItemsByUsersSegment($fiscalYearRef, $month, $groupingIds, $limit),
                    'item_type_available' => false,
                ];
            }
        );
    }

    /**
     * @param  list<int>  $groupingIds
     * @return array{total: int, without_image: int, without_irancode: int}
     */
    private function overview(int|string $fiscalYearRef, ?int $stockRef, array $groupingIds): array
    {
        $base = Item::query()
            ->where('CodingGroupRef', '!=', self::EXCLUDED_CODING_GROUP)
            ->when($groupingIds !== [], fn ($q) => $q->whereIn('CodingGroupRef', $groupingIds));

        if ($stockRef) {
            $itemIds = ItemStockSummary::query()
                ->where('FiscalYearRef', $fiscalYearRef)
                ->where('StockRef', $stockRef)
                ->where('Quantity', '>', 0)
                ->pluck('ItemRef');

            $base->whereIn('ItemID', $itemIds->isEmpty() ? [0] : $itemIds);
        } else {
            $base->whereInStock((string) $fiscalYearRef);
        }

        return [
            'total' => (clone $base)->count(),
            'without_image' => (clone $base)->doesntHave('image')->count(),
            'without_irancode' => (clone $base)->where(function ($q) {
                $q->whereNull('IranCode')->orWhere('IranCode', '=', '');
            })->count(),
        ];
    }

    /**
     * @param  list<int>  $groupingIds
     */
    private function inventoryValue(int|string $fiscalYearRef, ?int $stockRef, array $groupingIds): float
    {
        $items = ItemStockSummary::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->where('Quantity', '>', 0)
            ->when($stockRef, fn ($q) => $q->where('StockRef', $stockRef))
            ->whereHas('item', function ($query) use ($groupingIds) {
                $query->where('CodingGroupRef', '!=', self::EXCLUDED_CODING_GROUP)
                    ->when($groupingIds !== [], fn ($q) => $q->whereIn('CodingGroupRef', $groupingIds));
            })
            ->get(['ItemRef', 'Quantity']);

        $itemIds = $items->pluck('ItemRef')->unique()->filter()->values();

        if ($itemIds->isEmpty()) {
            return 0.0;
        }

        $latestFees = $this->latestFees($itemIds->all());
        $balance = 0.0;

        foreach ($items as $item) {
            $fee = (float) ($latestFees[(int) $item->ItemRef] ?? 0);
            $balance += $fee * (float) $item->Quantity;
        }

        return $balance;
    }

    /**
     * @param  list<int>  $groupingIds
     * @return list<array{id: int, code: string, title: string, quantity: float, sales: float, profit: float, grouping_id: int|null, iran_code: string|null}>
     */
    private function itemSalesRows(int|string $fiscalYearRef, ?int $month, array $groupingIds): array
    {
        [$start, $end] = $this->periodBounds($fiscalYearRef, $month);

        if (! $start || ! $end) {
            return [];
        }

        $rows = DB::connection('sqlsrv')
            ->table('SLS.InvoiceItem as ii')
            ->join('SLS.Invoice as i', 'ii.InvoiceRef', '=', 'i.InvoiceId')
            ->join('INV.Item as it', 'ii.ItemRef', '=', 'it.ItemID')
            ->leftJoin('INV.InventoryReceiptItem as fee', function ($join) {
                $join->on('fee.ItemRef', '=', 'ii.ItemRef')
                    ->whereIn('fee.InventoryReceiptItemID', function ($query) {
                        $query->selectRaw('MAX(InventoryReceiptItemID)')
                            ->from('INV.InventoryReceiptItem')
                            ->groupBy('ItemRef');
                    });
            })
            ->where('i.FiscalYearRef', $fiscalYearRef)
            ->where('i.State', '<>', 2)
            ->whereBetween('i.Date', [$start, $end])
            ->where('it.CodingGroupRef', '!=', self::EXCLUDED_CODING_GROUP)
            ->when($groupingIds !== [], fn ($q) => $q->whereIn('it.CodingGroupRef', $groupingIds))
            ->groupBy('it.ItemID', 'it.Code', 'it.Title', 'it.CodingGroupRef', 'it.IranCode')
            ->selectRaw('
                it.ItemID as id,
                it.Code as code,
                it.Title as title,
                it.CodingGroupRef as grouping_id,
                it.IranCode as iran_code,
                SUM(ii.Quantity) as quantity,
                SUM(ii.Price) as sales,
                SUM(ii.NetPrice - ISNULL(fee.Fee, 0) * ii.Quantity) as profit
            ')
            ->get();

        return $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'code' => (string) ($row->code ?? ''),
            'title' => (string) ($row->title ?? ''),
            'quantity' => (float) $row->quantity,
            'sales' => (float) $row->sales,
            'profit' => (float) $row->profit,
            'grouping_id' => $row->grouping_id !== null ? (int) $row->grouping_id : null,
            'iran_code' => $row->iran_code !== null && $row->iran_code !== '' ? (string) $row->iran_code : null,
        ])->all();
    }

    /**
     * @param  list<array{id: int, code: string, title: string, quantity: float, sales: float, profit: float, grouping_id: int|null, iran_code: string|null}>  $itemSales
     * @return list<array{id: int, title: string, quantity: float, sales: float, profit: float}>
     */
    private function aggregateGroups(array $itemSales): array
    {
        $grouped = [];

        foreach ($itemSales as $row) {
            $groupId = (int) ($row['grouping_id'] ?? 0);

            if ($groupId <= 0) {
                continue;
            }

            if (! isset($grouped[$groupId])) {
                $grouped[$groupId] = [
                    'id' => $groupId,
                    'title' => '',
                    'quantity' => 0.0,
                    'sales' => 0.0,
                    'profit' => 0.0,
                ];
            }

            $grouped[$groupId]['quantity'] += $row['quantity'];
            $grouped[$groupId]['sales'] += $row['sales'];
            $grouped[$groupId]['profit'] += $row['profit'];
        }

        if ($grouped === []) {
            return [];
        }

        $titles = Grouping::query()
            ->whereIn('GroupingID', array_keys($grouped))
            ->pluck('Title', 'GroupingID');

        foreach ($grouped as $id => $row) {
            $grouped[$id]['title'] = (string) ($titles[$id] ?? ('#'.$id));
        }

        return array_values($grouped);
    }

    /**
     * @param  list<array{id: int, code: string, title: string, quantity: float, sales: float, profit: float, grouping_id: int|null, iran_code: string|null}>  $itemSales
     * @return array{
     *     by_sales: list<array{id: int|string, title: string, quantity: float, sales: float, profit: float}>|null,
     *     by_profit: list<array{id: int|string, title: string, quantity: float, sales: float, profit: float}>|null
     * }
     */
    private function aggregateBrands(array $itemSales): array
    {
        $iranCodes = collect($itemSales)
            ->pluck('iran_code')
            ->filter()
            ->unique()
            ->values();

        if ($iranCodes->isEmpty()) {
            return ['by_sales' => null, 'by_profit' => null];
        }

        try {
            $brandMap = Product::query()
                ->whereIn('Id', $iranCodes->all())
                ->whereNotNull('BrandId')
                ->pluck('BrandId', 'Id');
        } catch (\Throwable) {
            return ['by_sales' => null, 'by_profit' => null];
        }

        if ($brandMap->isEmpty()) {
            return ['by_sales' => null, 'by_profit' => null];
        }

        $brands = [];

        foreach ($itemSales as $row) {
            $iranCode = $row['iran_code'] ?? null;

            if (! $iranCode || ! isset($brandMap[$iranCode])) {
                continue;
            }

            $brandId = (int) $brandMap[$iranCode];

            if (! isset($brands[$brandId])) {
                $brands[$brandId] = [
                    'id' => $brandId,
                    'title' => '#'.$brandId,
                    'quantity' => 0.0,
                    'sales' => 0.0,
                    'profit' => 0.0,
                ];
            }

            $brands[$brandId]['quantity'] += $row['quantity'];
            $brands[$brandId]['sales'] += $row['sales'];
            $brands[$brandId]['profit'] += $row['profit'];
        }

        if ($brands === []) {
            return ['by_sales' => null, 'by_profit' => null];
        }

        $bySales = array_values($brands);
        usort($bySales, static fn (array $a, array $b): int => $b['sales'] <=> $a['sales']);

        $byProfit = array_values($brands);
        usort($byProfit, static fn (array $a, array $b): int => $b['profit'] <=> $a['profit']);

        return [
            'by_sales' => array_slice($bySales, 0, 10),
            'by_profit' => array_slice($byProfit, 0, 10),
        ];
    }

    /**
     * @param  list<int>  $itemGroupingIds
     * @param  list<int>  $partyGroupingIds
     * @return list<array{id: int, code: string, title: string, quantity: float, sales: float, profit: float}>
     */
    private function topItemsByPartyGroupings(
        int|string $fiscalYearRef,
        ?int $month,
        array $itemGroupingIds,
        array $partyGroupingIds,
        int $limit
    ): array {
        [$start, $end] = $this->periodBounds($fiscalYearRef, $month);

        if (! $start || ! $end || $partyGroupingIds === []) {
            return [];
        }

        $rows = DB::connection('sqlsrv')
            ->table('SLS.InvoiceItem as ii')
            ->join('SLS.Invoice as i', 'ii.InvoiceRef', '=', 'i.InvoiceId')
            ->join('GNR.Party as p', 'i.CustomerPartyRef', '=', 'p.PartyId')
            ->join('INV.Item as it', 'ii.ItemRef', '=', 'it.ItemID')
            ->leftJoin('INV.InventoryReceiptItem as fee', function ($join) {
                $join->on('fee.ItemRef', '=', 'ii.ItemRef')
                    ->whereIn('fee.InventoryReceiptItemID', function ($query) {
                        $query->selectRaw('MAX(InventoryReceiptItemID)')
                            ->from('INV.InventoryReceiptItem')
                            ->groupBy('ItemRef');
                    });
            })
            ->where('i.FiscalYearRef', $fiscalYearRef)
            ->where('i.State', '<>', 2)
            ->whereBetween('i.Date', [$start, $end])
            ->where('p.IsCustomer', 1)
            ->whereIn('p.CustomerGroupingRef', $partyGroupingIds)
            ->where('it.CodingGroupRef', '!=', self::EXCLUDED_CODING_GROUP)
            ->when($itemGroupingIds !== [], fn ($q) => $q->whereIn('it.CodingGroupRef', $itemGroupingIds))
            ->groupBy('it.ItemID', 'it.Code', 'it.Title')
            ->selectRaw('
                it.ItemID as id,
                it.Code as code,
                it.Title as title,
                SUM(ii.Quantity) as quantity,
                SUM(ii.Price) as sales,
                SUM(ii.NetPrice - ISNULL(fee.Fee, 0) * ii.Quantity) as profit
            ')
            ->orderByDesc('quantity')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'code' => (string) ($row->code ?? ''),
            'title' => (string) ($row->title ?? ''),
            'quantity' => (float) $row->quantity,
            'sales' => (float) $row->sales,
            'profit' => (float) $row->profit,
        ])->all();
    }

    /**
     * @param  list<int>  $itemGroupingIds
     * @return list<array{id: int, code: string, title: string, quantity: float, sales: float, profit: float}>
     */
    private function topItemsByUsersSegment(
        int|string $fiscalYearRef,
        ?int $month,
        array $itemGroupingIds,
        int $limit
    ): array {
        [$start, $end] = $this->periodBounds($fiscalYearRef, $month);

        if (! $start || ! $end) {
            return [];
        }

        $excluded = array_values(array_unique([
            ...self::ORGANIZATIONAL_GROUPING_IDS,
            ...self::COLLEAGUE_GROUPING_IDS,
        ]));

        $rows = DB::connection('sqlsrv')
            ->table('SLS.InvoiceItem as ii')
            ->join('SLS.Invoice as i', 'ii.InvoiceRef', '=', 'i.InvoiceId')
            ->join('GNR.Party as p', 'i.CustomerPartyRef', '=', 'p.PartyId')
            ->join('INV.Item as it', 'ii.ItemRef', '=', 'it.ItemID')
            ->leftJoin('INV.InventoryReceiptItem as fee', function ($join) {
                $join->on('fee.ItemRef', '=', 'ii.ItemRef')
                    ->whereIn('fee.InventoryReceiptItemID', function ($query) {
                        $query->selectRaw('MAX(InventoryReceiptItemID)')
                            ->from('INV.InventoryReceiptItem')
                            ->groupBy('ItemRef');
                    });
            })
            ->where('i.FiscalYearRef', $fiscalYearRef)
            ->where('i.State', '<>', 2)
            ->whereBetween('i.Date', [$start, $end])
            ->where('p.IsCustomer', 1)
            ->where(function ($q) use ($excluded) {
                $q->whereNull('p.CustomerGroupingRef')
                    ->orWhereNotIn('p.CustomerGroupingRef', $excluded);
            })
            ->where('it.CodingGroupRef', '!=', self::EXCLUDED_CODING_GROUP)
            ->when($itemGroupingIds !== [], fn ($q) => $q->whereIn('it.CodingGroupRef', $itemGroupingIds))
            ->groupBy('it.ItemID', 'it.Code', 'it.Title')
            ->selectRaw('
                it.ItemID as id,
                it.Code as code,
                it.Title as title,
                SUM(ii.Quantity) as quantity,
                SUM(ii.Price) as sales,
                SUM(ii.NetPrice - ISNULL(fee.Fee, 0) * ii.Quantity) as profit
            ')
            ->orderByDesc('quantity')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'code' => (string) ($row->code ?? ''),
            'title' => (string) ($row->title ?? ''),
            'quantity' => (float) $row->quantity,
            'sales' => (float) $row->sales,
            'profit' => (float) $row->profit,
        ])->all();
    }

    /**
     * @param  list<int>  $groupingIds
     * @return list<array{id: int, code: string, title: string, quantity: float, last_sale_at: string|null}>
     */
    private function stagnantItems(int|string $fiscalYearRef, ?int $stockRef, array $groupingIds, int $limit): array
    {
        $cutoff = now()->subMonths(self::STALE_MONTHS)->startOfDay();

        $lastSaleSql = '(SELECT MAX(i.[Date]) FROM [SLS].[InvoiceItem] ii INNER JOIN [SLS].[Invoice] i ON i.[InvoiceId] = ii.[InvoiceRef] AND i.[State] <> 2 WHERE ii.[ItemRef] = [INV].[Item].[ItemID])';

        $qtySql = $stockRef
            ? '(SELECT COALESCE(SUM(CAST(s.[Quantity] AS DECIMAL(18,4))), 0) FROM [INV].[ItemStockSummary] s WHERE s.[ItemRef] = [INV].[Item].[ItemID] AND s.[FiscalYearRef] = ? AND s.[StockRef] = ?)'
            : '(SELECT COALESCE(SUM(CAST(s.[Quantity] AS DECIMAL(18,4))), 0) FROM [INV].[ItemStockSummary] s WHERE s.[ItemRef] = [INV].[Item].[ItemID] AND s.[FiscalYearRef] = ?)';

        $qtyBindings = $stockRef ? [$fiscalYearRef, $stockRef] : [$fiscalYearRef];

        $rows = Item::query()
            ->where('CodingGroupRef', '!=', self::EXCLUDED_CODING_GROUP)
            ->when($groupingIds !== [], fn ($q) => $q->whereIn('CodingGroupRef', $groupingIds))
            ->whereRaw("{$qtySql} > 0", $qtyBindings)
            ->whereRaw("({$lastSaleSql} IS NULL OR {$lastSaleSql} < ?)", [$cutoff])
            ->select(['ItemID', 'Code', 'Title'])
            ->selectRaw("{$qtySql} as stock_quantity", $qtyBindings)
            ->selectRaw("{$lastSaleSql} as last_sale_at")
            ->orderByRaw("CASE WHEN {$lastSaleSql} IS NULL THEN 0 ELSE 1 END ASC")
            ->orderByRaw("{$lastSaleSql} ASC")
            ->limit($limit)
            ->get();

        return $rows->map(fn (Item $item) => [
            'id' => (int) $item->ItemID,
            'code' => (string) ($item->Code ?? ''),
            'title' => (string) ($item->Title ?? ''),
            'quantity' => (float) ($item->stock_quantity ?? 0),
            'last_sale_at' => $item->last_sale_at
                ? Carbon::parse($item->last_sale_at)->toDateString()
                : null,
        ])->all();
    }

    /**
     * @param  list<int|string>  $itemIds
     * @return array<int, float>
     */
    private function latestFees(array $itemIds): array
    {
        return InventoryReceiptItem::query()
            ->whereIn('ItemRef', $itemIds)
            ->whereIn('InventoryReceiptItemID', function ($query) {
                $query->selectRaw('MAX(InventoryReceiptItemID)')
                    ->from('INV.InventoryReceiptItem')
                    ->groupBy('ItemRef');
            })
            ->pluck('Fee', 'ItemRef')
            ->mapWithKeys(fn ($fee, $itemRef) => [(int) $itemRef => (float) $fee])
            ->all();
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
