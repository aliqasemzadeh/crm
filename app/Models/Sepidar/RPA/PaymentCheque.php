<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;

class PaymentCheque extends Model
{
    public $table = 'RPA.PaymentCheque';
    public $connection = 'sqlsrv';
    public $primaryKey = 'PaymentChequeId';

    protected static function booted()
    {
        static::deleted(function ($cheque) {
            \App\Livewire\Panels\Accounting\PaymentCheque\Index::clearCache();
        });

        static::created(function ($cheque) {
            \App\Livewire\Panels\Accounting\PaymentCheque\Index::clearCache();
        });

        static::updated(function ($cheque) {
            \App\Livewire\Panels\Accounting\PaymentCheque\Index::clearCache();
        });
    }

    public function dl()
    {
        return $this->belongsTo(\App\Models\Sepidar\ACC\DL::class, 'DlRef', 'DLId');
    }
}
