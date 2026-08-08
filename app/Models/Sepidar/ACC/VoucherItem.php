<?php

namespace App\Models\Sepidar\ACC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherItem extends Model
{
    public $table = 'ACC.VoucherItem';

    public $connection = 'sqlsrv';

    public $primaryKey = 'VoucherItemId';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'VoucherItemId',
        'VoucherRef',
        'RowNumber',
        'AccountSLRef',
        'DLRef',
        'Debit',
        'Credit',
        'CurrencyRef',
        'CurrencyRate',
        'CurrencyDebit',
        'CurrencyCredit',
        'TrackingNumber',
        'TrackingDate',
        'IssuerEntityName',
        'IssuerEntityRef',
        'Description',
        'Description_En',
        'Version',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'VoucherRef', 'VoucherId');
    }

    public function dl(): BelongsTo
    {
        return $this->belongsTo(DL::class, 'DLRef', 'DLId');
    }
}
