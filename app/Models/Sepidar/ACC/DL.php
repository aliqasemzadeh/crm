<?php

namespace App\Models\Sepidar\ACC;

use App\Models\Sepidar\RPA\PaymentCheque;
use App\Models\Sepidar\RPA\ReceiptCheque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DL extends Model
{
    protected $table = 'ACC.DL';
    protected $connection = 'sqlsrv';
    protected $primaryKey = 'DLId';

    public function voucherItems(): HasMany
    {
        return $this->hasMany(VoucherItem::class, 'DLRef', 'DLId');
    }

    public function receiptCheques(): HasMany
    {
        return $this->hasMany(ReceiptCheque::class, 'DlRef', 'DLId');
    }

    public function paymentCheques(): HasMany
    {
        return $this->hasMany(PaymentCheque::class, 'DlRef', 'DLId');
    }
}
