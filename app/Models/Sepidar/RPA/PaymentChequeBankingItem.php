<?php

namespace App\Models\Sepidar\RPA;

use App\Services\Accounting\TodayChequeRemainingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentChequeBankingItem extends Model
{
    public $table = 'RPA.PaymentChequeBankingItem';

    public $connection = 'sqlsrv';

    public $primaryKey = 'PaymentChequeBankingItemId';

    public $timestamps = false;

    protected static function booted(): void
    {
        static::created(function () {
            TodayChequeRemainingService::clearCache();
        });

        static::deleted(function () {
            TodayChequeRemainingService::clearCache();
        });
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'BankAccountRef', 'BankAccountId');
    }
}
