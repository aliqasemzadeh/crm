<?php

namespace App\Models\Sepidar\SLS;

use App\Models\Sepidar\INV\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceNoteItem extends Model
{
    public $table = 'SLS.PriceNoteItem';
    public $connection = 'sqlsrv';
    public $primaryKey = 'PriceNoteItemID';


    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'ItemRef', 'ItemID');
    }
}
