<?php

namespace App\Models\SetareganCo;

use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    protected $connection = 'setaregan_sqlsrv';

    protected $table = 'DiscountCode';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = [
        'ProductGroupId',
        'ProductSubGroupId',
        'ProductId',
        'BrandId',
        'Code',
        'FromDate',
        'ToDate',
        'DiscountAmount',
        'IsPercent',
        'RemainCount',
        'NationalCode',
        'ForSpecialOffer',
        'Status',
        'ForPackage',
        'ShowInHomePage',
    ];

    protected function casts(): array
    {
        return [
            'FromDate' => 'datetime',
            'ToDate' => 'datetime',
            'DiscountAmount' => 'decimal:2',
            'IsPercent' => 'boolean',
            'RemainCount' => 'integer',
            'ForSpecialOffer' => 'boolean',
            'Status' => 'boolean',
            'ForPackage' => 'boolean',
            'ShowInHomePage' => 'boolean',
        ];
    }
}
