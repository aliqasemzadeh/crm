<?php

namespace App\Models\Sepidar\INV;

use App\Models\Sepidar\SLS\Invoice;
use App\Models\Sepidar\SLS\InvoiceItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReceiptItem extends Model
{
    public $table = 'sepidar_inventory_receipt_items';
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(InventoryReceipt::class, 'InventoryReceiptRef', 'InventoryReceiptID');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'ItemRef', 'ItemID');
    }
}
