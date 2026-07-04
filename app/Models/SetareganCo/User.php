<?php

namespace App\Models\SetareganCo;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $connection = 'setaregan_sqlsrv';
    protected $table = 'User';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'UserName',
        'RegisterDate',
        'Description',
        'UserType',
        'UserStatus',
        'Email',
        'EmailConfirmed',
        'PasswordHash',
        'SecurityStamp',
        'PhoneNumber',
        'PhoneNumberConfirmed',
        'TwoFactorEnabled',
        'LockoutEndDateUtc',
        'LockoutEnabled',
        'AccessFailedCount',
        'NormalizedUserName',
    ];

    protected $casts = [
        'RegisterDate' => 'datetime',
        'EmailConfirmed' => 'boolean',
        'PhoneNumberConfirmed' => 'boolean',
        'TwoFactorEnabled' => 'boolean',
        'LockoutEnabled' => 'boolean',
        'LockoutEndDateUtc' => 'datetime',
        'AccessFailedCount' => 'integer',
        'UserStatus' => 'integer',
        'UserType' => 'integer',
    ];

    public function userInfo()
    {
        return $this->hasOne(UserInfo::class, 'UserId', 'Id');
    }

    public function orders()
    {
        return $this->hasManyThrough(Order::class, UserInfo::class, 'UserId', 'UserInfoId', 'Id', 'Id');
    }
}
