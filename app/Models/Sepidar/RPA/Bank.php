<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    public $table = 'RPA.Bank';
    public $connection = 'sqlsrv';

    public function bankBranches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BankBranch::class, 'BankRef', 'BankId');
    }
}
