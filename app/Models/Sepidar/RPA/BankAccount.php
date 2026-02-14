<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    public $table = 'RPA.BankAccount';
    public $connection = 'sqlsrv';

    public function bankBranch(): BelongsTo
    {
        return $this->belongsTo(BankBranch::class, 'BankBranchRef', 'BankBranchId');
    }

    public function balances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BankAccountBalance::class, 'BankAccountRef', 'BankAccountId');
    }
}
