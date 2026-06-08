<?php

namespace App\Models\Workspace;

use App\Models\User;
use App\Models\Workspace\TaskChecklist;
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
        'starts_at',
        'due_at',
        'done_at',

        // Creator / source
        'created_by',
        'source',            // user | system | manager
        'repeat_type',       // none | daily | weekly | monthly
        'repeat_weekday',    // 0..6 (sat..fri)
        'repeat_monthday',   // 1..31
        'last_repeated_at',
        'next_repeat_at',
        'generated_from_task_id',
        'is_locked',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'starts_at'   => 'datetime',
        'due_at'      => 'datetime',
        'done_at'     => 'datetime',
        'approved_at' => 'datetime',
        'last_repeated_at' => 'datetime',
        'next_repeat_at' => 'datetime',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    /* ---------------- Booted ---------------- */
    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            if ($task->isDirty('status') && $task->status === 'done') {
                if ($task->checklists()->where('is_done', false)->exists()) {
                    throw new \Exception(__('app.task.checklist_not_completed'));
                }
            }

            if ($task->isDirty('status')) {
                if ($task->status === 'done') {
                    $task->done_at = now();
                    if (in_array($task->approval_status ?? 'none', ['none', null, 'rejected'], true)) {
                        $task->approval_status = 'pending';
                    }
                } else {
                    $task->done_at = null;
                }
            }
        });
    }

    /* ---------------- Users ---------------- */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function generatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'generated_from_task_id');
    }

    public function generatedTasks()
    {
        return $this->hasMany(self::class, 'generated_from_task_id');
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

    public function reports()
    {
        return $this->hasMany(\App\Models\Workspace\TaskReport::class, 'task_id')
            ->latest();
    }

    public function checklists()
    {
        return $this->hasMany(TaskChecklist::class, 'task_id')->orderBy('order');
    }

    public function canBeManagedBy(?User $user): bool
    {
        if (! $this->is_locked) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return (int) $this->locked_by === (int) $user->id || $user->hasRole('administrator');
    }
}
