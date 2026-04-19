<?php

namespace App\Models\Sepidar\ACC;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    public $table = 'ACC.Voucher';
    public $connection = 'sqlsrv';
    public $primaryKey = 'VoucherId';

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VoucherItem::class, 'VoucherRef', 'VoucherId');
    }
}
