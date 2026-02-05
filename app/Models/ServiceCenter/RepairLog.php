<?php

namespace App\Models\ServiceCenter;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairLog extends Model
{
    use SoftDeletes;

    protected $fillable = ['repair_id', 'technician_user_id', 'description', 'status'];

    public function repair(): BelongsTo
    {
        return $this->belongsTo(Repair::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_user_id');
    }
}
