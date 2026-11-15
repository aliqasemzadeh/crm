<?php

namespace App\Services\Sepidar;

use App\Models\Sepidar\INV\ItemStock;
use App\Support\Sepidar\NextIdAllocator;

class ItemStockEnsure
{
    /**
     * @param  list<array{item_ref: int, stock_ref: int}>  $pairs
     */
    public function ensure(array $pairs): void
    {
        $unique = [];

        foreach ($pairs as $pair) {
            $itemRef = (int) ($pair['item_ref'] ?? 0);
            $stockRef = (int) ($pair['stock_ref'] ?? 0);

            if ($itemRef <= 0 || $stockRef <= 0) {
                continue;
            }

            $unique["{$itemRef}:{$stockRef}"] = [
                'item_ref' => $itemRef,
                'stock_ref' => $stockRef,
            ];
        }

        if ($unique === []) {
            return;
        }

        $nextId = NextIdAllocator::nextRange('INV.ItemStock', 'ItemStockID', count($unique));
        $created = false;

        foreach ($unique as $pair) {
            $exists = ItemStock::query()
                ->where('ItemRef', $pair['item_ref'])
                ->where('StockRef', $pair['stock_ref'])
                ->exists();

            if ($exists) {
                continue;
            }

            ItemStock::query()->create([
                'ItemStockID' => $nextId++,
                'ItemRef' => $pair['item_ref'],
                'StockRef' => $pair['stock_ref'],
                'Version' => 1,
            ]);
            $created = true;
        }

        if ($created) {
            NextIdAllocator::syncIdentity('INV.ItemStock', 'ItemStockID');
        }
    }
}
