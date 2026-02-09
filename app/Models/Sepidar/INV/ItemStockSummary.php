<?php

namespace App\Models\Sepidar\INV;

use Illuminate\Database\Eloquent\Model;

class ItemStockSummary extends Model
{
    public $table = 'INV.ItemStockSummary';
    public $connection = 'sqlsrv';
}
