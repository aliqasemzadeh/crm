<?php

namespace App\Models\Sepidar\RPA;

use App\Livewire\Panels\Accounting\PaymentHeader\Index;
use Illuminate\Database\Eloquent\Model;

class PaymentHeader extends Model
{
    public $table = 'RPA.PaymentHeader';
    public $connection = 'sqlsrv';

    protected static function booted()
    {
        static::deleted(function ($paymentHeader) {
            Index::clearCache();
        });

        static::created(function ($paymentHeader) {
            Index::clearCache();
        });

        static::updated(function ($paymentHeader) {
            Index::clearCache();
        });
    }
}
