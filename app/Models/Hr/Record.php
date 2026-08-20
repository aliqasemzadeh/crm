<?php

namespace App\Models\Hr;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Record extends Model
{
    protected $table = 'hr_records';

    protected $attributes = [
        'is_device_approved' => false,
    ];

    protected $fillable = [
        'user_id',
        'user_device_id',
        'type',
        'recorded_at',
        'is_device_approved',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'is_device_approved' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'user_device_id');
    }
}
