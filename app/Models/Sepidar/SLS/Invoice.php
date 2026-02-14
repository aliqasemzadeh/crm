<?php

namespace App\Models\Sepidar\SLS;

use App\Livewire\Panels\Accounting\Invoice\Index;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public $table = 'SLS.Invoice';
    public $connection = 'sqlsrv';
    public $primaryKey = 'InvoiceId';

    protected static function booted()
    {
        static::deleted(function ($invoice) {
            Index::clearCache();
        });

        static::created(function ($invoice) {
            Index::clearCache();
        });

        static::updated(function ($invoice) {
            Index::clearCache();
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'InvoiceRef', 'InvoiceId');
    }
}
