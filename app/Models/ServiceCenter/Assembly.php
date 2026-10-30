<?php

namespace App\Models\ServiceCenter;

use App\Enums\AssemblyLogTypeEnum;
use App\Enums\AssemblyStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Assembly extends Model
{
    use SoftDeletes;

    protected $table = 'service_center_assemblies';

    protected $fillable = [
        'assembly_order_id',
        'unit_number',
        'system_serial',
        'status',
        'assembled_by_user_id',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssemblyStatusEnum::class,
            'unit_number' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(AssemblyOrder::class, 'assembly_order_id');
    }

    public function assembledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assembled_by_user_id');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(AssemblyPart::class, 'assembly_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AssemblyLog::class, 'assembly_id')->latest();
    }

    public function getStatusEnumAttribute(): AssemblyStatusEnum
    {
        return $this->status instanceof AssemblyStatusEnum
            ? $this->status
            : AssemblyStatusEnum::tryFromSafe(is_string($this->status) ? $this->status : null);
    }

    public function durationMinutes(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return (int) $this->started_at->diffInMinutes($this->completed_at);
    }

    public function applyStatus(AssemblyStatusEnum $status, ?string $description = null): void
    {
        $old = $this->status instanceof AssemblyStatusEnum
            ? $this->status
            : AssemblyStatusEnum::tryFromSafe((string) $this->status);

        $updates = ['status' => $status];

        if ($status === AssemblyStatusEnum::InProgress && ! $this->started_at) {
            $updates['started_at'] = now();
        }

        if (in_array($status, [AssemblyStatusEnum::Ready, AssemblyStatusEnum::Delivered], true)) {
            $updates['completed_at'] = $this->completed_at ?? now();
            if (! $this->started_at) {
                $updates['started_at'] = $this->started_at ?? now();
            }
        }

        if ($status === AssemblyStatusEnum::Pending) {
            $updates['started_at'] = null;
            $updates['completed_at'] = null;
        }

        $this->update($updates);

        AssemblyLog::create([
            'assembly_order_id' => $this->assembly_order_id,
            'assembly_id' => $this->id,
            'user_id' => Auth::id(),
            'type' => $status === AssemblyStatusEnum::ReturnedWarranty
                ? AssemblyLogTypeEnum::WarrantyReturn
                : AssemblyLogTypeEnum::StatusChange,
            'status' => $status->value,
            'description' => $description ?: __('app.assembly_status_changed_log', [
                'from' => $old->label(),
                'to' => $status->label(),
                'unit' => $this->unit_number,
            ]),
        ]);

        $this->order?->recalculateStatus();
    }
}
