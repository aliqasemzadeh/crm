<?php

namespace App\Models\Sepidar\RPA;

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
            \App\Livewire\Panels\Accounting\ReceiptCheque\Index::clearCache();
        });

        static::created(function ($cheque) {
            \App\Livewire\Panels\Accounting\ReceiptCheque\Index::clearCache();
        });

        static::updated(function ($cheque) {
            \App\Livewire\Panels\Accounting\ReceiptCheque\Index::clearCache();
        });
    }

    public function dl(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Sepidar\ACC\DL::class, 'DlRef', 'DLId');
    }

    public function latestBankingItem(): HasOne
    {
        return $this->hasOne(ReceiptChequeBankingItem::class, 'ReceiptChequeRef', 'ReceiptChequeId')
            ->latestOfMany('ReceiptChequeBankingItemId');
    }
}
