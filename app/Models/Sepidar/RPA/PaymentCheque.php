<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;

class PaymentCheque extends Model
{
    public $table = 'RPA.PaymentCheque';
    public $connection = 'sqlsrv';
}
