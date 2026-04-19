<?php

namespace App\Models\Sepidar\ACC;

use Illuminate\Database\Eloquent\Model;

class VoucherItem extends Model
{
    public $table = 'ACC.VoucherItem';
    public $connection = 'sqlsrv';
    public $primaryKey = 'VoucherItemId';

    public function voucher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'VoucherRef', 'VoucherId');
    }

    public function dl(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DL::class, 'DLRef', 'DLId');
    }
}
