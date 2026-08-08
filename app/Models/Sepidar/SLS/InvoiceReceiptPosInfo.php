<?php

namespace App\Models\Sepidar\SLS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceReceiptPosInfo extends Model
{
    public $table = 'SLS.InvoiceReceiptPosInfo';

    public $connection = 'sqlsrv';

    public $primaryKey = 'InvoiceReceiptPosInfoId';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'InvoiceReceiptPosInfoId',
        'InvoiceRef',
        'Amount',
        'TrackingCode',
        'PartyAccountSettlementItemRef',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'InvoiceRef', 'InvoiceId');
    }
}
