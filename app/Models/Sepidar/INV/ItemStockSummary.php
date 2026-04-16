<?php

namespace App\Models\Sepidar\INV;

use Illuminate\Database\Eloquent\Model;

class ItemStockSummary extends Model
{
    public $table = 'INV.ItemStockSummary';
    public $connection = 'sqlsrv';
    public $primaryKey = 'ItemStockSummaryId';

    public function item(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Item::class, 'ItemRef', 'ItemID');
    }
}
