<?php

namespace App\Models\Sepidar\SLS;

use Illuminate\Database\Eloquent\Model;

class SaleType extends Model
{
    public $table = 'SLS.SaleType';

    public $connection = 'sqlsrv';

    public $primaryKey = 'SaleTypeId';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'SaleTypeId',
        'Number',
        'Title',
        'Title_En',
        'SaleTypeMarket',
        'PartSalesSLRef',
        'ServiceSalesSLRef',
        'PartSalesReturnSLRef',
        'ServiceSalesReturnSLRef',
        'PartSalesDiscountSLRef',
        'ServiceSalesDiscountSLRef',
        'SalesAdditionSLRef',
    ];
}
