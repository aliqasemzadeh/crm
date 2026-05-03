<?php

namespace App\Models\Issabel;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $connection = 'issabel-config';

    protected $table = 'devices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
