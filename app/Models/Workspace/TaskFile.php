<?php

namespace App\Models\Workspace;

use Illuminate\Database\Eloquent\Model;

class TaskFile extends Model
{
    protected $table = 'workspace_task_files';

    protected $fillable = ['task_id', 'task_report_id', 'user_id', 'disk', 'path', 'original_name', 'mime', 'size'];

    public function report()
    {
        return $this->belongsTo(TaskReport::class, 'task_report_id');
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
