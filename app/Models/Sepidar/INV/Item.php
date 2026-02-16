<?php

namespace App\Models\Sepidar\INV;

use App\Models\Sepidar\FMK\User;
use App\Models\Sepidar\GNR\Grouping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    public $table = 'INV.Item';
    public $connection = 'sqlsrv';
    public $primaryKey = 'ItemID';

    public function grouping(): BelongsTo
    {
        return $this->belongsTo(Grouping::class, 'CodingGroupRef', 'GroupingID');
    }

    public function image(): HasOne
    {
        return $this->hasOne(ItemImage::class, 'ItemRef', 'ItemID');
    }

    public function stockSummaries(): HasMany
    {
        return $this->hasMany(ItemStockSummary::class, 'ItemRef', 'ItemID');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Creator', 'UserID');
    }
}
