<?php

namespace App\Models\Warehouse;

use App\Enums\WarehouseWarrantyShipmentStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyShipment extends Model
{
    protected $table = 'warehouse_warranty_shipments';

    protected $fillable = [
        'item_title',
        'quantity',
        'serial_number',
        'warranty_company',
        'sent_at',
        'received_at',
        'status',
        'description',
        'last_reminded_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sent_at' => 'date',
            'received_at' => 'date',
            'last_reminded_at' => 'datetime',
            'status' => WarehouseWarrantyShipmentStatusEnum::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<WarrantyShipment>  $query
     * @return Builder<WarrantyShipment>
     */
    public function scopeOverdue(Builder $query, int $days = 28): Builder
    {
        return $query
            ->where('status', '!=', WarehouseWarrantyShipmentStatusEnum::Received->value)
            ->whereDate('sent_at', '<=', now()->subDays($days)->toDateString());
    }

    /**
     * @param  Builder<WarrantyShipment>  $query
     * @return Builder<WarrantyShipment>
     */
    public function scopeNeedsReminder(Builder $query, int $days = 28): Builder
    {
        return $query
            ->overdue($days)
            ->where(function (Builder $q): void {
                $q->whereNull('last_reminded_at')
                    ->orWhereDate('last_reminded_at', '<', now()->toDateString());
            });
    }
}
