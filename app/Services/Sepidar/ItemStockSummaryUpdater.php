<?php

namespace App\Services\Sepidar;

use Illuminate\Support\Facades\DB;

class ItemStockSummaryUpdater
{
    /**
     * @param  list<array{stock_ref: int, item_ref: int, tracing_ref?: int|null, fiscal_year_ref?: int|null}>  $keys
     */
    public function refresh(array $keys): void
    {
        $unique = [];
        $fiscalYearDefault = (int) config('sepidar.FiscalYearRef');

        foreach ($keys as $key) {
            $stockRef = (int) ($key['stock_ref'] ?? 0);
            $itemRef = (int) ($key['item_ref'] ?? 0);
            $fiscalYearRef = (int) ($key['fiscal_year_ref'] ?? $fiscalYearDefault);
            $tracingRef = array_key_exists('tracing_ref', $key) && $key['tracing_ref'] !== null
                ? (int) $key['tracing_ref']
                : null;

            if ($stockRef <= 0 || $itemRef <= 0 || $fiscalYearRef <= 0) {
                continue;
            }

            $unique["{$stockRef}:{$itemRef}:".($tracingRef ?? 'null').":{$fiscalYearRef}"] = [
                'stock_ref' => $stockRef,
                'item_ref' => $itemRef,
                'tracing_ref' => $tracingRef,
                'fiscal_year_ref' => $fiscalYearRef,
            ];
        }

        if ($unique === []) {
            return;
        }

        $valueSql = [];
        $bindings = [];

        foreach ($unique as $row) {
            $valueSql[] = '(?, ?, ?, ?, 0)';
            $bindings[] = $row['stock_ref'];
            $bindings[] = $row['item_ref'];
            $bindings[] = $row['tracing_ref'];
            $bindings[] = $row['fiscal_year_ref'];
        }

        $sql = '
            DECLARE @t [INV].[SummaryRecordTable];
            INSERT INTO @t ([StockID], [ItemID], [TracingID], [FiscalYearID], [FeedFromClosingOperation])
            VALUES '.implode(",\n", $valueSql).';
            EXEC [INV].[spUpdateItemStockSummary]
                @SummaryTable = @t,
                @UpdateSaleWithReserve = 1,
                @IncludeRegisteredOrders = 0;
        ';

        DB::connection('sqlsrv')->statement($sql, $bindings);
    }
}
