<?php

namespace App\Models\Sepidar\INV;

use App\Models\Sepidar\FMK\User;
use App\Models\Sepidar\GNR\Grouping;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * @param  Builder<Item>  $query
     * @return Builder<Item>
     */
    public function scopeWhereInStock(Builder $query, string $fiscalYearRef): Builder
    {
        $sql = '(SELECT COALESCE(SUM(CAST(s.[Quantity] AS DECIMAL(18,4))), 0) FROM [INV].[ItemStockSummary] s WHERE s.[ItemRef] = [INV].[Item].[ItemID] AND s.[FiscalYearRef] = ?)';

        return $query->whereRaw("{$sql} > 0", [$fiscalYearRef]);
    }

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

    public function stockQuantity(?string $fiscalYearRef = null): float
    {
        $fiscalYearRef ??= config('sepidar.FiscalYearRef');

        return (float) $this->stockSummaries()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->sum('Quantity');
    }

    public function siteUrl(): ?string
    {
        if (! $this->IranCode) {
            return null;
        }

        return 'https://setaregan.co/Product/'.$this->IranCode;
    }

    /**
     * Site MinPrice stored in toman; return rial for UI consistency with Sepidar fees.
     */
    public function siteMinPriceRial(): ?int
    {
        $minPrice = $this->product?->MinPrice;

        if ($minPrice === null || $minPrice === '') {
            return null;
        }

        return (int) $minPrice * 10;
    }

    public function mainGroupingTitle(): ?string
    {
        if (! $this->grouping) {
            return null;
        }

        return $this->grouping->rootAncestor()->Title ?? $this->grouping->Title;
    }
}
