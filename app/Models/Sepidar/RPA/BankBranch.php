<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankBranch extends Model
{
    public $table = 'sepidar_bank_branches';

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'BankRef', 'BankId');
    }
}
