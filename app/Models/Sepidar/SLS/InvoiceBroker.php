<?php

namespace App\Models\Sepidar\SLS;

use App\Models\Sepidar\GNR\Party;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceBroker extends Model
{
    public $table = 'SLS.InvoiceBroker';

    public $connection = 'sqlsrv';

    public $primaryKey = 'BrokerID';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'BrokerID',
        'InvoiceRef',
        'PartyRef',
        'Commission',
        'Rate',
        'CommissionInBaseCurrency',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'InvoiceRef', 'InvoiceId');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'PartyRef', 'PartyId');
    }
}
