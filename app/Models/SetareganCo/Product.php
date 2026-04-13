<?php

namespace App\Models\SetareganCo;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'setaregan_sqlsrv';
    protected $table = 'Product';
    protected $primaryKey = 'Id';

    public $timestamps = false; // The schema doesn't show created_at/updated_at columns

    protected $fillable = [
        'ProductSubGroupId',
        'Name',
        'MeasureId',
        'ShowMeasureInSite',
        'BrandId',
        'Status',
        'MetaDescription',
        'MetaKeyWords',
        'MetaTags',
        'MetaTitle',
        'ImageName',
        'IsShow',
        'RegisterDate',
        'Hits',
        'Description',
        'Weight',
        'PageLink',
        'IsFreeDelivery',
        'PurchaseLimitCount',
        'HasTax',
        'MinPrice',
        'ProductCode',
        'HeaderHtmlCode',
    ];

    /**
     * Get the prices for the product.
     */
    public function prices()
    {
        return $this->hasMany(ProductPrice::class, 'ProductId', 'Id');
    }
}
