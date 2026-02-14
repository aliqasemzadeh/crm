<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;

class ReceiptCheque extends Model
{
    public $table = 'RPA.ReceiptCheque';
    public $connection = 'sqlsrv';
    public $primaryKey = 'ReceiptChequeId';

    protected static function booted()
    {
        static::deleted(function ($cheque) {
            \App\Livewire\Panels\Accounting\ReceiptCheque\Index::clearCache();
        });

        static::created(function ($cheque) {
            \App\Livewire\Panels\Accounting\ReceiptCheque\Index::clearCache();
        });

        static::updated(function ($cheque) {
            \App\Livewire\Panels\Accounting\ReceiptCheque\Index::clearCache();
        });
    }

    public function dl()
    {
        return $this->belongsTo(\App\Models\Sepidar\ACC\DL::class, 'DlRef', 'DLId');
    }
}
