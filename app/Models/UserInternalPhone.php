<?php

namespace App\Models;

use App\Models\Issabel\Device as IssabelDevice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInternalPhone extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): ?IssabelDevice
    {
        return IssabelDevice::query()->find($this->device_id);
    }
}
