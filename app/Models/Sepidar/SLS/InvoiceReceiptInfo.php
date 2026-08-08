<?php

namespace App\Models\Sepidar\SLS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceReceiptInfo extends Model
{
    public $table = 'SLS.InvoiceReceiptInfo';

    public $connection = 'sqlsrv';

    public $primaryKey = 'InvoiceReceiptInfoID';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'InvoiceReceiptInfoID',
        'InvoiceRef',
        'Discount',
        'Amount',
        'DraftAmount',
        'PartyAccountSettlementItemRef',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'InvoiceRef', 'InvoiceId');
    }
}
