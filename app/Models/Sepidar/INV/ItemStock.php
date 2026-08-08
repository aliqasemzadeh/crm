<?php

namespace App\Models\Sepidar\INV;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemStock extends Model
{
    public $table = 'INV.ItemStock';

    public $connection = 'sqlsrv';

    public $primaryKey = 'ItemStockID';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'ItemStockID',
        'ItemRef',
        'StockRef',
        'Version',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'ItemRef', 'ItemID');
    }
}
