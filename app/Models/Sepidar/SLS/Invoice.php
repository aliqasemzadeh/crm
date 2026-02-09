<?php

namespace App\Models\Sepidar\SLS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public $table = 'sepidar_invoices';

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'InvoiceRef', 'InvoiceId');
    }
}
