<?php

namespace App\Models\Hr;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Record extends Model
{
    public const CATEGORY_WORK = 'work';

    public const CATEGORY_LEAVE = 'leave';

    public const CATEGORY_MISSION = 'mission';

    public const CATEGORIES = [
        self::CATEGORY_WORK,
        self::CATEGORY_LEAVE,
        self::CATEGORY_MISSION,
    ];

    protected $table = 'hr_records';

    protected $attributes = [
        'is_device_approved' => false,
        'is_manual' => false,
        'category' => self::CATEGORY_WORK,
    ];

    protected $fillable = [
        'user_id',
        'user_device_id',
        'type',
        'category',
        'is_manual',
        'recorded_at',
        'is_device_approved',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'is_device_approved' => 'boolean',
        'is_manual' => 'boolean',
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
