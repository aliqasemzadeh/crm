<?php

namespace App\Models\Sepidar\SLS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItemAdditionFactor extends Model
{
    public $table = 'SLS.InvoiceItemAdditionFactor';

    public $connection = 'sqlsrv';

    public $primaryKey = 'InvoiceItemAdditionFactorID';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'InvoiceItemAdditionFactorID',
        'InvoiceItemRef',
        'AdditionFactorRef',
        'Value',
        'ValueInBaseCurrency',
    ];

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'InvoiceItemRef', 'InvoiceItemID');
    }
}
