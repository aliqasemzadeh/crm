<?php

namespace App\Models\Sepidar\INV;

use App\Models\Sepidar\FMK\User;
use App\Models\Sepidar\GNR\Grouping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    public $table = 'INV.Item';
    public $connection = 'sqlsrv';
    public $primaryKey = 'ItemID';
    public $timestamps = false;
    protected $fillable = ['IranCode'];

    public function grouping(): BelongsTo
    {
        return $this->belongsTo(Grouping::class, 'CodingGroupRef', 'GroupingID');
    }

    public function image(): HasOne
    {
        return $this->hasOne(ItemImage::class, 'ItemRef', 'ItemID');
    }

    public function stockSummaries(): HasMany
    {
        return $this->hasMany(ItemStockSummary::class, 'ItemRef', 'ItemID');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Creator', 'UserID');
    }

    public function product(): HasOne
    {
        return $this->hasOne(\App\Models\SetareganCo\Product::class, 'Id', 'IranCode');
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(\App\Models\SetareganCo\ProductPrice::class, 'ProductId', 'IranCode');
    }

    public function inventoryReceiptItems(): HasMany
    {
        return $this->hasMany(InventoryReceiptItem::class, 'ItemRef', 'ItemID');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(\App\Models\Sepidar\SLS\InvoiceItem::class, 'ItemRef', 'ItemID');
    }

    public function getLastPurchasePrice()
    {
        return \Illuminate\Support\Facades\Cache::remember("item_{$this->ItemID}_last_purchase_price", now()->addHours(1), function () {
            return $this->inventoryReceiptItems()->latest('InventoryReceiptItemID')->value('Fee') ?? 0;
        });
    }

    public function getLastSalePrice()
    {
        return \Illuminate\Support\Facades\Cache::remember("item_{$this->ItemID}_last_sale_price", now()->addHours(1), function () {
            return $this->invoiceItems()->latest('InvoiceItemId')->value('Fee') ?? 0;
        });
    }
}
