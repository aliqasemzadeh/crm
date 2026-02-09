<?php

namespace App\Models\Sepidar\INV;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    public $table = 'sepidar_items';

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(InventoryReceiptItem::class, 'ItemRef', 'ItemID')->latest()->first()->withDefault(['Title' => null]);
    }
}
