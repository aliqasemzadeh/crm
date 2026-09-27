<?php

namespace App\Models\Sepidar\RPA;

use App\Livewire\Panels\Accounting\Dashboard\Index as DashboardIndex;
use App\Livewire\Panels\Accounting\ReceiptCheque\Index;
use App\Models\Sepidar\ACC\DL;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReceiptCheque extends Model
{
    public $table = 'RPA.ReceiptCheque';

    public $connection = 'sqlsrv';

    public $primaryKey = 'ReceiptChequeId';

    protected $casts = [
        'Date' => 'datetime',
        'State' => 'integer',
        'Amount' => 'decimal:4',
    ];

    protected static function booted()
    {
        static::deleted(function ($cheque) {
            Index::clearCache();
            DashboardIndex::clearCache();
        });

        static::created(function ($cheque) {
            Index::clearCache();
            DashboardIndex::clearCache();
        });

        static::updated(function ($cheque) {
            Index::clearCache();
            DashboardIndex::clearCache();
        });
    }

    public function dl(): BelongsTo
    {
        return $this->belongsTo(DL::class, 'DlRef', 'DLId');
    }

    public function latestBankingItem(): HasOne
    {
        return $this->hasOne(ReceiptChequeBankingItem::class, 'ReceiptChequeRef', 'ReceiptChequeId')
            ->latestOfMany('ReceiptChequeBankingItemId');
    }
}
