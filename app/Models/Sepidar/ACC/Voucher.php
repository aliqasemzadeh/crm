<?php

namespace App\Models\Sepidar\ACC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    public $table = 'ACC.Voucher';

    public $connection = 'sqlsrv';

    public $primaryKey = 'VoucherId';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'VoucherId',
        'Number',
        'Date',
        'ReferenceNumber',
        'SecondaryNumber',
        'State',
        'Type',
        'FiscalYearRef',
        'Description',
        'Description_En',
        'Version',
        'Creator',
        'CreationDate',
        'LastModifier',
        'LastModificationDate',
        'DailyNumber',
        'IssuerSystem',
        'IsMerged',
        'MergedIssuerSystem',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(VoucherItem::class, 'VoucherRef', 'VoucherId');
    }
}
