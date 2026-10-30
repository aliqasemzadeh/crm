<?php

namespace App\Models\ServiceCenter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssemblyOrderPart extends Model
{
    use SoftDeletes;

    protected $table = 'service_center_assembly_order_parts';

    protected $fillable = [
        'assembly_order_id',
        'sepidar_item_id',
        'sepidar_invoice_item_id',
        'title',
        'quantity_per_unit',
        'requires_serial',
        'sort_order',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_per_unit' => 'integer',
            'requires_serial' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(AssemblyOrder::class, 'assembly_order_id');
    }

    public function assemblyParts(): HasMany
    {
        return $this->hasMany(AssemblyPart::class, 'assembly_order_part_id');
    }
}
