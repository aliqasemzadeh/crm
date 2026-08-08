<?php

namespace App\Models\Sepidar\SLS;

use App\Models\Sepidar\RPA\Bank;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceReceiptChequeInfo extends Model
{
    public $table = 'SLS.InvoiceReceiptChequeInfo';

    public $connection = 'sqlsrv';

    public $primaryKey = 'InvoiceReceiptChequeInfoId';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'InvoiceReceiptChequeInfoId',
        'InvoiceRef',
        'Number',
        'Amount',
        'Date',
        'AccountNo',
        'BankRef',
        'PartyAccountSettlementItemRef',
        'SayadCode',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'InvoiceRef', 'InvoiceId');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'BankRef', 'BankId');
    }
}
