<?php

namespace App\Models\ServiceCenter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssemblyPart extends Model
{
    use SoftDeletes;

    protected $table = 'service_center_assembly_parts';

    protected $fillable = [
        'assembly_id',
        'assembly_order_part_id',
        'sepidar_item_id',
        'title',
        'serial_number',
        'notes',
    ];

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(Assembly::class, 'assembly_id');
    }

    public function orderPart(): BelongsTo
    {
        return $this->belongsTo(AssemblyOrderPart::class, 'assembly_order_part_id');
    }
}
