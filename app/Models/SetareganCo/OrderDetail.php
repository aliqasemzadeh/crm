<?php

namespace App\Models\SetareganCo;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $connection = 'setaregan_sqlsrv';
    protected $table = 'OrderDetail';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'PackageId',
        'OrderId',
        'ProductPriceId',
        'Price',
        'Count',
        'TotalTaxAmount',
        'ProductOrderableValueId',
        'DiscountCodeCount',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'OrderId', 'Id');
    }

    public function productPrice()
    {
        return $this->belongsTo(ProductPrice::class, 'ProductPriceId', 'Id');
    }

    /**
     * Through ProductPrice, we can usually find the Product.
     * But since the user asked for relationship with Product,
     * and ProductPrice usually has a ProductId, I'll check ProductPrice model if needed.
     * Assuming ProductPrice has ProductId.
     */
    public function product()
    {
        // Many systems have ProductId directly in OrderDetail or linked via ProductPrice
        // Based on the SQL provided, there is no ProductId directly in OrderDetail.
        // It's likely linked via ProductPriceId.
        return $this->hasOneThrough(
            Product::class,
            ProductPrice::class,
            'Id', // Foreign key on ProductPrice table...
            'Id', // Foreign key on Product table...
            'ProductPriceId', // Local key on OrderDetail table...
            'ProductId' // Local key on ProductPrice table...
        );
    }
}
