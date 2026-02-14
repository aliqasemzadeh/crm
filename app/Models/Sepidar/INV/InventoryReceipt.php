<?php

namespace App\Models\Sepidar\INV;

use App\Livewire\Panels\Accounting\InventoryReceipt\Index;
use App\Models\Sepidar\ACC\DL;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventoryReceipt extends Model
{
    public $table = 'INV.InventoryReceipt';
    public $connection = 'sqlsrv';
    public $primaryKey = 'InventoryReceiptID';

    protected static function booted()
    {
        static::addGlobalScope('notReturn', function (Builder $builder) {
            $builder->where('IsReturn', 0);
        });

        static::deleted(function ($receipt) {
            Index::clearCache();
        });

        static::created(function ($receipt) {
            Index::clearCache();
        });

        static::updated(function ($receipt) {
            Index::clearCache();
        });
    }

    public function dl(): BelongsTo
    {
        return $this->belongsTo(DL::class, 'DelivererDLRef', 'DLId');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryReceiptItem::class, 'InventoryReceiptRef', 'InventoryReceiptID');
    }

    public function lastItem(): HasOne
    {
        return $this->hasOne(
            InventoryReceiptItem::class,
            'InventoryReceiptRef',
            'InventoryReceiptID'
        )->latest('InventoryReceiptItemID');
    }

    public function maxPriceItem(): HasOne
    {
        return $this->hasOne(
                    InventoryReceiptItem::class,
                    'InventoryReceiptRef',
                    'InventoryReceiptID'
                )->orderByDesc('Fee');
    }

    public function maxFeeItem(): HasOne
    {
        return $this->hasOne(
                    InventoryReceiptItem::class,
                    'InventoryReceiptRef',
                    'InventoryReceiptID'
                )->orderByDesc('Fee'); // برای رفع تساوی
    }

    public function getPriceAttribute()
    {
        return $this->TotalPrice;
    }

    public function getWeightedAveragePriceAttribute()
    {
        $totalQuantity = $this->items->sum('Quantity');
        if ($totalQuantity == 0) {
            return 0;
        }

        $totalValue = $this->items->sum(function ($item) {
            return $item->Quantity * $item->Fee;
        });

        return $totalValue / $totalQuantity;
    }
}
