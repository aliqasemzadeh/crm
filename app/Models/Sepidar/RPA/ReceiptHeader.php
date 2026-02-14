<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;

class ReceiptHeader extends Model
{
    public $table = 'RPA.ReceiptHeader';
    public $connection = 'sqlsrv';
    public $primaryKey = 'ReceiptHeaderId';

    protected static function booted()
    {
        static::deleted(function ($receiptHeader) {
            \App\Livewire\Panels\Accounting\ReceiptHeader\Index::clearCache();
        });

        static::created(function ($receiptHeader) {
            \App\Livewire\Panels\Accounting\ReceiptHeader\Index::clearCache();
        });

        static::updated(function ($receiptHeader) {
            \App\Livewire\Panels\Accounting\ReceiptHeader\Index::clearCache();
        });
    }
}
