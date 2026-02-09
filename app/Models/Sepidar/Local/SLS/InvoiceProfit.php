<?php

namespace App\Models\Sepidar\Local\SLS;

use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceProfit extends Model
{
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'InvoiceRef', 'InvoiceId');
    }
}
