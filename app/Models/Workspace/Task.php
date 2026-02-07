<?php

namespace App\Models\Workspace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;
    protected $table = 'workspace_tasks';

    protected $fillable = [
        'title',
        'description',

        // Kanban
        'status',            // planning | doing | done
        'order',

        // Review / Approval
        'approval_status',   // none | pending | approved | rejected
        'approved_by',
        'approved_at',
        'review_note',

        // Meta
        'priority',          // low | medium | high | urgent
        'due_at',

        // Creator / source
        'created_by',
        'source',            // user | system | manager
    ];

    protected $casts = [
        'due_at'      => 'datetime',
        'approved_at' => 'datetime',
    ];

    /* ---------------- Users ---------------- */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'workspace_task_user',
            'task_id',
            'user_id'
        )
            ->withPivot(['role', 'assigned_at', 'assigned_by'])
            ->withTimestamps();
    }

    public function assignees(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'assignee');
    }

    public function reviewers(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'reviewer');
    }

    public function watchers(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'watcher');
    }

    /* ---------------- Polymorphic (Generic) ---------------- */

    /**
     * Generic polymorphic relation.
     * Task may have ZERO or MANY related entities.
     * Future-proof, no need to know entity types now.
     */
    public function taskables(): MorphToMany
    {
        return $this->morphToMany(
            Model::class,
            'taskable',
            'workspace_taskables',
            'task_id',
            'taskable_id'
        )->withTimestamps();
    }

    /* ---------------- Helpers ---------------- */

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function needsReview(): bool
    {
        return $this->status === 'done' && $this->approval_status === 'pending';
    }
}
