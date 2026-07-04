<?php

namespace App\Models\Crm;

use App\Enums\FollowUpStatusEnum;
use App\Models\SetareganCo\User as Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FollowUp extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_follow_ups';

    protected $fillable = [
        'agent_id',
        'customer_id',
        'status',
        'failure_reason',
        'description',
        'due_date',
        'next_follow_up_date',
        'parent_id',
        'satisfaction',
        'warranty_satisfaction',
        'colleague',
        'resale',
    ];

    protected $casts = [
        'status' => FollowUpStatusEnum::class,
        'due_date' => 'datetime',
        'next_follow_up_date' => 'datetime',
        'satisfaction' => 'boolean',
        'warranty_satisfaction' => 'boolean',
        'colleague' => 'boolean',
        'resale' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FollowUp::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FollowUp::class, 'parent_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeTodayPending(Builder $query): void
    {
        $query->where('status', FollowUpStatusEnum::PENDING)
              ->whereDate('due_date', '<=', now());
    }

    public function scopeForAgent(Builder $query, int $agentId): void
    {
        $query->where('agent_id', $agentId);
    }
}
