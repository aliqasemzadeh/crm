<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'url',
        'method',
        'ip'
    ];

    // برقراری رابطه با مدل کاربر
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
