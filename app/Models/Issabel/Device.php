<?php

namespace App\Models\Issabel;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $connection = 'issabel-config';

    protected $table = 'devices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * کاربرانی که این خط داخلی Issabel به آن‌ها متصل است (users.internal_phone_id = devices.id).
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'internal_phone_id', 'id');
    }
}
