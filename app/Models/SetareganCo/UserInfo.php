<?php

namespace App\Models\SetareganCo;

use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    protected $connection = 'setaregan_sqlsrv';
    protected $table = 'UserInfo';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'UserId',
        'Name',
        'Family',
        'Email',
        'NationalCode',
        'AreaId',
        'CityId',
        'Mobile',
        'Tel',
        'PostalCode',
        'Address',
        'Gender',
        'BirthDate',
        'Status',
        'NationalCodeImage',
        'BusinessLicenseImage',
        'NationalCodeImageIsLock',
        'BusinessLicenseImageIsLock',
        'NormalizedEmail',
        'ReferrerCode',
        'MyReferrerCode',
        'Credit',
        'CoworkerGroupId',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'UserId', 'Id');
    }
}
