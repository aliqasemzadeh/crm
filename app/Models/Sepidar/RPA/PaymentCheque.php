<?php

namespace App\Models\Sepidar\RPA;

use App\Livewire\Panels\Accounting\Dashboard\Index as DashboardIndex;
use App\Livewire\Panels\Accounting\PaymentCheque\Index;
use App\Models\Sepidar\ACC\DL;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentCheque extends Model
{
    public $table = 'RPA.PaymentCheque';

    public $connection = 'sqlsrv';

    public $primaryKey = 'PaymentChequeId';

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

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'BankAccountRef', 'BankAccountId');
    }

    public function bankingItems(): HasMany
    {
        return $this->hasMany(PaymentChequeBankingItem::class, 'PaymentChequeRef', 'PaymentChequeId');
    }

    public function scopeWithPassedFlag(Builder $query): Builder
    {
        return $query->select($query->getModel()->getTable().'.*')->selectRaw(
            'CASE WHEN EXISTS (
                SELECT 1 FROM [RPA].[PaymentChequeBankingItem]
                WHERE [RPA].[PaymentChequeBankingItem].[PaymentChequeRef] = [RPA].[PaymentCheque].[PaymentChequeId]
            ) THEN 1 ELSE 0 END AS [is_passed]'
        );
    }
}
