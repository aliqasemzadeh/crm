<?php

namespace App\Models\SetareganCo;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $connection = 'setaregan_sqlsrv';
    protected $table = 'Order';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'UserInfoId',
        'Date',
        'TrackingCode',
        'CustomerName',
        'CityId',
        'Mobile',
        'NationalCode',
        'Tel',
        'PostalCode',
        'Address',
        'TaxAmount',
        'TaxPercentage',
        'FreeShipping',
        'SendCost',
        'PaymentDate',
        'BankTrackNo',
        'PayType',
        'SendType',
        'SendDate',
        'CourierId',
        'SendRegisterDate',
        'AdminDescription',
        'CustomerDescription',
        'TotalAmount',
        'Status',
        'UserIp',
        'IsPayed',
        'SendTrackingNo',
        'DiscountCodeId',
        'DiscountCodeAmount',
    ];

    public function details()
    {
        return $this->hasMany(OrderDetail::class, 'OrderId', 'Id');
    }

    public function userInfo()
    {
        return $this->belongsTo(UserInfo::class, 'UserInfoId', 'Id');
    }
}
