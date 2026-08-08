<?php

namespace App\Models\Sepidar\SLS;

use App\Models\Sepidar\GNR\Party;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceCommissionBroker extends Model
{
    public $table = 'SLS.InvoiceCommissionBroker';

    public $connection = 'sqlsrv';

    public $primaryKey = 'InvoiceCommissionBrokerID';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'InvoiceCommissionBrokerID',
        'InvoiceRef',
        'PartyRef',
        'SalePortionPercent',
        'ManualCommissionAmount',
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
