<?php

namespace App\Models\ServiceCenter;

use App\Enums\AssemblyLogTypeEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssemblyLog extends Model
{
    use SoftDeletes;

    protected $table = 'service_center_assembly_logs';

    protected $fillable = [
        'assembly_order_id',
        'assembly_id',
        'user_id',
        'type',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => AssemblyLogTypeEnum::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(AssemblyOrder::class, 'assembly_order_id');
    }

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(Assembly::class, 'assembly_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
