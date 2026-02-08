<?php

namespace App\Models\ServiceCenter;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairService extends Model
{
    use SoftDeletes;
    protected $table = 'service_center_repair_services';
    protected $fillable = ['repair_id', 'technician_user_id', 'description', 'price','service_type'];

    public function repair(): BelongsTo
    {
        return $this->belongsTo(Repair::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_user_id');
    }
}
