<?php

namespace App\Models\Sepidar\RPA;

use App\Models\Sepidar\FMK\User;
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Creator', 'UserID');
    }

    public function balances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BankAccountBalance::class, 'BankAccountRef', 'BankAccountId');
    }
}
