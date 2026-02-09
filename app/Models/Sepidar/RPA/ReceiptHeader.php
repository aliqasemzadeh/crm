<?php

namespace App\Models\Sepidar\RPA;

use Illuminate\Database\Eloquent\Model;

class ReceiptHeader extends Model
{
    public $table = 'RPA.ReceiptHeader';
    public $connection = 'sqlsrv';
}
