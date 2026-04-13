<?php

namespace App\Models\SetareganCo;

use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    protected $connection = 'setaregan_sqlsrv';
    protected $table = 'ProductPrice';
    protected $primaryKey = 'Id';

    public $timestamps = false; // No timestamps in schema

    protected $fillable = [
        'ProductId',
        'GuaranteeId',
        'ColorId',
        'Price',
        'Quantity',
        'ForeignCurrencyId',
        'ForeignCurrencyPrice',
        'PriceChangeDate',
    ];

    /**
     * Get the product that owns the price.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'ProductId', 'Id');
    }
}
