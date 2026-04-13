<?php

namespace App\Models\Accounting\PriceNote;

use Illuminate\Database\Eloquent\Model;

class ItemPriceFetcher extends Model
{
    protected $fillable = [
        'item_id',
        'fetcher',
        'link',
        'price',
        'message',
    ];
}
