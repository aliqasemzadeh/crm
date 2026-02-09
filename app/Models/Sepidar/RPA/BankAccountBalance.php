<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccountBalance extends Model
{
    public $table = 'sepidar_bank_account_balances';
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'BankAccountRef', 'BankAccountId');
    }

}
