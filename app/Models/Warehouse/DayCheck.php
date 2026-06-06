<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;

class DayCheck extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'items',
        'item_stocks',
        'item_checks',
        'user_comment',
        'admin_comment',
        'check_at',
        'approve_at',
        'reject_at',
    ];

    protected $casts = [
        'items' => 'array',
        'item_stocks' => 'array',
        'item_checks' => 'array',
        'check_at' => 'datetime',
        'approve_at' => 'datetime',
        'reject_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
