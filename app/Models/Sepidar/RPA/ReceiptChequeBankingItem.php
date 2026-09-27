<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptChequeBankingItem extends Model
{
    public $table = 'RPA.ReceiptChequeBankingItem';

    public $connection = 'sqlsrv';

    public $primaryKey = 'ReceiptChequeBankingItemId';

    public $timestamps = false;

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'BankAccountRef', 'BankAccountId');
    }
}
