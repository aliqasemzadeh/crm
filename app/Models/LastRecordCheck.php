<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LastRecordCheck extends Model
{
    protected $fillable = [
        'model',
        'last_record_id',
    ];
}
