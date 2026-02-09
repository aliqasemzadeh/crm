<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;

class PaymentHeader extends Model
{
    public $table = 'RPA.PaymentHeader';
    public $connection = 'sqlsrv';
}
