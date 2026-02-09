<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankBranch extends Model
{
    public $table = 'RPA.BankBranch';
    public $connection = 'sqlsrv';

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'BankRef', 'BankId');
    }
}
