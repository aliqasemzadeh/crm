<?php

namespace App\Models\Workspace;

use Illuminate\Database\Eloquent\Model;

class TaskReport extends Model
{
    protected $table = 'workspace_task_reports';

    protected $fillable = [
        'task_id', 'user_id', 'type', 'body', 'spent_minutes', 'meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function files()
    {
        return $this->hasMany(TaskFile::class, 'task_report_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
