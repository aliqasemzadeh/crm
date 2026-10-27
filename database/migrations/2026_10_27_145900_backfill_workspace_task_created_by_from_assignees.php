<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $taskIds = DB::table('workspace_tasks')
            ->whereNull('created_by')
            ->whereNull('deleted_at')
            ->pluck('id');

        foreach ($taskIds as $taskId) {
            $firstAssigneeId = DB::table('workspace_task_user')
                ->where('task_id', $taskId)
                ->where('role', 'assignee')
                ->orderBy('assigned_at')
                ->orderBy('id')
                ->value('user_id');

            if (! $firstAssigneeId) {
                continue;
            }

            DB::table('workspace_tasks')
                ->where('id', $taskId)
                ->whereNull('created_by')
                ->update(['created_by' => $firstAssigneeId]);
        }
    }

    public function down(): void
    {
        //
    }
};
