<?php

namespace App\Models\Workspace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class PurchaseRequest extends Model implements ApprovableModel
{
    use Approvable, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'quantity',
        'description',
        'price',
        'supplier',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        return true;
    }
}
