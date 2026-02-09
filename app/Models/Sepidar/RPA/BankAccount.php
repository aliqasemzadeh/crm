<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    public $table = 'sepidar_bank_accounts';

    public function bankBranch(): BelongsTo
    {
        return $this->belongsTo(BankBranch::class, 'BankBranchRef', 'BankBranchId');
    }
}
