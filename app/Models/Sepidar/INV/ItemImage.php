<?php

namespace App\Models\Sepidar\INV;

use Illuminate\Database\Eloquent\Model;

class ItemImage extends Model
{
    public $table = 'INV.ItemImage';
    public $connection = 'sqlsrv';
    public $primaryKey = 'ItemImageId';
    
}
