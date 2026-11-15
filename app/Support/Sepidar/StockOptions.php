<?php

namespace App\Support\Sepidar;

use App\Models\Sepidar\INV\ItemStockSummary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockOptions
{
    /**
     * @return Collection<int, string>
     */
    public static function labels(?int $preferredStockRef = null): Collection
    {
        $fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        $preferred = $preferredStockRef ?? (int) config('sepidar.DefaultStockRef', 2);

        $refs = ItemStockSummary::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->distinct()
            ->orderBy('StockRef')
            ->pluck('StockRef')
            ->map(fn ($ref) => (int) $ref)
            ->filter()
            ->values();

        if ($preferred > 0 && ! $refs->contains($preferred)) {
            $refs->prepend($preferred);
        }

        if ($refs->isEmpty()) {
            return collect($preferred > 0
                ? [$preferred => __('app.warehouse_stock_label', ['id' => $preferred])]
                : []
            );
        }

        $titles = collect();

        try {
            $placeholders = implode(',', array_fill(0, $refs->count(), '?'));
            $titles = collect(DB::connection('sqlsrv')->select(
                "SELECT StockID, Title FROM INV.Stock WHERE StockID IN ({$placeholders})",
                $refs->all()
            ))->pluck('Title', 'StockID');
        } catch (\Throwable) {
            // Stock table may be unavailable; fall back to numeric labels.
        }

        return $refs->mapWithKeys(function (int $ref) use ($titles) {
            $title = trim((string) ($titles[$ref] ?? ''));

            return [$ref => $title !== '' ? $title : __('app.warehouse_stock_label', ['id' => $ref])];
        });
    }

    public static function labelFor(?int $stockRef): string
    {
        if (! $stockRef) {
            return '-';
        }

        return (string) (static::labels($stockRef)->get($stockRef)
            ?? __('app.warehouse_stock_label', ['id' => $stockRef]));
    }
}
