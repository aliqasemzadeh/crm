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

    /**
     * Get the color associated with the product price.
     */
    public function color()
    {
        return $this->belongsTo(Color::class, 'ColorId', 'Id');
    }

    /**
     * Get the guarantee associated with the product price.
     */
    public function guarantee()
    {
        return $this->belongsTo(Guarantee::class, 'GuaranteeId', 'Id');
    }

    /**
     * Get the item associated with the product price.
     */
    public function item()
    {
        return $this->belongsTo(\App\Models\Sepidar\INV\Item::class, 'ProductId', 'IranCode');
    }
}
